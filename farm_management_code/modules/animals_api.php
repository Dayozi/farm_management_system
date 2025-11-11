<?php
// This API handles all CRUD operations for the animals module, now including GET for filtering.
header('Content-Type: application/json');

// Include necessary files
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
// Include the notification helper to log actions
require_once __DIR__ . '/../includes/notification_helper.php';

// Check if a user is logged in and has the required role
require_role(['Admin', 'Manager']);

// Determine the request method and handle accordingly
$requestMethod = $_SERVER['REQUEST_METHOD'];

switch ($requestMethod) {
    case 'GET':
        handleGet($pdo);
        break;
    case 'POST':
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
        break;
    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
        break;
}

/**
 * Handles the retrieval and filtering of animal records.
 * This function now allows searching by unique_tag, species, breed, and more.
 * @param PDO $pdo The PDO database connection object.
 */
function handleGet($pdo) {
    // Start with a base SQL query that selects all animals
    $sql = "SELECT * FROM animals WHERE 1=1";
    $params = [];
    
    // Check for each potential filter parameter in the URL query string
    if (!empty($_GET['unique_tag'])) {
        $sql .= " AND unique_tag LIKE ?";
        $params[] = '%' . $_GET['unique_tag'] . '%';
    }
    if (!empty($_GET['species'])) {
        $sql .= " AND species LIKE ?";
        $params[] = '%' . $_GET['species'] . '%';
    }
    if (!empty($_GET['breed'])) {
        $sql .= " AND breed LIKE ?";
        $params[] = '%' . $_GET['breed'] . '%';
    }
    if (!empty($_GET['sex'])) {
        $sql .= " AND sex = ?";
        $params[] = $_GET['sex'];
    }
    if (!empty($_GET['color'])) {
        $sql .= " AND color LIKE ?";
        $params[] = '%' . $_GET['color'] . '%';
    }
    // Added a check for animal_id to allow direct lookup
    if (!empty($_GET['animal_id'])) {
        $sql .= " AND animal_id = ?";
        $params[] = $_GET['animal_id'];
    }
    if (!empty($_GET['sire_id'])) {
        $sql .= " AND sire_id = ?";
        $params[] = $_GET['sire_id'];
    }
    if (!empty($_GET['dam_id'])) {
        $sql .= " AND dam_id = ?";
        $params[] = $_GET['dam_id'];
    }

    try {
        // Prepare and execute the dynamic query with the collected parameters
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return the filtered results as a JSON array
        echo json_encode(['status' => 'success', 'data' => $animals]);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Database error: ' . $e->getMessage(), 0);
        echo json_encode(['status' => 'error', 'message' => 'An unexpected database error occurred.']);
    }
}

/**
 * Handles the creation of a new animal record.
 * @param PDO $pdo The PDO database connection object.
 */
function handleCreate($pdo) {
    // Validate required fields
    if (empty($_POST['unique_tag'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Unique tag is required.']);
        return;
    }

    $photo_path = null;
    if (!empty($_FILES['photo']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/animal_photos/';
        // Ensure upload directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . basename($_FILES['photo']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
            // *** THIS IS THE FIX ***
            // We now save only the filename to the database.
            $photo_path = $fileName;
        } else {
            // Handle file upload failure
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload photo. Check directory permissions.']);
            return;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO animals (unique_tag, species, breed, sex, birth_date, color, origin, sire_id, dam_id, photo_path, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            trim($_POST['unique_tag']),
            trim($_POST['species']) ?? null,
            trim($_POST['breed']) ?? null,
            $_POST['sex'] ?? null,
            $_POST['birth_date'] ?? null,
            trim($_POST['color']) ?? null,
            trim($_POST['origin']) ?? null,
            !empty($_POST['sire_id']) ? (int)$_POST['sire_id'] : null,
            !empty($_POST['dam_id']) ? (int)$_POST['dam_id'] : null,
            $photo_path,
            $_SESSION['user']['user_id']
        ]);

        log_notification($pdo, $_SESSION['user']['user_id'], 'Animal Management', "New animal with tag '{$_POST['unique_tag']}' was created.");

        echo json_encode(['status' => 'success', 'message' => 'Animal created successfully.']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            http_response_code(409); // Conflict
            echo json_encode(['status' => 'error', 'message' => 'The unique tag already exists.']);
        } else {
            http_response_code(500);
            error_log('Database error: ' . $e->getMessage(), 0);
            echo json_encode(['status' => 'error', 'message' => 'An unexpected database error occurred.']);
        }
    }
}

/**
 * Handles the editing of an existing animal record.
 * @param PDO $pdo The PDO database connection object.
 */
function handleEdit($pdo) {
    // Validate required fields
    if (empty($_POST['animal_id']) || empty($_POST['unique_tag'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Animal ID and unique tag are required.']);
        return;
    }

    $photo_path = null;
    // Check for new photo upload
    if (!empty($_FILES['photo']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/animal_photos/';
        $fileName = time() . '_' . basename($_FILES['photo']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
            // *** THIS IS THE FIX ***
            // We now save only the filename to the database.
            $photo_path = $fileName;
        } else {
            // Handle file upload failure
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload new photo. Check directory permissions.']);
            return;
        }
    }

    try {
        // Build the query and parameters dynamically
        $sql = "UPDATE animals SET unique_tag = ?, species = ?, breed = ?, sex = ?, birth_date = ?, color = ?, origin = ?, sire_id = ?, dam_id = ?";
        $params = [
            trim($_POST['unique_tag']),
            trim($_POST['species']) ?? null,
            trim($_POST['breed']) ?? null,
            $_POST['sex'] ?? null,
            $_POST['birth_date'] ?? null,
            trim($_POST['color']) ?? null,
            trim($_POST['origin']) ?? null,
            !empty($_POST['sire_id']) ? (int)$_POST['sire_id'] : null,
            !empty($_POST['dam_id']) ? (int)$_POST['dam_id'] : null
        ];

        if ($photo_path !== null) {
            $sql .= ", photo_path = ?";
            $params[] = $photo_path;
        }

        $sql .= " WHERE animal_id = ?";
        $params[] = $_POST['animal_id'];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        log_notification($pdo, $_SESSION['user']['user_id'], 'Animal Management', "Animal with tag '{$_POST['unique_tag']}' was updated.");

        echo json_encode(['status' => 'success', 'message' => 'Animal updated successfully.']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            http_response_code(409); // Conflict
            echo json_encode(['status' => 'error', 'message' => 'The unique tag already exists.']);
        } else {
            http_response_code(500);
            error_log('Database error: ' . $e->getMessage(), 0);
            echo json_encode(['status' => 'error', 'message' => 'An unexpected database error occurred.']);
        }
    }
}

/**
 * Handles the deletion of an animal record.
 * @param PDO $pdo The PDO database connection object.
 */
function handleDelete($pdo) {
    // Validate required fields
    if (empty($_POST['animal_id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Animal ID is required.']);
        return;
    }

    try {
        // Fetch the unique_tag and photo path before deleting
        $stmt = $pdo->prepare("SELECT unique_tag, photo_path FROM animals WHERE animal_id = ?");
        $stmt->execute([$_POST['animal_id']]);
        $animal = $stmt->fetch(PDO::FETCH_ASSOC);
        $unique_tag = $animal['unique_tag'] ?? 'Unknown Animal';

        if ($animal && !empty($animal['photo_path'])) {
            // Construct the full path to the file for deletion
            $photoFile = __DIR__ . '/../uploads/animal_photos/' . $animal['photo_path'];
            if (file_exists($photoFile)) {
                unlink($photoFile);
            }
        }

        // Now delete the database record
        $stmt = $pdo->prepare("DELETE FROM animals WHERE animal_id = ?");
        $stmt->execute([$_POST['animal_id']]);
        
        log_notification($pdo, $_SESSION['user']['user_id'], 'Animal Management', "Animal with tag '{$unique_tag}' was deleted.");

        echo json_encode(['status' => 'success', 'message' => 'Animal deleted successfully.']);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Database error: ' . $e->getMessage(), 0);
        echo json_encode(['status' => 'error', 'message' => 'An unexpected database error occurred.']);
    }
}
?>
