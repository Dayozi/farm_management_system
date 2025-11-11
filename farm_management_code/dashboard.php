<?php
// We still need to require auth and header
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="row g-3">
  <div class="col-12">
    <div class="p-4 bg-white border rounded-3 shadow-sm">
      <h4 class="mb-1">Dashboard</h4>
      <p class="text-muted mb-0">Role: <strong><?= htmlspecialchars($user['role']) ?></strong></p>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Quick Links</h5>
        <div class="list-group">
          <a href="modules/animals.php" class="list-group-item list-group-item-action">Animals</a>
          <a href="modules/health_logs.php" class="list-group-item list-group-item-action">Health</a>
          <a href="modules/breeding.php" class="list-group-item list-group-item-action">Breeding</a>
          <a href="modules/inventory.php" class="list-group-item list-group-item-action">Inventory</a>
          <a href="modules/tasks.php" class="list-group-item list-group-item-action">Tasks</a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Notifications</h5>
        <div id="notif-area">
          <!-- Notifications will be loaded here by JavaScript -->
          <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// A function to fetch and display notifications
function fetchNotifications() {
  const notifArea = document.getElementById('notif-area');
  
  // Use a modern Fetch API to get data from our new API endpoint
  // We now specify the action as 'fetch'
  fetch('modules/notifications_api.php?action=fetch')
    .then(response => response.json())
    .then(result => {
      // Check for success status before processing the data
      if (result.status === 'success') {
        const data = result.data.notifications;
        // Clear the loading indicator
        notifArea.innerHTML = '';
        if (data.length > 0) {
          data.forEach(notif => {
            // Create a new div for each notification
            const notifDiv = document.createElement('div');
            // Check if the notification is read and apply a different style
            notifDiv.className = `alert ${notif.is_read == 1 ? 'alert-secondary' : 'alert-info'} py-2 mb-2`;
            notifDiv.innerHTML = `
              <strong>${notif.type}:</strong>
              ${notif.message}
              <span class="float-end small text-muted">${notif.created_at}</span>
            `;
            notifArea.appendChild(notifDiv);
          });
        } else {
          notifArea.innerHTML = '<p class="text-center text-muted mt-4">No new notifications.</p>';
        }
      } else {
        // Display an error if the API call was not successful
        notifArea.innerHTML = `<div class="alert alert-danger mt-4">Error: ${result.message}</div>`;
      }
    })
    .catch(error => {
      console.error('Error fetching notifications:', error);
      notifArea.innerHTML = '<div class="alert alert-danger mt-4">Could not load notifications.</div>';
    });
}

// Fetch notifications when the page loads
fetchNotifications();

// This is an optional feature to make it more dynamic.
// It will refresh the notifications every 30 seconds.
setInterval(fetchNotifications, 30000); 
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
