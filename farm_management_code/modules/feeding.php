<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/db.php';

// Data fetching is only for the groups dropdown now, for initial page load
$groups = $pdo->query("SELECT * FROM groups ORDER BY group_name")->fetchAll();
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
</style>

<h4>Feeding & Nutrition</h4>
<div id="alert-container"></div>
<div class="row g-4">
  <div class="col-md-5">
    <div class="card card-custom shadow-sm h-100">
      <div class="card-body">
        <h6 class="mb-3"><span id="form-title" class="text-success fw-bold">Add Feed Log</span></h6>
        <form id="feed-form">
          <input type="hidden" name="action" id="form-action" value="add">
          <input type="hidden" name="feed_id" id="feed-id">
          <div class="mb-3">
            <label class="form-label">Group</label>
            <select class="form-select" name="group_id" id="group-id" required>
              <option value="">Select group</option>
              <?php foreach ($groups as $g): ?>
                <option value="<?= $g['group_id'] ?>"><?= htmlspecialchars($g['group_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3"><label class="form-label">Date</label><input type="date" name="feed_date" id="feed-date" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Feed Type</label><input name="feed_type" id="feed-type" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Quantity (kg)</label><input type="number" step="0.01" name="quantity" id="quantity" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Cost</label><input type="number" step="0.01" name="cost" id="cost" class="form-control"></div>
          <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-secondary btn-custom d-none me-2" id="cancel-edit">Cancel</button>
            <button type="submit" class="btn btn-success btn-custom">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card card-custom shadow-sm h-100">
      <div class="card-body">
        <h6 class="mb-3"><span class="text-success fw-bold">Recent Feed Logs</span></h6>
        <div class="table-responsive table-responsive-custom">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th>Group</th>
                <th>Type</th>
                <th>Qty</th>
                <th>Cost</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="feeds-table-body">
              <!-- Content will be populated by JavaScript -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('feed-form');
    const alertContainer = document.getElementById('alert-container');
    const tableBody = document.getElementById('feeds-table-body');
    const formTitle = document.getElementById('form-title');
    const formAction = document.getElementById('form-action');
    const feedId = document.getElementById('feed-id');
    const cancelEditBtn = document.getElementById('cancel-edit');
    const feedDateInput = document.getElementById('feed-date');
    
    // Set today's date as default
    feedDateInput.valueAsDate = new Date();

    function showAlert(message, type = 'success') {
        alertContainer.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
    }

    function resetForm() {
        form.reset();
        formTitle.textContent = 'Add Feed Log';
        formAction.value = 'add';
        feedId.value = '';
        cancelEditBtn.classList.add('d-none');
        feedDateInput.valueAsDate = new Date(); // Reset to today's date
    }

    function renderTable(data) {
        tableBody.innerHTML = ''; // Clear existing rows
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No feed logs found.</td></tr>';
            return;
        }
        data.forEach(log => {
            const row = document.createElement('tr');
            row.dataset.id = log.feed_id;
            row.dataset.groupId = log.group_id;
            row.dataset.date = log.feed_date;
            row.dataset.type = log.feed_type;
            row.dataset.quantity = log.quantity;
            row.dataset.cost = log.cost;
            row.innerHTML = `
                <td>${log.feed_date}</td>
                <td>${log.group_name || 'N/A'}</td>
                <td>${log.feed_type}</td>
                <td>${log.quantity}</td>
                <td>${log.cost}</td>
                <td>
                    <button class="btn btn-sm btn-info btn-custom edit-btn">Edit</button>
                    <button class="btn btn-sm btn-danger btn-custom delete-btn">Delete</button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    }

    async function fetchData() {
        try {
            const response = await fetch('feeding_api.php?action=fetch_all');
            if (!response.ok) {
                // Log a more detailed error message to the console
                console.error('Network response was not ok. Status:', response.status);
                throw new Error('Failed to fetch data. Check the server path.');
            }
            const result = await response.json();
            if (result.status === 'success') {
                renderTable(result.data);
            } else {
                showAlert(result.message, 'danger');
                console.error('API Error:', result.message);
            }
        } catch (error) {
            showAlert('Failed to load data. Please check the console for more details.', 'danger');
            console.error('Fetch Error:', error);
        }
    }

    // Initial data fetch and render
    fetchData();

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);

        try {
            const response = await fetch('feeding_api.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.status === 'success') {
                showAlert(result.message);
                resetForm();
                fetchData(); // Fetch and re-render the table dynamically
            } else {
                showAlert(result.message, 'danger');
            }

        } catch (error) {
            showAlert('An error occurred. Please try again.', 'danger');
            console.error('Error:', error);
        }
    });

    tableBody.addEventListener('click', (e) => {
        const row = e.target.closest('tr');
        if (!row) return;

        const data = {
            id: row.dataset.id,
            groupId: row.dataset.groupId,
            date: row.dataset.date,
            type: row.dataset.type,
            quantity: row.dataset.quantity,
            cost: row.dataset.cost
        };

        if (e.target.classList.contains('edit-btn')) {
            formTitle.textContent = 'Edit Feed Log';
            formAction.value = 'edit';
            feedId.value = data.id;
            document.getElementById('group-id').value = data.groupId;
            document.getElementById('feed-date').value = data.date;
            document.getElementById('feed-type').value = data.type;
            document.getElementById('quantity').value = data.quantity;
            document.getElementById('cost').value = data.cost;
            cancelEditBtn.classList.remove('d-none');
            window.scrollTo({ top: 0, behavior: 'smooth' });

        } else if (e.target.classList.contains('delete-btn')) {
            if (confirm('Are you sure you want to delete this feed log?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('feed_id', data.id);
                
                fetch('feeding_api.php', {
                    method: 'POST',
                    body: formData
                }).then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        showAlert(result.message);
                        fetchData(); // Fetch and re-render the table dynamically
                    } else {
                        showAlert(result.message, 'danger');
                    }
                }).catch(error => {
                    showAlert('An error occurred during deletion.', 'danger');
                    console.error('Error:', error);
                });
            }
        }
    });

    cancelEditBtn.addEventListener('click', resetForm);
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
