<?php
// This API handles all CRUD operations and data fetching for the inventory module.
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Check if a user is logged in and has the required role
require_role(['Admin', 'Manager']);

// Get the action from the request
$action = $_REQUEST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid action specified.'];

try {
    switch ($action) {
        case 'fetch':
            // Fetch all inventory items
            $stmt = $pdo->query("SELECT * FROM inventory ORDER BY item_name");
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['status' => 'success', 'data' => $items];
            break;

        case 'create':
            // Validate required fields
            if (empty($_POST['item_name']) || empty($_POST['quantity']) || empty($_POST['unit'])) {
                http_response_code(400);
                $response = ['status' => 'error', 'message' => 'Required fields are missing.'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO inventory (item_name, category, quantity, unit, min_threshold) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    trim($_POST['item_name']),
                    $_POST['category'] ?? 'Other',
                    (float)$_POST['quantity'],
                    trim($_POST['unit']),
                    (float)($_POST['min_threshold'] ?? 0)
                ]);
                $response = ['status' => 'success', 'message' => 'Inventory item added successfully.'];
            }
            break;

        case 'edit':
            // Validate required fields
            if (empty($_POST['item_id']) || empty($_POST['item_name']) || empty($_POST['quantity']) || empty($_POST['unit'])) {
                http_response_code(400);
                $response = ['status' => 'error', 'message' => 'Required fields are missing.'];
            } else {
                $stmt = $pdo->prepare("UPDATE inventory SET item_name = ?, category = ?, quantity = ?, unit = ?, min_threshold = ? WHERE item_id = ?");
                $stmt->execute([
                    trim($_POST['item_name']),
                    $_POST['category'] ?? 'Other',
                    (float)$_POST['quantity'],
                    trim($_POST['unit']),
                    (float)($_POST['min_threshold'] ?? 0),
                    $_POST['item_id']
                ]);
                $response = ['status' => 'success', 'message' => 'Inventory item updated successfully.'];
            }
            break;

        case 'delete':
            // Validate required fields
            if (empty($_POST['item_id'])) {
                http_response_code(400);
                $response = ['status' => 'error', 'message' => 'Item ID is required.'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM inventory WHERE item_id = ?");
                $stmt->execute([$_POST['item_id']]);
                $response = ['status' => 'success', 'message' => 'Inventory item deleted successfully.'];
            }
            break;

        default:
            http_response_code(400);
            $response = ['status' => 'error', 'message' => 'Invalid action provided.'];
            break;
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    $response = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
}

echo json_encode($response);
