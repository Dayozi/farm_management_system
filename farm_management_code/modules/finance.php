<?php
// require auth.php to handle user authentication and permissions
require_once __DIR__ . '/../includes/auth.php';
require_login();
// include the header file
include __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-12">
        <div class="p-4 bg-white border rounded-3 shadow-sm">
            <h4 class="mb-0">Finance</h4>
        </div>
    </div>
</div>

<div class="row g-3 mt-3">
    <!-- Add Expense Card -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Add Expense</h6>
                <form id="expenseForm">
                    <input type="hidden" name="action" value="add_expense">
                    <div class="mb-2">
                        <label class="form-label">Date</label>
                        <input type="date" name="expense_date" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Category</label>
                        <input name="category" class="form-control" placeholder="Feed, Medicine, etc." required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">Add Expense</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Income Card -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Add Income</h6>
                <form id="incomeForm">
                    <input type="hidden" name="action" value="add_income">
                    <div class="mb-2">
                        <label class="form-label">Date</label>
                        <input type="date" name="income_date" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Source</label>
                        <input name="source" class="form-control" placeholder="Milk Sales, Animal Sales" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Add Income</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Summary</h6>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Total Income</span>
                        <strong id="total-income">0.00</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Total Expenses</span>
                        <strong id="total-expenses">0.00</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Profit / Loss</span>
                        <strong id="profit-loss">0.00</strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<hr>
<div class="row g-3">
    <!-- Recent Expenses Table -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Recent Expenses</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="expensesTableBody">
                            <!-- Expenses data will be loaded by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Income Table -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Recent Income</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Source</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="incomeTableBody">
                            <!-- Income data will be loaded by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const expenseForm = document.getElementById('expenseForm');
        const incomeForm = document.getElementById('incomeForm');
        const totalIncomeEl = document.getElementById('total-income');
        const totalExpensesEl = document.getElementById('total-expenses');
        const profitLossEl = document.getElementById('profit-loss');
        const expensesTableBody = document.getElementById('expensesTableBody');
        const incomeTableBody = document.getElementById('incomeTableBody');

        // Function to fetch and render all finance data
        async function fetchAndRenderFinanceData() {
            try {
                const response = await fetch('finance_api.php?action=fetch_data');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const result = await response.json();
                if (result.status === 'success') {
                    // Update Summary
                    const summary = result.data.summary;
                    totalIncomeEl.textContent = summary.total_income;
                    totalExpensesEl.textContent = summary.total_expenses;
                    profitLossEl.textContent = summary.profit;
                    profitLossEl.classList.remove('text-success', 'text-danger');
                    if (parseFloat(summary.profit) >= 0) {
                        profitLossEl.classList.add('text-success');
                    } else {
                        profitLossEl.classList.add('text-danger');
                    }

                    // Render Expenses Table
                    renderExpenses(result.data.expenses);

                    // Render Income Table
                    renderIncome(result.data.income);
                } else {
                    alert('Error fetching data: ' + result.message);
                }
            } catch (error) {
                console.error('Fetch error:', error);
                alert('An error occurred while fetching finance data.');
            }
        }

        // Helper function to render expenses table
        function renderExpenses(expenses) {
            expensesTableBody.innerHTML = ''; // Clear existing rows
            if (expenses.length === 0) {
                expensesTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No expenses found.</td></tr>';
                return;
            }
            expenses.forEach(exp => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${exp.expense_date}</td>
                    <td>${exp.category}</td>
                    <td>$${exp.amount}</td>
                    <td>${exp.description}</td>
                    <td><button class="btn btn-sm btn-danger delete-expense-btn" data-id="${exp.expense_id}">Delete</button></td>
                `;
                expensesTableBody.appendChild(row);
            });
        }

        // Helper function to render income table
        function renderIncome(income) {
            incomeTableBody.innerHTML = ''; // Clear existing rows
            if (income.length === 0) {
                incomeTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No income found.</td></tr>';
                return;
            }
            income.forEach(inc => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${inc.income_date}</td>
                    <td>${inc.source}</td>
                    <td>$${inc.amount}</td>
                    <td>${inc.description}</td>
                    <td><button class="btn btn-sm btn-danger delete-income-btn" data-id="${inc.income_id}">Delete</button></td>
                `;
                incomeTableBody.appendChild(row);
            });
        }

        // Handle expense form submission
        expenseForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const response = await fetch('finance_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (response.ok && result.status === 'success') {
                    alert(result.message);
                    this.reset();
                    fetchAndRenderFinanceData();
                } else {
                    alert('Error: ' + (result.message || 'An unknown error occurred.'));
                }
            } catch (error) {
                console.error('Expense form submission error:', error);
                alert('An error occurred. Check the console for details.');
            }
        });

        // Handle income form submission
        incomeForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const response = await fetch('finance_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (response.ok && result.status === 'success') {
                    alert(result.message);
                    this.reset();
                    fetchAndRenderFinanceData();
                } else {
                    alert('Error: ' + (result.message || 'An unknown error occurred.'));
                }
            } catch (error) {
                console.error('Income form submission error:', error);
                alert('An error occurred. Check the console for details.');
            }
        });

        // Event delegation for deleting expenses
        expensesTableBody.addEventListener('click', async function(e) {
            const deleteBtn = e.target.closest('.delete-expense-btn');
            if (deleteBtn) {
                const expenseId = deleteBtn.dataset.id;
                if (confirm('Are you sure you want to delete this expense record?')) {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete_expense');
                        formData.append('expense_id', expenseId);
                        const response = await fetch('finance_api.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();
                        if (response.ok && result.status === 'success') {
                            alert(result.message);
                            fetchAndRenderFinanceData();
                        } else {
                            alert('Error: ' + (result.message || 'An unknown error occurred.'));
                        }
                    } catch (error) {
                        console.error('Delete expense error:', error);
                        alert('An error occurred. Check the console for details.');
                    }
                }
            }
        });

        // Event delegation for deleting income
        incomeTableBody.addEventListener('click', async function(e) {
            const deleteBtn = e.target.closest('.delete-income-btn');
            if (deleteBtn) {
                const incomeId = deleteBtn.dataset.id;
                if (confirm('Are you sure you want to delete this income record?')) {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete_income');
                        formData.append('income_id', incomeId);
                        const response = await fetch('finance_api.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();
                        if (response.ok && result.status === 'success') {
                            alert(result.message);
                            fetchAndRenderFinanceData();
                        } else {
                            alert('Error: ' + (result.message || 'An unknown error occurred.'));
                        }
                    } catch (error) {
                        console.error('Delete income error:', error);
                        alert('An error occurred. Check the console for details.');
                    }
                }
            }
        });

        // Initial fetch of data on page load
        fetchAndRenderFinanceData();
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
