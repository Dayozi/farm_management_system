<?php
// This API endpoint handles all CRUD operations for user management.
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
// Include the notification helper to log actions
require_once __DIR__ . '/../includes/notification_helper.php';

// Only allow Admins to access this API.
require_role(['Admin']);

$action = $_REQUEST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid action specified.'];

try {
    switch ($action) {
        case 'fetch':
            // Fetch all users from the database.
            $stmt = $pdo->query("SELECT user_id, username, full_name, role, email, phone FROM users ORDER BY created_at DESC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['status' => 'success', 'data' => $users];
            break;

        case 'create':
            // Handle new user creation
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $fullName = trim($_POST['full_name'] ?? '');
            $role = trim($_POST['role'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            // Basic validation
            if (empty($username) || empty($password) || empty($role)) {
                http_response_code(400);
                throw new Exception('Username, password, and role are required.');
            }

            // Check if username already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                http_response_code(409); // Conflict
                throw new Exception('Username already exists. Please choose a different one.');
            }

            // Hash the password for security
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insert the new user into the database
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $passwordHash, $fullName, $role, $email, $phone]);

            // Log a notification for the new user creation
            log_notification($pdo, $_SESSION['user']['user_id'], 'User Management', "New user '{$username}' was created.");

            $response = ['status' => 'success', 'message' => 'User created successfully.'];
            break;

        case 'edit':
            // Handle user information update
            $userId = $_POST['user_id'] ?? '';
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $fullName = trim($_POST['full_name'] ?? '');
            $role = trim($_POST['role'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            // Basic validation
            if (empty($userId) || empty($username) || empty($role)) {
                http_response_code(400);
                throw new Exception('User ID, username, and role are required.');
            }

            // Start building the update query and parameters
            $sql = "UPDATE users SET username = ?, full_name = ?, role = ?, email = ?, phone = ?";
            $params = [$username, $fullName, $role, $email, $phone];
            
            // If a new password is provided, update the password hash
            if (!empty($password)) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ", password_hash = ?";
                $params[] = $passwordHash;
            }
            
            // Complete the query with the user ID condition
            $sql .= " WHERE user_id = ?";
            $params[] = $userId;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Log a notification for the user update
            log_notification($pdo, $_SESSION['user']['user_id'], 'User Management', "User '{$username}' was updated.");

            $response = ['status' => 'success', 'message' => 'User updated successfully.'];
            break;

        case 'delete':
            // Handle user deletion
            $userId = $_POST['user_id'] ?? '';
            if (empty($userId)) {
                http_response_code(400);
                throw new Exception('User ID is required for deletion.');
            }

            // Prevent an admin from deleting their own account.
            if (current_user()['user_id'] == $userId) {
                http_response_code(403); // Forbidden
                throw new Exception('You cannot delete your own account.');
            }

            // Fetch the username before deleting the user
            $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);
            $username = $user_to_delete['username'] ?? 'Unknown User';

            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Log a notification for the user deletion
            log_notification($pdo, $_SESSION['user']['user_id'], 'User Management', "User '{$username}' was deleted.");

            $response = ['status' => 'success', 'message' => 'User deleted successfully.'];
            break;
        
        default:
            http_response_code(400);
            $response = ['status' => 'error', 'message' => 'Invalid action.'];
            break;
    }
} catch (Exception $e) {
    // If an HTTP status code has not been set yet, use 500 for a general server error.
    if (http_response_code() === 200) {
        http_response_code(500);
    }
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);
