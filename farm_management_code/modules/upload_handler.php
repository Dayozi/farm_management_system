<?php
// This API endpoint handles all file uploads.

// We need to require auth.php for session and role checks.
require_once __DIR__ . '/../includes/auth.php';
// We also need the database connection.
require_once __DIR__ . '/../config/db.php';

// Only allow Admins and Managers to upload files.
require_role(['Admin', 'Manager']);

// Set content type to JSON.
header('Content-Type: application/json');

// Check if the request method is POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

// Check if a file was uploaded.
if (empty($_FILES['file_to_upload'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No file was uploaded.']);
    exit;
}

$file = $_FILES['file_to_upload'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File upload failed with error code: ' . $file['error']]);
    exit;
}

// Get the file extension
$fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileExt = strtolower($fileExt);

// Generate a unique file name to prevent overwriting existing files
$uniqueFileName = uniqid() . '.' . $fileExt;
$destinationPath = __DIR__ . '/../uploads/' . $uniqueFileName;

// Ensure the directory is writable.
if (!is_writable(__DIR__ . '/../uploads')) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Upload directory is not writable.']);
    exit;
}

// Move the file from the temporary location to the final destination
if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
    // If the file was moved successfully, return the new file path
    echo json_encode(['status' => 'success', 'message' => 'File uploaded successfully.', 'filePath' => '/uploads/' . $uniqueFileName]);
} else {
    // If there was an error moving the file
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to move the uploaded file.']);
}

?>
