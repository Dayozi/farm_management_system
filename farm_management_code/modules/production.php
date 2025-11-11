<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/db.php';

// Data fetching is now handled by JavaScript via the API,
// but we still need to fetch dropdown data for initial page load.
$groups = $pdo->query("SELECT * FROM groups ORDER BY group_name")->fetchAll();
$animals = $pdo->query("SELECT animal_id, unique_tag FROM animals ORDER BY unique_tag")->fetchAll();
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

<h4>Production Tracking</h4>
<div id="alert-container"></div>
<div class="row g-4">
  <div class="col-md-5">
    <div class="card card-custom shadow-sm h-100">
      <div class="card-body">
        <h6 class="mb-3"><span id="form-title" class="text-success fw-bold">Add Production Log</span></h6>
        <form id="production-form">
          <input type="hidden" name="action" id="form-action" value="add">
          <input type="hidden" name="production_id" id="production-id">
          <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" id="type" class="form-select" required>
              <option value="Milk">Milk</option>
              <option value="Eggs">Eggs</option>
              <option value="Meat">Meat</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="production_date" id="production-date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Group</label>
            <select name="group_id" id="group-id" class="form-select" required>
              <option value="">Select group</option>
              <?php foreach ($groups as $g): ?>
                <option value="<?= $g['group_id'] ?>"><?= htmlspecialchars($g['group_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Animal (optional)</label>
            <select name="animal_id" id="animal-id" class="form-select">
              <option value="">Per group</option>
              <?php foreach ($animals as $a): ?>
                <option value="<?= $a['animal_id'] ?>"><?= htmlspecialchars($a['unique_tag']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Quantity</label>
            <input type="number" step="0.01" name="quantity" id="quantity" class="form-control" required>
          </div>
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
        <h6 class="mb-3"><span class="text-success fw-bold">Recent Production</span></h6>
        <div class="table-responsive table-responsive-custom">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Group</th>
                <th>Animal</th>
                <th>Qty</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="production-table-body">
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
    const form = document.getElementById('production-form');
    const alertContainer = document.getElementById('alert-container');
    const tableBody = document.getElementById('production-table-body');
    const formTitle = document.getElementById('form-title');
    const formAction = document.getElementById('form-action');
    const productionId = document.getElementById('production-id');
    const cancelEditBtn = document.getElementById('cancel-edit');
    const productionDateInput = document.getElementById('production-date');
    
    // Set today's date as default
    productionDateInput.valueAsDate = new Date();

    function showAlert(message, type = 'success') {
        alertContainer.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
    }

    function resetForm() {
        form.reset();
        formTitle.textContent = 'Add Production Log';
        formAction.value = 'add';
        productionId.value = '';
        cancelEditBtn.classList.add('d-none');
        productionDateInput.valueAsDate = new Date();
    }

    function renderTable(data) {
        tableBody.innerHTML = ''; // Clear existing rows
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No production logs found.</td></tr>';
            return;
        }
        data.forEach(log => {
            const row = document.createElement('tr');
            row.dataset.id = log.production_id;
            row.dataset.groupId = log.group_id;
            row.dataset.animalId = log.animal_id;
            row.dataset.date = log.production_date;
            row.dataset.type = log.type;
            row.dataset.quantity = log.quantity;
            row.innerHTML = `
                <td>${log.production_date}</td>
                <td>${log.type}</td>
                <td>${log.group_name || 'N/A'}</td>
                <td>${log.unique_tag || 'N/A'}</td>
                <td>${log.quantity}</td>
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
            const response = await fetch('production_api.php?action=fetch_all');
            if (!response.ok) {
                console.error('Network response was not ok. Status:', response.status);
                throw new Error('Failed to fetch data.');
            }
            const result = await response.json();
            if (result.status === 'success') {
                renderTable(result.data);
            } else {
                showAlert(result.message, 'danger');
                console.error('API Error:', result.message);
            }
        } catch (error) {
            showAlert('Failed to load production data. Please check the console for details.', 'danger');
            console.error('Fetch Error:', error);
        }
    }

    // Initial data fetch and render
    fetchData();

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);

        try {
            const response = await fetch('production_api.php', {
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
            animalId: row.dataset.animalId,
            date: row.dataset.date,
            type: row.dataset.type,
            quantity: row.dataset.quantity,
        };

        if (e.target.classList.contains('edit-btn')) {
            formTitle.textContent = 'Edit Production Log';
            formAction.value = 'edit';
            productionId.value = data.id;
            document.getElementById('group-id').value = data.groupId;
            document.getElementById('animal-id').value = data.animalId;
            document.getElementById('production-date').value = data.date;
            document.getElementById('type').value = data.type;
            document.getElementById('quantity').value = data.quantity;
            cancelEditBtn.classList.remove('d-none');
            window.scrollTo({ top: 0, behavior: 'smooth' });

        } else if (e.target.classList.contains('delete-btn')) {
            if (confirm('Are you sure you want to delete this production log?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('production_id', data.id);
                
                fetch('production_api.php', {
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
