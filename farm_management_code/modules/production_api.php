<?php
// This API endpoint handles all CRUD operations for the production module.
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'fetch_all':
            $stmt = $pdo->prepare("
                SELECT p.*, g.group_name, a.unique_tag
                FROM production_logs p
                LEFT JOIN groups g ON p.group_id = g.group_id
                LEFT JOIN animals a ON p.animal_id = a.animal_id
                ORDER BY p.production_date DESC, p.production_id DESC
            ");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'add':
            $group_id = (int)($_POST['group_id'] ?? 0);
            $animal_id = !empty($_POST['animal_id']) ? (int)$_POST['animal_id'] : null;
            $production_date = $_POST['production_date'] ?? null;
            $type = trim($_POST['type'] ?? '');
            $quantity = (float)($_POST['quantity'] ?? 0);

            if (empty($group_id) || empty($production_date) || empty($type) || $quantity <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Required fields are missing or invalid.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO production_logs (group_id, animal_id, production_date, type, quantity, recorded_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$group_id, $animal_id, $production_date, $type, $quantity, $_SESSION['user']['user_id']]);

            $group_name = '';
            if ($group_id) {
                $stmt_group = $pdo->prepare("SELECT group_name FROM groups WHERE group_id = ?");
                $stmt_group->execute([$group_id]);
                $group_name = $stmt_group->fetchColumn();
            }

            $animal_tag = '';
            if ($animal_id) {
                $stmt_animal = $pdo->prepare("SELECT unique_tag FROM animals WHERE animal_id = ?");
                $stmt_animal->execute([$animal_id]);
                $animal_tag = $stmt_animal->fetchColumn();
            }

            $log_message = "New production log added for " . ($animal_tag ? "animal '{$animal_tag}'" : "group '{$group_name}'") . ": {$quantity} of {$type}.";
            log_notification($pdo, $_SESSION['user']['user_id'], 'Production Tracking', $log_message);
            
            echo json_encode(['status' => 'success', 'message' => 'Production log added successfully.']);
            break;

        case 'edit':
            $production_id = (int)($_POST['production_id'] ?? 0);
            $group_id = (int)($_POST['group_id'] ?? 0);
            $animal_id = !empty($_POST['animal_id']) ? (int)$_POST['animal_id'] : null;
            $production_date = $_POST['production_date'] ?? null;
            $type = trim($_POST['type'] ?? '');
            $quantity = (float)($_POST['quantity'] ?? 0);

            if (empty($production_id) || empty($group_id) || empty($production_date) || empty($type) || $quantity <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Required fields are missing or invalid for edit.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE production_logs SET group_id=?, animal_id=?, production_date=?, type=?, quantity=? WHERE production_id=?");
            $stmt->execute([$group_id, $animal_id, $production_date, $type, $quantity, $production_id]);

            $group_name = '';
            if ($group_id) {
                $stmt_group = $pdo->prepare("SELECT group_name FROM groups WHERE group_id = ?");
                $stmt_group->execute([$group_id]);
                $group_name = $stmt_group->fetchColumn();
            }

            $animal_tag = '';
            if ($animal_id) {
                $stmt_animal = $pdo->prepare("SELECT unique_tag FROM animals WHERE animal_id = ?");
                $stmt_animal->execute([$animal_id]);
                $animal_tag = $stmt_animal->fetchColumn();
            }
            
            $log_message = "Production log #{$production_id} updated for " . ($animal_tag ? "animal '{$animal_tag}'" : "group '{$group_name}'") . ".";
            log_notification($pdo, $_SESSION['user']['user_id'], 'Production Tracking', $log_message);

            echo json_encode(['status' => 'success', 'message' => 'Production log updated successfully.']);
            break;

        case 'delete':
            $production_id = (int)($_POST['production_id'] ?? 0);

            if (empty($production_id)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Production ID is missing for deletion.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM production_logs WHERE production_id = ?");
            $stmt->execute([$production_id]);

            $log_message = "Production log #{$production_id} deleted.";
            log_notification($pdo, $_SESSION['user']['user_id'], 'Production Tracking', $log_message);

            echo json_encode(['status' => 'success', 'message' => 'Production log deleted successfully.']);
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
