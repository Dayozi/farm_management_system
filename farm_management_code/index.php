<?php
// We require auth first.
require_once __DIR__ . '/includes/auth.php';

// Check if a user is already logged in.
// If the user is logged in, redirect them to the dashboard immediately.
if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

// Generate a CSRF token for the form.
$csrf_token = generate_csrf_token();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate the CSRF token on form submission.
    if (!validate_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $u = trim($_POST['username'] ?? '');
        $p = trim($_POST['password'] ?? '');
        if (login($u, $p)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h3 class="mb-3 text-center">Sign in</h3>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
          <!-- Add the CSRF token as a hidden input field -->
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button class="btn btn-success w-100">Login</button>
      <!--    <p class="mt-3 small text-muted">
            Demo credentials: admin / password123
          </p>-->
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
