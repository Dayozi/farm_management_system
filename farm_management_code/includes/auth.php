<?php
// Start a new session or resume an existing one.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// Function to generate and validate a CSRF token.
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        // Generate a new token and store it in the session.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    // Check if the token exists and matches the one in the session.
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Ensure the user is logged in. If not, redirect to the login page.
function require_login() {
    if (!isset($_SESSION['user'])) {
        header('Location: /index.php');
        exit;
    }
}

// Check if the logged-in user has the required role.
function require_role($roles = []) {
    require_login();
    $userRole = $_SESSION['user']['role'] ?? '';
    if (!in_array($userRole, $roles)) {
        // Halt script execution and send a 403 Forbidden status.
        http_response_code(403);
        die('Forbidden: Insufficient permissions to access this page.');
    }
}

// Return the current logged-in user's data from the session.
function current_user() {
    return $_SESSION['user'] ?? null;
}

// Handle the user login process.
function login($username, $password) {
    global $pdo;
    
    // Prepare a secure query to prevent SQL injection.
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Verify the hashed password.
    if ($user && password_verify($password, $user['password_hash'])) {
        // Regenerate the session ID to prevent session hijacking.
        session_regenerate_id(true); 
        $_SESSION['user'] = $user;
        return true;
    }

    return false;
}

// Log out the current user by destroying the session.
function logout() {
    // Unset all session variables.
    $_SESSION = [];
    
    // Invalidate the session cookie.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finally, destroy the session.
    session_destroy();
}
?>
