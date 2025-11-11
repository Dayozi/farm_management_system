<?php
// This API handles all CRUD operations for the Labor module.
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'clock_in':
            $stmt = $pdo->prepare("INSERT INTO time_logs (user_id, login_time) VALUES (?, NOW())");
            $stmt->execute([$_SESSION['user']['user_id']]);
            log_notification($pdo, $_SESSION['user']['user_id'], 'Labor & Workforce', "User clocked in.");
            echo json_encode(['status' => 'success', 'message' => 'Clocked in successfully.']);
            break;
        
        case 'clock_out':
            $stmt = $pdo->prepare("UPDATE time_logs SET logout_time = NOW() WHERE user_id=? AND logout_time IS NULL ORDER BY log_id DESC LIMIT 1");
            $stmt->execute([$_SESSION['user']['user_id']]);
            log_notification($pdo, $_SESSION['user']['user_id'], 'Labor & Workforce', "User clocked out.");
            echo json_encode(['status' => 'success', 'message' => 'Clocked out successfully.']);
            break;

        case 'add_task':
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $assigned_to = (int)$_POST['assigned_to'];
            $due_date = $_POST['due_date'] ?? null;
            
            if (empty($title) || empty($assigned_to)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Task title and assigned user are required.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO tasks (title, description, assigned_to, due_date, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $assigned_to, $due_date, $_SESSION['user']['user_id']]);
            log_notification($pdo, $_SESSION['user']['user_id'], 'Labor & Workforce', "New task '{$title}' created.");
            echo json_encode(['status' => 'success', 'message' => 'Task created successfully.']);
            break;

        case 'edit_task':
            $task_id = (int)$_POST['task_id'];
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $assigned_to = (int)$_POST['assigned_to'];
            $due_date = $_POST['due_date'] ?? null;
            
            if (empty($task_id) || empty($title) || empty($assigned_to)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Task ID, title, and assigned user are required for edit.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE tasks SET title=?, description=?, assigned_to=?, due_date=? WHERE task_id=?");
            $stmt->execute([$title, $description, $assigned_to, $due_date, $task_id]);
            log_notification($pdo, $_SESSION['user']['user_id'], 'Labor & Workforce', "Task #{$task_id} updated.");
            echo json_encode(['status' => 'success', 'message' => 'Task updated successfully.']);
            break;

        case 'delete_task':
            $task_id = (int)$_POST['task_id'];
            if (empty($task_id)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Task ID is required for deletion.']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE task_id = ?");
            $stmt->execute([$task_id]);
            log_notification($pdo, $_SESSION['user']['user_id'], 'Labor & Workforce', "Task #{$task_id} deleted.");
            echo json_encode(['status' => 'success', 'message' => 'Task deleted successfully.']);
            break;
        
        case 'complete_task':
            $task_id = (int)$_POST['task_id'];
            if (empty($task_id)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Task ID is required.']);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE tasks SET status='Completed' WHERE task_id=? AND assigned_to=?");
            $stmt->execute([$task_id, $_SESSION['user']['user_id']]);
            log_notification($pdo, $_SESSION['user']['user_id'], 'Labor & Workforce', "Task #{$task_id} marked as completed.");
            echo json_encode(['status' => 'success', 'message' => 'Task marked as completed.']);
            break;

        case 'fetch_time_logs':
            $stmt = $pdo->prepare("SELECT t.*, u.full_name FROM time_logs t JOIN users u ON t.user_id=u.user_id ORDER BY t.log_id DESC LIMIT 20");
            $stmt->execute();
            $time_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $time_logs]);
            break;

        case 'fetch_my_tasks':
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE assigned_to=? ORDER BY status, due_date");
            $stmt->execute([$_SESSION['user']['user_id']]);
            $my_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $my_tasks]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
            break;
    }
} catch (Exception $e) {
    if (http_response_code() === 200) {
        http_response_code(500);
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
