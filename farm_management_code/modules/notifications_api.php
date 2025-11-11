<?php
// This API endpoint handles all notification-related requests.
header('Content-Type: application/json');

// Include authentication and database connection
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user']['user_id'];
$response = ['status' => 'error', 'message' => 'Invalid action.'];

try {
    switch ($action) {
        case 'fetch':
            // Fetch the latest 50 notifications for the current user
            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$user_id]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch the unread count
            $unread_stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
            $unread_stmt->execute([$user_id]);
            $unread_count = $unread_stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];

            $response = [
                'status' => 'success',
                'data' => [
                    'notifications' => $notifications,
                    'unread_count' => $unread_count
                ]
            ];
            break;

        case 'mark_as_read':
            // Mark a specific notification as read
            $notification_id = $_POST['notification_id'] ?? null;
            if (!$notification_id) {
                http_response_code(400);
                throw new Exception('Notification ID is required.');
            }
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
            $stmt->execute([$notification_id, $user_id]);

            $response = ['status' => 'success', 'message' => 'Notification marked as read.'];
            break;

        case 'mark_all_as_read':
            // Mark all notifications for the current user as read
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $response = ['status' => 'success', 'message' => 'All notifications marked as read.'];
            break;

        default:
            http_response_code(400);
            $response = ['status' => 'error', 'message' => 'Action not found.'];
            break;
    }
} catch (Exception $e) {
    if (http_response_code() === 200) {
        http_response_code(500);
    }
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
