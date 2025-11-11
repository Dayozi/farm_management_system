<?php
// This API handles all CRUD operations for the feeding module.
header('Content-Type: application/json');

// Include necessary files
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/notification_helper.php';

// Get the action from the POST data or GET parameter
$action = $_REQUEST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid action specified.'];

try {
    switch ($action) {
        case 'add':
            handleAdd($pdo);
            break;
        case 'edit':
            handleEdit($pdo);
            break;
        case 'delete':
            handleDelete($pdo);
            break;
        case 'fetch_all':
            handleFetchAll($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Handles the creation of a new feed log record.
 * @param PDO $pdo The PDO database connection object.
 */
function handleAdd($pdo) {
    $group_id = (int)$_POST['group_id'] ?? null;
    $feed_date = $_POST['feed_date'] ?? null;
    $feed_type = trim($_POST['feed_type'] ?? '');
    $quantity = (float)$_POST['quantity'] ?? 0;
    $cost = (float)$_POST['cost'] ?? 0;

    if (empty($group_id) || empty($feed_date) || empty($feed_type) || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Required fields (Group, Date, Feed Type, Quantity) are missing or invalid.']);
        exit;
    }

    $stmt_group = $pdo->prepare("SELECT group_name FROM groups WHERE group_id = ?");
    $stmt_group->execute([$group_id]);
    $group_name = $stmt_group->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO feed_logs (group_id, feed_date, feed_type, quantity, cost, recorded_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$group_id, $feed_date, $feed_type, $quantity, $cost, $_SESSION['user']['user_id']]);

    $message = "New feed log added for group '{$group_name}': {$quantity}kg of {$feed_type}.";
    log_notification($pdo, $_SESSION['user']['user_id'], 'Feeding & Nutrition', $message);
    
    echo json_encode(['status' => 'success', 'message' => 'Feed log added successfully.']);
}

/**
 * Handles the editing of an existing feed log record.
 * @param PDO $pdo The PDO database connection object.
 */
function handleEdit($pdo) {
    $feed_id = (int)$_POST['feed_id'] ?? null;
    $group_id = (int)$_POST['group_id'] ?? null;
    $feed_date = $_POST['feed_date'] ?? null;
    $feed_type = trim($_POST['feed_type'] ?? '');
    $quantity = (float)$_POST['quantity'] ?? 0;
    $cost = (float)$_POST['cost'] ?? 0;

    if (empty($feed_id) || empty($group_id) || empty($feed_date) || empty($feed_type) || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Required fields for edit are missing or invalid.']);
        exit;
    }

    $stmt_group = $pdo->prepare("SELECT group_name FROM groups WHERE group_id = ?");
    $stmt_group->execute([$group_id]);
    $group_name = $stmt_group->fetchColumn();

    $stmt = $pdo->prepare("UPDATE feed_logs SET group_id = ?, feed_date = ?, feed_type = ?, quantity = ?, cost = ? WHERE feed_id = ?");
    $stmt->execute([$group_id, $feed_date, $feed_type, $quantity, $cost, $feed_id]);

    $message = "Feed log updated for group '{$group_name}': {$quantity}kg of {$feed_type}.";
    log_notification($pdo, $_SESSION['user']['user_id'], 'Feeding & Nutrition', $message);
    
    echo json_encode(['status' => 'success', 'message' => 'Feed log updated successfully.']);
}

/**
 * Handles the deletion of a feed log record.
 * @param PDO $pdo The PDO database connection object.
 */
function handleDelete($pdo) {
    $feed_id = (int)$_POST['feed_id'] ?? null;

    if (empty($feed_id)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Feed ID is required for deletion.']);
        exit;
    }
    
    // Fetch details before deleting for the notification log
    $stmt_fetch = $pdo->prepare("SELECT f.quantity, f.feed_type, g.group_name FROM feed_logs f JOIN groups g ON f.group_id = g.group_id WHERE f.feed_id = ?");
    $stmt_fetch->execute([$feed_id]);
    $deleted_log = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("DELETE FROM feed_logs WHERE feed_id = ?");
    $stmt->execute([$feed_id]);

    if ($deleted_log) {
        $message = "Feed log deleted for group '{$deleted_log['group_name']}': {$deleted_log['quantity']}kg of {$deleted_log['feed_type']}.";
        log_notification($pdo, $_SESSION['user']['user_id'], 'Feeding & Nutrition', $message);
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Feed log deleted successfully.']);
}

/**
 * Fetches all feed log records for the table.
 * @param PDO $pdo The PDO database connection object.
 */
function handleFetchAll($pdo) {
    $stmt = $pdo->query("SELECT f.*, g.group_name FROM feed_logs f LEFT JOIN groups g ON f.group_id=g.group_id ORDER BY feed_date DESC, feed_id DESC");
    $feeds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $feeds]);
}
