<?php
// This helper function allows any module to easily log a new notification.
// It should be included where notifications need to be created.

function log_notification($pdo, $user_id, $type, $message) {
    try {
        // Prepare the SQL statement to prevent SQL injection.
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)");
        
        // Execute the statement with the provided values.
        $stmt->execute([$user_id, $type, $message]);
        
        // Return true on success.
        return true;
    } catch (PDOException $e) {
        // In case of an error, log it and return false.
        error_log("Notification logging failed: " . $e->getMessage());
        return false;
    }
}
?>
