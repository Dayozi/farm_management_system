<?php
// require auth.php to handle user authentication and permissions
require_once __DIR__ . '/../includes/auth.php';
// require_role to make sure only an Admin can access this page
require_role(['Admin']);
// require the database connection
require_once __DIR__ . '/../config/db.php';
// include the header file
include __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-12">
        <div class="p-4 bg-white border rounded-3 shadow-sm d-flex justify-content-between align-items-center">
            <h4 class="mb-0">User Management</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" id="addUserBtn">
                <i class="fas fa-plus-circle me-2"></i>Add New User
            </button>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="usersTable">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <!-- User data will be loaded here by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="userForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Add/Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user-id">
                    <input type="hidden" name="action" id="action-type" value="create">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="full-name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full-name" name="full_name">
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="Admin">Admin</option>
                            <option value="Veterinarian">Veterinarian</option>
                            <option value="Manager">Manager</option>
                            <option value="Worker">Worker</option>
                            <option value="Accountant">Accountant</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="form-text text-muted" id="password-help">
                            Leave blank to keep the current password. Required for new users.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userForm = document.getElementById('userForm');
        const userModal = new bootstrap.Modal(document.getElementById('userModal'));
        const usersTableBody = document.getElementById('usersTableBody');
        const modalLabel = document.getElementById('userModalLabel');
        const submitBtn = document.getElementById('submitBtn');
        const passwordField = document.getElementById('password');
        const passwordHelp = document.getElementById('password-help');
        const userIdInput = document.getElementById('user-id');
        const actionTypeInput = document.getElementById('action-type');

        // Function to fetch and render users
        async function fetchUsers() {
            try {
                const response = await fetch('users_api.php?action=fetch');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const result = await response.json();
                if (result.status === 'success') {
                    renderUsers(result.data);
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Fetch error:', error);
                alert('An error occurred while fetching user data.');
            }
        }

        // Function to render the user table from fetched data
        function renderUsers(users) {
            usersTableBody.innerHTML = ''; // Clear existing rows
            if (users.length === 0) {
                usersTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No users found.</td></tr>';
                return;
            }

            users.forEach(user => {
                const row = document.createElement('tr');
                // Updated the buttons to use text instead of icons.
                row.innerHTML = `
                    <td>${user.username}</td>
                    <td>${user.full_name}</td>
                    <td>${user.role}</td>
                    <td>${user.email}</td>
                    <td>${user.phone}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-info text-white me-2 edit-btn" data-user-id="${user.user_id}">Edit</button>
                        <button class="btn btn-sm btn-danger delete-btn" data-user-id="${user.user_id}">Delete</button>
                    </td>
                `;
                usersTableBody.appendChild(row);
            });
        }

        // Handle form submission for both create and edit
        userForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const response = await fetch('users_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (response.ok && result.status === 'success') {
                    alert(result.message);
                    userModal.hide();
                    fetchUsers(); // Refresh table
                } else {
                    alert('Error: ' + (result.message || 'An unknown error occurred.'));
                }
            } catch (error) {
                console.error('Form submission error:', error);
                alert('An error occurred. Check the console for details.');
            }
        });

        // Event listener for the "Add New User" button
        document.getElementById('addUserBtn').addEventListener('click', function() {
            userForm.reset();
            modalLabel.textContent = 'Add New User';
            submitBtn.textContent = 'Save User';
            actionTypeInput.value = 'create';
            userIdInput.value = '';
            passwordField.required = true;
            passwordField.style.display = 'block';
            passwordHelp.textContent = 'Required for new users.';
        });

        // Event delegation for table buttons (Edit and Delete)
        usersTableBody.addEventListener('click', async function(e) {
            const editBtn = e.target.closest('.edit-btn');
            const deleteBtn = e.target.closest('.delete-btn');
            
            if (editBtn) {
                const userId = editBtn.dataset.userId;
                try {
                    const response = await fetch('users_api.php?action=fetch');
                    const result = await response.json();
                    if (result.status === 'success') {
                        const user = result.data.find(u => u.user_id == userId);
                        if (user) {
                            userForm.reset();
                            modalLabel.textContent = 'Edit User';
                            submitBtn.textContent = 'Update User';
                            actionTypeInput.value = 'edit';
                            userIdInput.value = user.user_id;
                            document.getElementById('username').value = user.username;
                            document.getElementById('full-name').value = user.full_name;
                            document.getElementById('role').value = user.role;
                            document.getElementById('email').value = user.email;
                            document.getElementById('phone').value = user.phone;
                            passwordField.required = false;
                            passwordField.style.display = 'block';
                            passwordHelp.textContent = 'Leave blank to keep the current password.';
                            userModal.show();
                        }
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                    alert('An error occurred while fetching user data for editing.');
                }
            } else if (deleteBtn) {
                const userId = deleteBtn.dataset.userId;
                if (confirm('Are you sure you want to delete this user?')) {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('user_id', userId);

                        const response = await fetch('users_api.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();
                        if (response.ok && result.status === 'success') {
                            alert(result.message);
                            fetchUsers(); // Refresh the table
                        } else {
                            alert('Error: ' + (result.message || 'An unknown error occurred.'));
                        }
                    } catch (error) {
                        console.error('Delete error:', error);
                        alert('An error occurred. Check the console for details.');
                    }
                }
            }
        });

        // Initial fetch of users on page load
        fetchUsers();
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
