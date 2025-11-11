<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/db.php';

// Prepare a JSON-ready report
$report_data = [
    'animal_count' => (int)$pdo->query("SELECT COUNT(*) FROM animals")->fetchColumn(),
    'production_30d' => [
        'milk' => (float)$pdo->query("SELECT COALESCE(SUM(quantity),0) FROM production_logs WHERE type='Milk' AND production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn(),
        'eggs' => (float)$pdo->query("SELECT COALESCE(SUM(quantity),0) FROM production_logs WHERE type='Eggs' AND production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn(),
        'meat' => (float)$pdo->query("SELECT COALESCE(SUM(quantity),0) FROM production_logs WHERE type='Meat' AND production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn(),
    ],
    'health_count_30d' => (int)$pdo->query("SELECT COUNT(*) FROM health_logs WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn(),
    'financial_data' => []
];

// Fetch weekly financial data for the last 90 days for the chart
$stmt = $pdo->query("
    SELECT 
        YEARWEEK(transaction_date, 1) as year_week,
        SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) AS weekly_profit
    FROM (
        SELECT income_date as transaction_date, amount, 'income' as type FROM income
        UNION ALL
        SELECT expense_date as transaction_date, amount, 'expense' as type FROM expenses
    ) AS transactions
    WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
    GROUP BY year_week
    ORDER BY year_week ASC
");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $report_data['financial_data'][] = $row;
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<style>
    /* Custom styles for a modern, clean look */
    .card-custom {
        border-radius: 1rem;
        border: none;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .card-custom:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.1);
    }
    .btn-custom {
        border-radius: 50rem;
        font-weight: bold;
        transition: background-color 0.2s;
    }
    .btn-success-custom {
        background-color: #28a745;
        border-color: #28a745;
    }
    .btn-success-custom:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }
    .text-success-custom {
        color: #28a745 !important;
    }
    .reports-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    @media print {
        body * {
            visibility: hidden;
        }
        .printable-content, .printable-content * {
            visibility: visible;
        }
        .printable-content {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .btn, .mt-3 {
            display: none;
        }
    }
</style>

<div class="reports-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Reports & Analytics</h4>
        <button class="btn btn-success btn-custom" onclick="window.print()">Print Report</button>
    </div>

    <div class="printable-content">
        <!-- Summary Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3"><div class="card card-custom shadow-sm"><div class="card-body"><h6>Animals</h6><div class="display-6 text-success-custom" id="animal-count"></div></div></div></div>
            <div class="col-md-3"><div class="card card-custom shadow-sm"><div class="card-body"><h6>Milk (30d)</h6><div class="display-6 text-success-custom" id="milk-prod"></div></div></div></div>
            <div class="col-md-3"><div class="card card-custom shadow-sm"><div class="card-body"><h6>Eggs (30d)</h6><div class="display-6 text-success-custom" id="eggs-prod"></div></div></div></div>
            <div class="col-md-3"><div class="card card-custom shadow-sm"><div class="card-body"><h6>Meat (kg, 30d)</h6><div class="display-6 text-success-custom" id="meat-prod"></div></div></div></div>
            <div class="col-md-3"><div class="card card-custom shadow-sm"><div class="card-body"><h6>Health Logs (30d)</h6><div class="display-6 text-success-custom" id="health-count"></div></div></div></div>
            <div class="col-md-3"><div class="card card-custom shadow-sm"><div class="card-body"><h6>Profit/Loss (30d)</h6><div class="display-6 text-success-custom" id="profit-loss"></div></div></div></div>
        </div>

        <!-- Charts Section -->
        <div class="card card-custom shadow-sm mb-4">
            <div class="card-body">
                <h6 class="mb-3"><span class="text-success fw-bold">Profit/Loss over 90 Days</span></h6>
                <canvas id="profitLossChart"></canvas>
            </div>
        </div>

        <p class="text-muted mt-3">This report provides a high-level summary. For detailed data, please refer to the individual modules.</p>
    </div>
</div>

<!-- Chart.js and Data Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const reportData = <?= json_encode($report_data) ?>;
        
        // Populate the summary cards with data
        document.getElementById('animal-count').textContent = reportData.animal_count.toLocaleString();
        document.getElementById('milk-prod').textContent = reportData.production_30d.milk.toLocaleString();
        document.getElementById('eggs-prod').textContent = reportData.production_30d.eggs.toLocaleString();
        document.getElementById('meat-prod').textContent = reportData.production_30d.meat.toLocaleString();
        document.getElementById('health-count').textContent = reportData.health_count_30d.toLocaleString();

        const profitLoss30d = reportData.financial_data.slice(-4).reduce((sum, item) => sum + parseFloat(item.weekly_profit), 0);
        document.getElementById('profit-loss').textContent = `$${profitLoss30d.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

        // Prepare data for the chart
        const labels = reportData.financial_data.map(item => `Week ${item.year_week.toString().slice(4)}`);
        const data = reportData.financial_data.map(item => parseFloat(item.weekly_profit));
        
        // Initialize the chart
        const ctx = document.getElementById('profitLossChart').getContext('2d');
        const profitLossChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Weekly Profit/Loss ($)',
                    data: data,
                    backgroundColor: data.map(val => val >= 0 ? 'rgba(40, 167, 69, 0.6)' : 'rgba(220, 53, 69, 0.6)'),
                    borderColor: data.map(val => val >= 0 ? 'rgba(40, 167, 69, 1)' : 'rgba(220, 53, 69, 1)'),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Amount ($)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Time (Week)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
