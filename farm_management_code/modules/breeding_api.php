<?php
// Enable error reporting but don't display errors (log them instead)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set a specific error log file for debugging
ini_set('error_log', __DIR__ . '/../logs/breeding_api_errors.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// Log the request
error_log("=== NEW REQUEST ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . print_r($_POST, true));

// Set JSON header immediately
header('Content-Type: application/json');

// Simple debug endpoint
if (isset($_GET['debug_test'])) {
    echo json_encode([
        'status' => 'success', 
        'message' => 'Debug test successful',
        'post_data' => $_POST,
        'get_data' => $_GET
    ]);
    exit;
}

// Check if required files exist
$files = [
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../includes/auth.php',
    // Include the notification helper here
    __DIR__ . '/../includes/notification_helper.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        error_log("Missing file: " . $file);
        echo json_encode(['status' => 'error', 'message' => 'Missing required file: ' . basename($file)]);
        exit;
    }
}

// Now require your files
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification_helper.php';

// Check if a user is logged in and has the required role
try {
    require_role(['Admin', 'Manager']);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication failed: ' . $e->getMessage()]);
    exit;
}

// Check for a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

// Get the action and ID from the POST request
$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? null;

// Function to validate animal IDs
function validateAnimalId($pdo, $animalId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM animals WHERE animal_id = ?");
    $stmt->execute([$animalId]);
    return $stmt->fetchColumn() > 0;
}

try {
    switch ($action) {
        case 'create-breeding':
            // Validate required fields
            $animal_id = $_POST['animal_id'] ?? null;
            $breeding_date = $_POST['breeding_date'] ?? null;
            $sire_id = $_POST['sire_id'] ?? null;
            $method = $_POST['method'] ?? 'Natural';
            $notes = $_POST['notes'] ?? '';

            if (!$animal_id || !$breeding_date) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields: Female (Dam) and Breeding Date are required.']);
                exit;
            }

            // Check if the dam animal exists
            if (!validateAnimalId($pdo, $animal_id)) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => "The selected female (dam) does not exist."]);
                exit;
            }

            // Check if the sire animal exists, if one was provided
            if ($sire_id && !validateAnimalId($pdo, $sire_id)) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => "The selected male (sire) does not exist."]);
                exit;
            }

            // Insert into the database
            $stmt = $pdo->prepare("INSERT INTO breeding_logs (animal_id, sire_id, method, breeding_date, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$animal_id, $sire_id, $method, $breeding_date, $notes]);

            // Log a notification for the new breeding log
            log_notification($pdo, $_SESSION['user']['user_id'], 'Breeding Management', "New breeding log created for animal ID: {$animal_id}.");

            echo json_encode(['status' => 'success', 'message' => 'Breeding log added successfully!']);
            break;
            
        case 'edit-breeding':
            // Logic for updating a breeding log
            $animal_id = $_POST['animal_id'] ?? null;
            $breeding_date = $_POST['breeding_date'] ?? null;
            $sire_id = $_POST['sire_id'] ?? null;
            $method = $_POST['method'] ?? 'Natural';
            $notes = $_POST['notes'] ?? '';

            if (!$id || !$animal_id || !$breeding_date) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields for update.']);
                exit;
            }

            // Check if the dam animal exists
            if (!validateAnimalId($pdo, $animal_id)) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => "The selected female (dam) does not exist."]);
                exit;
            }

            // Check if the sire animal exists, if one was provided
            if ($sire_id && !validateAnimalId($pdo, $sire_id)) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => "The selected male (sire) does not exist."]);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE breeding_logs SET animal_id = ?, sire_id = ?, method = ?, breeding_date = ?, notes = ? WHERE breeding_id = ?");
            $stmt->execute([$animal_id, $sire_id, $method, $breeding_date, $notes, $id]);
            
            // Log a notification for the updated breeding log
            log_notification($pdo, $_SESSION['user']['user_id'], 'Breeding Management', "Breeding log updated for animal ID: {$animal_id}.");

            echo json_encode(['status' => 'success', 'message' => 'Breeding log updated successfully!']);
            break;

        case 'delete-breeding':
            // Logic for deleting a breeding log
            if (!$id) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing ID for deletion.']);
                exit;
            }

            // Fetch the animal ID before deleting the record
            $stmt = $pdo->prepare("SELECT animal_id FROM breeding_logs WHERE breeding_id = ?");
            $stmt->execute([$id]);
            $animal_id = $stmt->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM breeding_logs WHERE breeding_id = ?");
            $stmt->execute([$id]);

            // Log a notification for the deleted breeding log
            log_notification($pdo, $_SESSION['user']['user_id'], 'Breeding Management', "Breeding log deleted for animal ID: {$animal_id}.");

            echo json_encode(['status' => 'success', 'message' => 'Breeding log deleted successfully!']);
            break;

        case 'create-pregnancy':
            // Logic for creating a pregnancy record
            $animal_id = $_POST['animal_id'] ?? null;
            $confirmed_date = $_POST['confirmed_date'] ?? null;
            $due_date = $_POST['due_date'] ?? null;
            $notes = $_POST['notes'] ?? '';

            if (!$animal_id || !$confirmed_date) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields for pregnancy record.']);
                exit;
            }

            if (!validateAnimalId($pdo, $animal_id)) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => "The selected animal does not exist."]);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO pregnancy_tracker (animal_id, confirmed_date, due_date, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$animal_id, $confirmed_date, $due_date, $notes]);
            
            // Log a notification for the new pregnancy record
            log_notification($pdo, $_SESSION['user']['user_id'], 'Breeding Management', "New pregnancy record created for animal ID: {$animal_id}.");

            echo json_encode(['status' => 'success', 'message' => 'Pregnancy record added successfully!']);
            break;

        case 'edit-pregnancy':
            // Logic for updating a pregnancy record
            $animal_id = $_POST['animal_id'] ?? null;
            $confirmed_date = $_POST['confirmed_date'] ?? null;
            $due_date = $_POST['due_date'] ?? null;
            $notes = $_POST['notes'] ?? '';

            if (!$id || !$animal_id || !$confirmed_date) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields for update.']);
                exit;
            }

            if (!validateAnimalId($pdo, $animal_id)) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => "The selected animal does not exist."]);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE pregnancy_tracker SET animal_id = ?, confirmed_date = ?, due_date = ?, notes = ? WHERE pregnancy_id = ?");
            $stmt->execute([$animal_id, $confirmed_date, $due_date, $notes, $id]);

            // Log a notification for the updated pregnancy record
            log_notification($pdo, $_SESSION['user']['user_id'], 'Breeding Management', "Pregnancy record updated for animal ID: {$animal_id}.");

            echo json_encode(['status' => 'success', 'message' => 'Pregnancy record updated successfully!']);
            break;
            
        case 'delete-pregnancy':
            // Logic for deleting a pregnancy record
            if (!$id) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing ID for deletion.']);
                exit;
            }

            // Fetch the animal ID before deleting the record
            $stmt = $pdo->prepare("SELECT animal_id FROM pregnancy_tracker WHERE pregnancy_id = ?");
            $stmt->execute([$id]);
            $animal_id = $stmt->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM pregnancy_tracker WHERE pregnancy_id = ?");
            $stmt->execute([$id]);

            // Log a notification for the deleted pregnancy record
            log_notification($pdo, $_SESSION['user']['user_id'], 'Breeding Management', "Pregnancy record deleted for animal ID: {$animal_id}.");

            echo json_encode(['status' => 'success', 'message' => 'Pregnancy record deleted successfully!']);
            break;
        
        case 'create-birth':
            // Logic for creating a birth record
            $dam_id = $_POST['dam_id'] ?? null;
            $birth_date = $_POST['birth_date'] ?? null;
            $offspring_count = $_POST['offspring_count'] ?? 0;
            $survival_status = $_POST['survival_status'] ?? '';
            $notes = $_POST['notes'] ?? '';

            if (!$dam_id || !$birth_date) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields for birth record.']);
                exit;
            }
            
            if (!validateAnimalId($pdo, $dam_id)) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => "The selected dam does not exist."]);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO birth_records (dam_id, birth_date, offspring_count, survival_status, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$dam_id, $birth_date, $offspring_count, $survival_status, $notes]);
            
            // Log a notification for the new birth record
            log_notification($pdo, $_SESSION['user']['user_id'], 'Breeding Management', "New birth record created for dam ID: {$dam_id}.");

            echo json_encode(['status' => 'success', 'message' => 'Birth record added successfully!']);
            break;

        case 'edit-birth':
            // Logic for updating a birth record
            $dam_id = $_POST['dam_id'] ?? null;
            $birth_date = $_POST['birth_date'] ?? null;
            $offspring_count = $_POST['offspring_count'] ?? 0;
            $survival_status = $_POST['survival_status'] ?? '';
            $notes = $_POST['notes'] ?? '';

            if (!$id || !$dam_id || !$birth_date) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields for update.']);
                exit;
            }

            if (!validateAnimalId($pdo, $dam_id)) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => "The selected dam does not exist."]);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE birth_records SET dam_id = ?, birth_date = ?, offspring_count = ?, survival_status = ?, notes = ? WHERE birth_id = ?");
            $stmt->execute([$dam_id, $birth_date, $offspring_count, $survival_status, $notes, $id]);

            // Log a notification for the updated birth record
            log_notification($pdo, $_SESSION['user']['user_id'], 'Breeding Management', "Birth record updated for dam ID: {$dam_id}.");

            echo json_encode(['status' => 'success', 'message' => 'Birth record updated successfully!']);
            break;

        case 'delete-birth':
            // Logic for deleting a birth record
            if (!$id) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing ID for deletion.']);
                exit;
            }

            // Fetch the dam ID before deleting the record
            $stmt = $pdo->prepare("SELECT dam_id FROM birth_records WHERE birth_id = ?");
            $stmt->execute([$id]);
            $dam_id = $stmt->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM birth_records WHERE birth_id = ?");
            $stmt->execute([$id]);

            // Log a notification for the deleted birth record
            log_notification($pdo, $_SESSION['user']['user_id'], 'Breeding Management', "Birth record deleted for dam ID: {$dam_id}.");

            echo json_encode(['status' => 'success', 'message' => 'Birth record deleted successfully!']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    // Log the full error for debugging
    error_log("Database Error: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
    error_log("General Error: " . $e->getMessage());
}
?>
