<?php
// Main Tasks Dashboard Page
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/db.php';

// Fetch all users to populate the "Assigned To" dropdowns
$users = $pdo->query("SELECT user_id, full_name, username, role FROM users ORDER BY full_name")->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
    /* Custom styles for a modern, clean look */
    .card-custom {
        border-radius: 1rem;
        border: none;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .card-custom:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.1);
    }
    .btn-custom {
        border-radius: 50rem;
        font-weight: bold;
        transition: background-color 0.2s;
    }
    .btn-success-custom {
        background-color: #28a745;
        border-color: #28a745;
    }
    .btn-success-custom:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }
    .btn-secondary-custom {
        background-color: #6c757d;
        border-color: #6c757d;
    }
    .btn-secondary-custom:hover {
        background-color: #5a6268;
        border-color: #545b62;
    }
    .btn-info-custom {
        background-color: #17a2b8;
        border-color: #17a2b8;
    }
    .btn-info-custom:hover {
        background-color: #117a8b;
        border-color: #10707f;
    }
    .btn-danger-custom {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    .btn-danger-custom:hover {
        background-color: #c82333;
        border-color: #bd2130;
    }
    .table-responsive-custom {
        border-radius: 1rem;
        overflow: hidden;
    }
    .table-striped > tbody > tr:nth-of-type(odd) {
        background-color: #f8f9fa;
    }
    .table-striped > tbody > tr:hover {
        background-color: #e2e6ea;
        transition: background-color 0.2s ease-in-out;
    }
    .list-group-item-custom {
        border-radius: 1rem;
        margin-bottom: 0.5rem;
    }
    .list-group-item-custom:last-child {
        margin-bottom: 0;
    }
    .status-badge-completed {
        background-color: #28a745;
        color: white;
    }
    .status-badge-overdue {
        background-color: #dc3545;
        color: white;
    }
    .status-badge-inprogress {
        background-color: #ffc107;
        color: #212529;
    }
</style>

<h4>Task Management</h4>
<div id="alert-container"></div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card card-custom shadow-sm h-100">
            <div class="card-body">
                <h6 class="mb-3"><span id="task-form-title" class="text-success fw-bold">Create Task</span></h6>
                <form id="task-form">
                    <input type="hidden" name="action" id="task-form-action" value="add_task">
                    <input type="hidden" name="task_id" id="task-id">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input name="title" id="task-title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="task-description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign To</label>
                        <select name="assigned_to" id="assigned-to" class="form-select" required>
                            <option value="">Select user</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['full_name'] ?: $u['username']) ?> (<?= htmlspecialchars($u['role']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" id="task-due-date" class="form-control">
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary btn-custom d-none me-2" id="cancel-task-edit">Cancel</button>
                        <button type="submit" class="btn btn-success btn-custom">Save Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card card-custom shadow-sm h-100">
            <div class="card-body">
                <h6 class="mb-3"><span class="text-success fw-bold">All Tasks</span></h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label d-block">Filter by User</label>
                        <select id="user-filter" class="form-select">
                            <option value="">All Users</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['full_name'] ?: $u['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label d-block">Filter by Status</label>
                        <select id="status-filter" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                            <option value="Overdue">Overdue</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive table-responsive-custom">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Assigned To</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tasks-table-body">
                            <!-- Task rows will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const alertContainer = document.getElementById('alert-container');
    const tasksTableBody = document.getElementById('tasks-table-body');
    const userFilter = document.getElementById('user-filter');
    const statusFilter = document.getElementById('status-filter');
    const taskForm = document.getElementById('task-form');
    const taskFormTitle = document.getElementById('task-form-title');
    const taskFormAction = document.getElementById('task-form-action');
    const taskIdInput = document.getElementById('task-id');
    const taskTitleInput = document.getElementById('task-title');
    const taskDescriptionInput = document.getElementById('task-description');
    const assignedToSelect = document.getElementById('assigned-to');
    const taskDueDateInput = document.getElementById('task-due-date');
    const cancelTaskEditBtn = document.getElementById('cancel-task-edit');
    
    let allTasksData = [];

    // Displays a temporary alert message to the user
    function showAlert(message, type = 'success') {
        alertContainer.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
    }

    // Resets the task form to its default "Create Task" state
    function resetTaskForm() {
        taskForm.reset();
        taskFormTitle.textContent = 'Create Task';
        taskFormAction.value = 'add_task';
        taskIdInput.value = '';
        cancelTaskEditBtn.classList.add('d-none');
    }

    // Fetches all tasks from the API
    async function fetchAllTasks() {
        try {
            const response = await fetch('tasks_api.php?action=fetch_all');
            const result = await response.json();
            if (result.status === 'success') {
                allTasksData = result.data;
                renderTasks();
            } else {
                showAlert(result.message, 'danger');
                console.error('API Error:', result.message);
            }
        } catch (error) {
            showAlert('Failed to load tasks. Please check the console for details.', 'danger');
            console.error('Fetch Error:', error);
        }
    }

    // Renders the tasks based on current filters
    function renderTasks() {
        const selectedUser = userFilter.value;
        const selectedStatus = statusFilter.value;
        
        let filteredTasks = allTasksData;

        if (selectedUser) {
            filteredTasks = filteredTasks.filter(task => task.assigned_to == selectedUser);
        }
        if (selectedStatus) {
            filteredTasks = filteredTasks.filter(task => task.status === selectedStatus);
        }

        tasksTableBody.innerHTML = '';
        if (filteredTasks.length === 0) {
            tasksTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No tasks found.</td></tr>';
            return;
        }

        filteredTasks.forEach(task => {
            const row = document.createElement('tr');
            const statusClass = task.status === 'Completed' ? 'status-badge-completed' :
                                new Date(task.due_date) < new Date() && task.status !== 'Completed' ? 'status-badge-overdue' :
                                'status-badge-inprogress';
            
            row.innerHTML = `
                <td><strong>${task.title}</strong><div class="small text-muted">${task.description}</div></td>
                <td>${task.full_name}</td>
                <td>${task.due_date}</td>
                <td><span class="badge ${statusClass}">${task.status}</span></td>
                <td class="text-end">
                    <div class="d-flex gap-2 justify-content-end">
                        <button class="btn btn-outline-info btn-sm btn-custom edit-btn" data-id="${task.task_id}">Edit</button>
                        <button class="btn btn-outline-danger btn-sm btn-custom delete-btn" data-id="${task.task_id}">Delete</button>
                        ${task.status !== 'Completed' ? `<button class="btn btn-outline-success btn-sm btn-custom complete-btn" data-id="${task.task_id}">Mark Done</button>` : ''}
                    </div>
                </td>
            `;
            tasksTableBody.appendChild(row);
        });
    }

    // Event listeners for filters
    userFilter.addEventListener('change', renderTasks);
    statusFilter.addEventListener('change', renderTasks);

    // Handles form submission for adding or editing tasks
    taskForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(taskForm);
        try {
            const response = await fetch('tasks_api.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.status === 'success') {
                showAlert(result.message);
                resetTaskForm();
                fetchAllTasks(); // Refresh tasks after action
            } else {
                showAlert(result.message, 'danger');
            }
        } catch (error) {
            showAlert('An error occurred. Please try again.', 'danger');
            console.error('Error:', error);
        }
    });

    // Handles clicks on task buttons (edit, delete, complete)
    tasksTableBody.addEventListener('click', async (e) => {
        const targetBtn = e.target.closest('button');
        if (!targetBtn) return;
        
        const taskId = targetBtn.dataset.id;
        let action = '';

        if (targetBtn.classList.contains('edit-btn')) {
            action = 'edit';
        } else if (targetBtn.classList.contains('delete-btn')) {
            action = 'delete';
        } else if (targetBtn.classList.contains('complete-btn')) {
            action = 'complete';
        }

        if (action === 'edit') {
            const taskToEdit = allTasksData.find(task => task.task_id == taskId);
            if (taskToEdit) {
                taskFormTitle.textContent = 'Edit Task';
                taskFormAction.value = 'edit_task';
                taskIdInput.value = taskToEdit.task_id;
                taskTitleInput.value = taskToEdit.title;
                taskDescriptionInput.value = taskToEdit.description;
                assignedToSelect.value = taskToEdit.assigned_to;
                taskDueDateInput.value = taskToEdit.due_date;
                cancelTaskEditBtn.classList.remove('d-none');
                window.scrollTo({ top: taskForm.offsetTop, behavior: 'smooth' });
            }
        } else {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('task_id', taskId);
            
            try {
                const response = await fetch('tasks_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.status === 'success') {
                    showAlert(result.message);
                    fetchAllTasks(); // Refresh tasks after action
                } else {
                    showAlert(result.message, 'danger');
                }
            } catch (error) {
                showAlert('An error occurred.', 'danger');
                console.error('Error:', error);
            }
        }
    });

    cancelTaskEditBtn.addEventListener('click', resetTaskForm);

    // Initial data fetch and render on page load
    fetchAllTasks();
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
