<?php
// This API endpoint handles all CRUD operations for the finance module.
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/db.php';
// Include the notification helper to log actions
require_once __DIR__ . '/../includes/notification_helper.php';

$action = $_REQUEST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid action specified.'];

try {
    switch ($action) {
        case 'fetch_data':
            // Fetch all expenses
            $expenses_stmt = $pdo->query("SELECT * FROM expenses ORDER BY expense_date DESC, expense_id DESC");
            $expenses = $expenses_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch all income
            $income_stmt = $pdo->query("SELECT * FROM income ORDER BY income_date DESC, income_id DESC");
            $income = $income_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate totals and profit
            $exp_total = $pdo->query("SELECT COALESCE(SUM(amount), 0) as t FROM expenses")->fetch()['t'];
            $inc_total = $pdo->query("SELECT COALESCE(SUM(amount), 0) as t FROM income")->fetch()['t'];
            $profit = $inc_total - $exp_total;

            $response = [
                'status' => 'success',
                'data' => [
                    'expenses' => $expenses,
                    'income' => $income,
                    'summary' => [
                        'total_income' => number_format($inc_total, 2, '.', ''),
                        'total_expenses' => number_format($exp_total, 2, '.', ''),
                        'profit' => number_format($profit, 2, '.', '')
                    ]
                ]
            ];
            break;

        case 'add_expense':
            $expense_date = $_POST['expense_date'] ?? null;
            $category = trim($_POST['category'] ?? '');
            $amount = floatval($_POST['amount'] ?? 0);
            $description = trim($_POST['description'] ?? '');

            if (empty($expense_date) || empty($category) || $amount <= 0) {
                http_response_code(400);
                throw new Exception('Date, category, and a positive amount are required for an expense.');
            }

            $stmt = $pdo->prepare("INSERT INTO expenses (expense_date, category, amount, description, recorded_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$expense_date, $category, $amount, $description, $_SESSION['user']['user_id']]);

            // Log a notification for the new expense
            log_notification($pdo, $_SESSION['user']['user_id'], 'Finance Management', "New expense of $" . number_format($amount, 2) . " added to category '{$category}'.");

            $response = ['status' => 'success', 'message' => 'Expense added successfully.'];
            break;

        case 'add_income':
            $income_date = $_POST['income_date'] ?? null;
            $source = trim($_POST['source'] ?? '');
            $amount = floatval($_POST['amount'] ?? 0);
            $description = trim($_POST['description'] ?? '');

            if (empty($income_date) || empty($source) || $amount <= 0) {
                http_response_code(400);
                throw new Exception('Date, source, and a positive amount are required for income.');
            }
            
            $stmt = $pdo->prepare("INSERT INTO income (income_date, source, amount, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$income_date, $source, $amount, $description]);

            // Log a notification for the new income
            log_notification($pdo, $_SESSION['user']['user_id'], 'Finance Management', "New income of $" . number_format($amount, 2) . " added from source '{$source}'.");

            $response = ['status' => 'success', 'message' => 'Income added successfully.'];
            break;

        case 'delete_expense':
            $expense_id = $_POST['expense_id'] ?? null;
            if (empty($expense_id)) {
                http_response_code(400);
                throw new Exception('Expense ID is required for deletion.');
            }

            // Fetch expense details before deleting for the notification log
            $stmt_fetch = $pdo->prepare("SELECT amount, category FROM expenses WHERE expense_id = ?");
            $stmt_fetch->execute([$expense_id]);
            $deleted_expense = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("DELETE FROM expenses WHERE expense_id = ?");
            $stmt->execute([$expense_id]);

            if ($deleted_expense) {
                // Log a notification for the deleted expense
                log_notification($pdo, $_SESSION['user']['user_id'], 'Finance Management', "Expense of $" . number_format($deleted_expense['amount'], 2) . " from category '{$deleted_expense['category']}' deleted.");
            }

            $response = ['status' => 'success', 'message' => 'Expense deleted successfully.'];
            break;

        case 'delete_income':
            $income_id = $_POST['income_id'] ?? null;
            if (empty($income_id)) {
                http_response_code(400);
                throw new Exception('Income ID is required for deletion.');
            }

            // Fetch income details before deleting for the notification log
            $stmt_fetch = $pdo->prepare("SELECT amount, source FROM income WHERE income_id = ?");
            $stmt_fetch->execute([$income_id]);
            $deleted_income = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("DELETE FROM income WHERE income_id = ?");
            $stmt->execute([$income_id]);

            if ($deleted_income) {
                // Log a notification for the deleted income
                log_notification($pdo, $_SESSION['user']['user_id'], 'Finance Management', "Income of $" . number_format($deleted_income['amount'], 2) . " from source '{$deleted_income['source']}' deleted.");
            }

            $response = ['status' => 'success', 'message' => 'Income deleted successfully.'];
            break;

        default:
            http_response_code(400);
            $response = ['status' => 'error', 'message' => 'Invalid action.'];
            break;
    }
} catch (Exception $e) {
    if (http_response_code() === 200) {
        http_response_code(500);
    }
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);
