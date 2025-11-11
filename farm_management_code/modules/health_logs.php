<?php
// This is the main page for managing health logs.

// We need to require auth.php for session and role checks.
require_once __DIR__ . '/../includes/auth.php';
// We need to require the database connection.
require_once __DIR__ . '/../config/db.php';

// Check if a user is logged in and has the required role
require_role(['Admin', 'Manager', 'Veterinarian']);

// --- DATABASE SETUP ---
// Check if the 'health_logs' table exists. If not, create it.
try {
    $sql = "SELECT 1 FROM `health_logs` LIMIT 1";
    $pdo->query($sql);
} catch (PDOException $e) {
    // Table does not exist, so we create it
    $sql = "CREATE TABLE `health_logs` (
        `health_id` INT AUTO_INCREMENT PRIMARY KEY,
        `animal_id` INT,
        `log_date` DATE,
        `type` ENUM('Vaccination','Treatment','Illness','Deworming'),
        `description` TEXT,
        `medication` VARCHAR(100),
        `dosage` VARCHAR(50),
        `vet_notes` TEXT,
        `recorded_by` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (animal_id) REFERENCES animals(animal_id) ON DELETE CASCADE,
        FOREIGN KEY (recorded_by) REFERENCES users(user_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        die("Failed to create database table: " . $e->getMessage());
    }
}


// Fetch all health logs from the database, joining with animals to get the unique tag
try {
    $stmt = $pdo->query("SELECT h.*, a.unique_tag FROM health_logs h JOIN animals a ON h.animal_id = a.animal_id ORDER BY h.log_date DESC");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}

// Fetch all animals for the dropdown
try {
    $animals = $pdo->query("SELECT animal_id, unique_tag FROM animals ORDER BY unique_tag ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}

// Include the header file.
include __DIR__ . '/../includes/header.php';

?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Health & Veterinary</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#logModal" data-action="create">Add Health Log</button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Animal Tag</th>
                    <th>Type</th>
                    <th>Medication</th>
                    <th>Dosage</th>
                    <th>Description</th>
                    <th>Vet Notes</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No health logs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['log_date']) ?></td>
                            <td><?= htmlspecialchars($log['unique_tag']) ?></td>
                            <td><?= htmlspecialchars($log['type']) ?></td>
                            <td><?= htmlspecialchars($log['medication']) ?></td>
                            <td><?= htmlspecialchars($log['dosage']) ?></td>
                            <td><?= nl2br(htmlspecialchars($log['description'])) ?></td>
                            <td><?= nl2br(htmlspecialchars($log['vet_notes'])) ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info text-white me-1" data-bs-toggle="modal" data-bs-target="#logModal" data-action="edit" data-log='<?= json_encode($log) ?>'>Edit</button>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-log-id="<?= $log['health_id'] ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Log Modal -->
<div class="modal fade" id="logModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="logForm" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="logModalLabel">Add Health Log</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="action-type" value="create">
                    <input type="hidden" name="health_id" id="health-id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="log_date" id="log_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Animal</label>
                            <select name="animal_id" id="animal_id" class="form-select" required>
                                <option value="">Select animal</option>
                                <?php foreach ($animals as $a): ?>
                                    <option value="<?= $a['animal_id'] ?>"><?= htmlspecialchars($a['unique_tag']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type</label>
                            <select name="type" id="type" class="form-select" required>
                                <option>Vaccination</option>
                                <option>Treatment</option>
                                <option>Illness</option>
                                <option>Deworming</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Medication</label>
                            <input name="medication" id="medication" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dosage</label>
                            <input name="dosage" id="dosage" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Vet Notes</label>
                            <textarea name="vet_notes" id="vet_notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this health log?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="post" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="health_id" id="delete-health-id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Event listener for the Add/Edit modal
    document.getElementById('logModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const action = button.getAttribute('data-action');
        const modalTitle = this.querySelector('.modal-title');
        const form = this.querySelector('form');
        const actionInput = form.querySelector('#action-type');
        const logIdInput = form.querySelector('#health-id');

        if (action === 'create') {
            modalTitle.textContent = 'Add Health Log';
            actionInput.value = 'create';
            logIdInput.value = '';
            form.reset(); // Clear form fields
        } else if (action === 'edit') {
            modalTitle.textContent = 'Edit Health Log';
            actionInput.value = 'edit';
            const log = JSON.parse(button.getAttribute('data-log'));
            logIdInput.value = log.health_id;
            // Populate form fields with log data
            document.getElementById('log_date').value = log.log_date;
            document.getElementById('animal_id').value = log.animal_id;
            document.getElementById('type').value = log.type;
            document.getElementById('medication').value = log.medication;
            document.getElementById('dosage').value = log.dosage;
            document.getElementById('description').value = log.description;
            document.getElementById('vet_notes').value = log.vet_notes;
        }
    });

    // Event listener for the Delete modal
    document.getElementById('deleteModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const logId = button.getAttribute('data-log-id');
        const form = this.querySelector('form');
        form.querySelector('#delete-health-id').value = logId;
    });

    // Handle form submissions with AJAX
    document.getElementById('logForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);

        fetch('/farm_management_code/modules/health_logs_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.message || 'Network response was not ok');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('An error occurred. See console for details.');
        });
    });

    document.getElementById('deleteForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);

        fetch('/farm_management_code/modules/health_logs_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.message || 'Network response was not ok');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('An error occurred. See console for details.');
        });
    });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
