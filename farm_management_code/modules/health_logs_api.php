<?php
// This API handles all CRUD operations for the health logs module.
header('Content-Type: application/json');

// Include necessary files
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
// Include the notification helper to log actions
require_once __DIR__ . '/../includes/notification_helper.php';

// Check if a user is logged in and has the required role
require_role(['Admin', 'Manager', 'Veterinarian']);

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

// Get the action from the POST data
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        handleCreate($pdo);
        break;
    case 'edit':
        handleEdit($pdo);
        break;
    case 'delete':
        handleDelete($pdo);
        break;
    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
        break;
}

/**
 * Handles the creation of a new health log record.
 * @param PDO $pdo The PDO database connection object.
 */
function handleCreate($pdo) {
    // Validate required fields
    if (empty($_POST['animal_id']) || empty($_POST['log_date']) || empty($_POST['type'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Required fields (Animal, Date, Type) are missing.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO health_logs (animal_id, log_date, type, description, medication, dosage, vet_notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            (int)$_POST['animal_id'],
            $_POST['log_date'],
            $_POST['type'],
            trim($_POST['description'] ?? ''),
            trim($_POST['medication'] ?? ''),
            trim($_POST['dosage'] ?? ''),
            trim($_POST['vet_notes'] ?? ''),
            $_SESSION['user']['user_id']
        ]);

        // Log a notification for the new health log
        log_notification($pdo, $_SESSION['user']['user_id'], 'Health Management', "New health log created for animal ID: {$_POST['animal_id']}.");

        echo json_encode(['status' => 'success', 'message' => 'Health log created successfully.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handles the editing of an existing health log record.
 * @param PDO $pdo The PDO database connection object.
 */
function handleEdit($pdo) {
    // Validate required fields
    if (empty($_POST['health_id']) || empty($_POST['animal_id']) || empty($_POST['log_date']) || empty($_POST['type'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Required fields are missing.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE health_logs SET animal_id = ?, log_date = ?, type = ?, description = ?, medication = ?, dosage = ?, vet_notes = ? WHERE health_id = ?");
        $stmt->execute([
            (int)$_POST['animal_id'],
            $_POST['log_date'],
            $_POST['type'],
            trim($_POST['description'] ?? ''),
            trim($_POST['medication'] ?? ''),
            trim($_POST['dosage'] ?? ''),
            trim($_POST['vet_notes'] ?? ''),
            (int)$_POST['health_id']
        ]);

        // Log a notification for the updated health log
        log_notification($pdo, $_SESSION['user']['user_id'], 'Health Management', "Health log updated for animal ID: {$_POST['animal_id']}.");

        echo json_encode(['status' => 'success', 'message' => 'Health log updated successfully.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handles the deletion of a health log record.
 * @param PDO $pdo The PDO database connection object.
 */
function handleDelete($pdo) {
    // Validate required fields
    if (empty($_POST['health_id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Health ID is required.']);
        return;
    }

    try {
        // Fetch the animal ID before deleting the record
        $stmt = $pdo->prepare("SELECT animal_id FROM health_logs WHERE health_id = ?");
        $stmt->execute([$_POST['health_id']]);
        $animal_id = $stmt->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM health_logs WHERE health_id = ?");
        $stmt->execute([$_POST['health_id']]);

        // Log a notification for the deleted health log
        log_notification($pdo, $_SESSION['user']['user_id'], 'Health Management', "Health log deleted for animal ID: {$animal_id}.");

        echo json_encode(['status' => 'success', 'message' => 'Health log deleted successfully.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' . $e->getMessage()]);
    }
}
?>
