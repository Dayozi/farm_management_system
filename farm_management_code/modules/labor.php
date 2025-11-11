<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/db.php';

// We still fetch the user list for the task assignment dropdown
$users = $pdo->query("SELECT user_id, full_name, username, role FROM users ORDER BY full_name")->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
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
</style>

<h4>Labor & Workforce</h4>
<div id="alert-container"></div>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card card-custom shadow-sm h-100">
            <div class="card-body">
                <h6 class="mb-3"><span class="text-success fw-bold">Time Clock</span></h6>
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-custom" id="clock-in-btn">Clock In</button>
                    <button class="btn btn-secondary btn-custom" id="clock-out-btn">Clock Out</button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card card-custom shadow-sm h-100">
            <div class="card-body">
                <h6 class="mb-3"><span class="text-success fw-bold">Recent Time Logs</span></h6>
                <div class="table-responsive table-responsive-custom">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>In</th>
                                <th>Out</th>
                            </tr>
                        </thead>
                        <tbody id="time-logs-table-body">
                            <!-- Time logs will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<hr>
<div class="row g-4">
    <div class="col-md-6">
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
    <div class="col-md-6">
        <div class="card card-custom shadow-sm h-100">
            <div class="card-body">
                <h6 class="mb-3"><span class="text-success fw-bold">My Tasks</span></h6>
                <ul class="list-group list-group-flush" id="my-tasks-list">
                    <!-- Tasks will be populated by JavaScript -->
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const alertContainer = document.getElementById('alert-container');
    const timeLogsTableBody = document.getElementById('time-logs-table-body');
    const myTasksList = document.getElementById('my-tasks-list');
    const taskForm = document.getElementById('task-form');
    const taskFormTitle = document.getElementById('task-form-title');
    const taskFormAction = document.getElementById('task-form-action');
    const taskIdInput = document.getElementById('task-id');
    const cancelTaskEditBtn = document.getElementById('cancel-task-edit');
    
    function showAlert(message, type = 'success') {
        alertContainer.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
    }

    function resetTaskForm() {
        taskForm.reset();
        taskFormTitle.textContent = 'Create Task';
        taskFormAction.value = 'add_task';
        taskIdInput.value = '';
        cancelTaskEditBtn.classList.add('d-none');
    }

    // --- Time Clock Functions ---
    async function handleClockAction(action) {
        try {
            const formData = new FormData();
            formData.append('action', action);
            const response = await fetch('labor_api.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.status === 'success') {
                showAlert(result.message);
                fetchTimeLogs();
            } else {
                showAlert(result.message, 'danger');
            }
        } catch (error) {
            showAlert('An error occurred. Please try again.', 'danger');
            console.error('Error:', error);
        }
    }

    document.getElementById('clock-in-btn').addEventListener('click', () => handleClockAction('clock_in'));
    document.getElementById('clock-out-btn').addEventListener('click', () => handleClockAction('clock_out'));

    async function fetchTimeLogs() {
        try {
            const response = await fetch('labor_api.php?action=fetch_time_logs');
            const result = await response.json();
            if (result.status === 'success') {
                renderTimeLogs(result.data);
            } else {
                showAlert(result.message, 'danger');
                console.error('API Error:', result.message);
            }
        } catch (error) {
            showAlert('Failed to load time logs. Please check the console for details.', 'danger');
            console.error('Fetch Error:', error);
        }
    }

    function renderTimeLogs(logs) {
        timeLogsTableBody.innerHTML = '';
        if (logs.length === 0) {
            timeLogsTableBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No time logs found.</td></tr>';
            return;
        }
        logs.forEach(log => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${log.full_name}</td>
                <td>${log.login_time}</td>
                <td>${log.logout_time || 'N/A'}</td>
            `;
            timeLogsTableBody.appendChild(row);
        });
    }

    // --- Task Management Functions ---
    async function fetchMyTasks() {
        try {
            const response = await fetch('labor_api.php?action=fetch_my_tasks');
            const result = await response.json();
            if (result.status === 'success') {
                renderMyTasks(result.data);
            } else {
                showAlert(result.message, 'danger');
                console.error('API Error:', result.message);
            }
        } catch (error) {
            showAlert('Failed to load tasks. Please check the console for details.', 'danger');
            console.error('Fetch Error:', error);
        }
    }

    function renderMyTasks(tasks) {
        myTasksList.innerHTML = '';
        if (tasks.length === 0) {
            myTasksList.innerHTML = '<li class="list-group-item text-center text-muted">No tasks assigned to you.</li>';
            return;
        }
        tasks.forEach(task => {
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-custom d-flex justify-content-between align-items-center';
            li.dataset.taskId = task.task_id;
            li.dataset.title = task.title;
            li.dataset.description = task.description;
            li.dataset.assignedTo = task.assigned_to;
            li.dataset.dueDate = task.due_date;
            li.innerHTML = `
                <div>
                    <strong>${task.title}</strong>
                    <div class="small text-muted">${task.description}</div>
                    <div class="small">Due: ${task.due_date} | Status: ${task.status}</div>
                </div>
                <div class="d-flex gap-2">
                    ${task.status !== 'Completed' ? `
                        <button class="btn btn-outline-success btn-sm btn-custom complete-btn">Mark Done</button>
                        <button class="btn btn-outline-info btn-sm btn-custom edit-btn">Edit</button>
                        <button class="btn btn-outline-danger btn-sm btn-custom delete-btn">Delete</button>
                    ` : ''}
                </div>
            `;
            myTasksList.appendChild(li);
        });
    }

    taskForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(taskForm);
        try {
            const response = await fetch('labor_api.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.status === 'success') {
                showAlert(result.message);
                resetTaskForm();
                fetchMyTasks();
            } else {
                showAlert(result.message, 'danger');
            }
        } catch (error) {
            showAlert('An error occurred. Please try again.', 'danger');
            console.error('Error:', error);
        }
    });

    myTasksList.addEventListener('click', async (e) => {
        const li = e.target.closest('li');
        if (!li) return;
        
        const taskId = li.dataset.taskId;
        const action = e.target.classList.contains('complete-btn') ? 'complete_task' :
                       e.target.classList.contains('edit-btn') ? 'edit_task' :
                       e.target.classList.contains('delete-btn') ? 'delete_task' : '';

        if (!action) return;

        if (action === 'edit_task') {
            taskFormTitle.textContent = 'Edit Task';
            taskFormAction.value = 'edit_task';
            taskIdInput.value = li.dataset.taskId;
            document.getElementById('task-title').value = li.dataset.title;
            document.getElementById('task-description').value = li.dataset.description;
            document.getElementById('assigned-to').value = li.dataset.assignedTo;
            document.getElementById('task-due-date').value = li.dataset.dueDate;
            cancelTaskEditBtn.classList.remove('d-none');
            window.scrollTo({ top: document.getElementById('task-form').offsetTop, behavior: 'smooth' });
        } else if (action === 'delete_task') {
            if (confirm('Are you sure you want to delete this task?')) {
                const formData = new FormData();
                formData.append('action', 'delete_task');
                formData.append('task_id', taskId);
                
                try {
                    const response = await fetch('labor_api.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.status === 'success') {
                        showAlert(result.message);
                        fetchMyTasks();
                    } else {
                        showAlert(result.message, 'danger');
                    }
                } catch (error) {
                    showAlert('An error occurred during deletion.', 'danger');
                    console.error('Error:', error);
                }
            }
        } else if (action === 'complete_task') {
            const formData = new FormData();
            formData.append('action', 'complete_task');
            formData.append('task_id', taskId);

            try {
                const response = await fetch('labor_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.status === 'success') {
                    showAlert(result.message);
                    fetchMyTasks();
                } else {
                    showAlert(result.message, 'danger');
                }
            } catch (error) {
                showAlert('An error occurred while marking the task as complete.', 'danger');
                console.error('Error:', error);
            }
        }
    });

    cancelTaskEditBtn.addEventListener('click', resetTaskForm);

    // Initial data fetch and render
    fetchTimeLogs();
    fetchMyTasks();
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
