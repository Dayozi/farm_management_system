<?php
// Ensure session is started for all pages. auth.php already handles this,
// so this line is technically redundant if auth.php is always required.
// We'll keep it as a safeguard.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Livestock Tracking system</title>
  <link rel="icon" type="image/x-icon" href="/farm_management_code/assets/icons/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/farm_management_code/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/farm_management_code/dashboard.php">Livestock Tracking System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExample" aria-controls="navbarsExample" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarsExample">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if(isset($_SESSION['user'])): ?>
          <li class="nav-item"><a class="nav-link" href="/farm_management_code/modules/animals.php">Animals</a></li>
          <li class="nav-item"><a class="nav-link" href="/farm_management_code/modules/health_logs.php">Health</a></li>
          <li class="nav-item"><a class="nav-link" href="/farm_management_code/modules/breeding.php">Breeding</a></li>
          <li class="nav-item"><a class="nav-link" href="/farm_management_code/modules/feeding.php">Feeding</a></li>
          <li class="nav-item"><a class="nav-link" href="/farm_management_code/modules/production.php">Production</a></li>
          <li class="nav-item"><a class="nav-link" href="/farm_management_code/modules/inventory.php">Inventory</a></li>
          <li class="nav-item"><a class="nav-link" href="/farm_management_code/modules/finance.php">Finance</a></li>
          <li class="nav-item"><a class="nav-link" href="/farm_management_code/modules/labor.php">Labor</a></li>
          <li class="nav-item"><a class="nav-link" href="/farm_management_code/modules/reports.php">Reports</a></li>
<li class="nav-item"><a class="nav-link" href="/farm_management_code/modules/users.php">Users</a></li>
          <li class="nav-item"><a class="nav-link" href="/farm_management_code/modules/notifications.php">Notifications</a></li>
        <?php endif; ?>
      </ul>
      <span class="navbar-text me-3">
        <?php if(isset($_SESSION['user'])): ?>
          Hi, <strong><?= htmlspecialchars($_SESSION['user']['full_name'] ?? $_SESSION['user']['username']) ?></strong>
        <?php endif; ?>
      </span>
      <div>
        <?php if(isset($_SESSION['user'])): ?>
            <a href="/farm_management_code/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<div class="container my-4">
