<?php
// require auth.php to handle user authentication and permissions
require_once __DIR__ . '/../includes/auth.php';
require_login();
// require the database connection
require_once __DIR__ . '/../config/db.php';
// include the header file
include __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-12">
        <div class="p-4 bg-white border rounded-3 shadow-sm d-flex justify-content-between align-items-center">
            <h4 class="mb-0">My Notifications</h4>
            <button id="markAllReadBtn" class="btn btn-primary btn-sm">Mark All as Read</button>
        </div>
    </div>
</div>

<div class="list-group mt-3" id="notificationsList">
    <!-- Notifications will be loaded here dynamically -->
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const notificationsList = document.getElementById('notificationsList');
        const markAllReadBtn = document.getElementById('markAllReadBtn');

        // Function to fetch and render notifications
        async function fetchNotifications() {
            try {
                const response = await fetch('notifications_api.php?action=fetch');
                const result = await response.json();
                
                if (result.status === 'success') {
                    renderNotifications(result.data.notifications);
                } else {
                    console.error('Error fetching notifications:', result.message);
                }
            } catch (error) {
                console.error('Fetch error:', error);
            }
        }

        // Function to render the notifications list
        function renderNotifications(notifs) {
            notificationsList.innerHTML = ''; // Clear existing list
            if (notifs.length === 0) {
                notificationsList.innerHTML = '<div class="list-group-item text-muted text-center">No notifications found.</div>';
                return;
            }

            notifs.forEach(notif => {
                const item = document.createElement('div');
                item.className = 'list-group-item';
                // Add a class for unread notifications for styling
                if (notif.is_read == 0) {
                    item.classList.add('list-group-item-light');
                }
                item.dataset.id = notif.notification_id;

                item.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>${notif.type}</strong>
                        <span class="small text-muted">${notif.created_at}</span>
                    </div>
                    <div>${notif.message}</div>
                `;
                notificationsList.appendChild(item);
            });
        }

        // Handle the "Mark All as Read" button click
        markAllReadBtn.addEventListener('click', async function() {
            if (confirm("Are you sure you want to mark all notifications as read?")) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'mark_all_as_read');
                    const response = await fetch('notifications_api.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        alert(result.message);
                        fetchNotifications(); // Refresh the list
                    } else {
                        alert('Error marking notifications as read: ' + result.message);
                    }
                } catch (error) {
                    console.error('Mark all read error:', error);
                }
            }
        });

        // Initial fetch on page load
        fetchNotifications();
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
