<?php
// This is the main page for managing breeding, pregnancy, and birth records.

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Check if a user is logged in and has the required role
require_role(['Admin', 'Manager']);

// --- DATABASE TABLE SETUP ---
// Check and create the `breeding_logs` table
try {
    $pdo->query("SELECT 1 FROM `breeding_logs` LIMIT 1");
} catch (PDOException $e) {
    $sql = "CREATE TABLE `breeding_logs` (
        `breeding_id` INT AUTO_INCREMENT PRIMARY KEY,
        `animal_id` INT,
        `sire_id` INT NULL,
        `method` ENUM('Natural', 'AI') NOT NULL,
        `breeding_date` DATE NOT NULL,
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (animal_id) REFERENCES animals(animal_id) ON DELETE CASCADE,
        FOREIGN KEY (sire_id) REFERENCES animals(animal_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
}

// Check and create the `pregnancy_tracker` table
try {
    $pdo->query("SELECT 1 FROM `pregnancy_tracker` LIMIT 1");
} catch (PDOException $e) {
    $sql = "CREATE TABLE `pregnancy_tracker` (
        `pregnancy_id` INT AUTO_INCREMENT PRIMARY KEY,
        `animal_id` INT,
        `confirmed_date` DATE NOT NULL,
        `due_date` DATE NULL,
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (animal_id) REFERENCES animals(animal_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
}

// Check and create the `birth_records` table
try {
    $pdo->query("SELECT 1 FROM `birth_records` LIMIT 1");
} catch (PDOException $e) {
    $sql = "CREATE TABLE `birth_records` (
        `birth_id` INT AUTO_INCREMENT PRIMARY KEY,
        `dam_id` INT,
        `birth_date` DATE NOT NULL,
        `offspring_count` INT,
        `survival_status` VARCHAR(255),
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (dam_id) REFERENCES animals(animal_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
}


// Fetch all animals for the dropdowns, filtered by sex
try {
    $animals = $pdo->query("SELECT animal_id, unique_tag, sex FROM animals ORDER BY unique_tag ASC")->fetchAll(PDO::FETCH_ASSOC);
    $females = array_filter($animals, function($a) { return $a['sex'] === 'Female'; });
    $males = array_filter($animals, function($a) { return $a['sex'] === 'Male'; });
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}

// Fetch all breeding logs
try {
    $stmt = $pdo->query("SELECT bl.*, a.unique_tag as dam_tag, s.unique_tag as sire_tag FROM breeding_logs bl LEFT JOIN animals a ON bl.animal_id = a.animal_id LEFT JOIN animals s ON bl.sire_id = s.animal_id ORDER BY bl.breeding_date DESC, bl.breeding_id DESC");
    $breedingLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}

// Fetch all pregnancy records
try {
    $stmt = $pdo->query("SELECT pt.*, a.unique_tag as dam_tag FROM pregnancy_tracker pt JOIN animals a ON pt.animal_id = a.animal_id ORDER BY pt.confirmed_date DESC, pt.pregnancy_id DESC");
    $pregnancyRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}

// Fetch all birth records
try {
    $stmt = $pdo->query("SELECT br.*, a.unique_tag as dam_tag FROM birth_records br JOIN animals a ON br.dam_id = a.animal_id ORDER BY br.birth_date DESC, br.birth_id DESC");
    $birthRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Breeding & Reproduction</h4>
</div>

<ul class="nav nav-tabs" id="breedingTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="breeding-tab" data-bs-toggle="tab" data-bs-target="#breeding" type="button" role="tab">Breeding</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="pregnancy-tab" data-bs-toggle="tab" data-bs-target="#pregnancy" type="button" role="tab">Pregnancy</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="births-tab" data-bs-toggle="tab" data-bs-target="#births" type="button" role="tab">Births</button>
    </li>
</ul>
<div class="tab-content border border-top-0 p-3 bg-white shadow-sm rounded-bottom">
    <!-- Breeding Tab -->
    <div class="tab-pane fade show active" id="breeding" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Breeding Logs</h5>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#breedingModal" data-action="create-breeding">Add Breeding Log</button>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Dam</th>
                        <th>Sire</th>
                        <th>Method</th>
                        <th>Notes</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($breedingLogs)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No breeding logs found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($breedingLogs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['breeding_date']) ?></td>
                                <td><?= htmlspecialchars($log['dam_tag']) ?></td>
                                <td><?= htmlspecialchars($log['sire_tag'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($log['method']) ?></td>
                                <td><?= nl2br(htmlspecialchars($log['notes'])) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info text-white me-1" data-bs-toggle="modal" data-bs-target="#breedingModal" data-action="edit-breeding" data-log='<?= json_encode($log) ?>'>Edit</button>
                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-type="breeding" data-id="<?= $log['breeding_id'] ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pregnancy Tab -->
    <div class="tab-pane fade" id="pregnancy" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Pregnancy Records</h5>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#breedingModal" data-action="create-pregnancy">Add Pregnancy Record</button>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Dam</th>
                        <th>Confirmed Date</th>
                        <th>Due Date</th>
                        <th>Notes</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pregnancyRecords)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No pregnancy records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pregnancyRecords as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['dam_tag']) ?></td>
                                <td><?= htmlspecialchars($record['confirmed_date']) ?></td>
                                <td><?= htmlspecialchars($record['due_date'] ?? 'N/A') ?></td>
                                <td><?= nl2br(htmlspecialchars($record['notes'])) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info text-white me-1" data-bs-toggle="modal" data-bs-target="#breedingModal" data-action="edit-pregnancy" data-log='<?= json_encode($record) ?>'>Edit</button>
                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-type="pregnancy" data-id="<?= $record['pregnancy_id'] ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Births Tab -->
    <div class="tab-pane fade" id="births" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Birth Records</h5>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#breedingModal" data-action="create-birth">Add Birth Record</button>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Dam</th>
                        <th>Birth Date</th>
                        <th>Offspring Count</th>
                        <th>Survival Status</th>
                        <th>Notes</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($birthRecords)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No birth records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($birthRecords as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['dam_tag']) ?></td>
                                <td><?= htmlspecialchars($record['birth_date']) ?></td>
                                <td><?= htmlspecialchars($record['offspring_count']) ?></td>
                                <td><?= htmlspecialchars($record['survival_status']) ?></td>
                                <td><?= nl2br(htmlspecialchars($record['notes'])) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info text-white me-1" data-bs-toggle="modal" data-bs-target="#breedingModal" data-action="edit-birth" data-log='<?= json_encode($record) ?>'>Edit</button>
                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-type="birth" data-id="<?= $record['birth_id'] ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Main Breeding/Pregnancy/Birth Modal -->
<div class="modal fade" id="breedingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="breedingForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="breedingModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="breeding-action-type">
                    <input type="hidden" name="id" id="breeding-id">
                    
                    <div id="breeding-fields">
                        <div class="mb-3">
                            <label class="form-label">Female (Dam)</label>
                            <select name="animal_id" id="dam_id" class="form-select">
                                <option value="">Select female</option>
                                <?php foreach ($females as $a): ?>
                                    <option value="<?= $a['animal_id'] ?>"><?= htmlspecialchars($a['unique_tag']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sire (Male)</label>
                            <select name="sire_id" id="sire_id" class="form-select">
                                <option value="">Select male (optional)</option>
                                <?php foreach ($males as $a): ?>
                                    <option value="<?= $a['animal_id'] ?>"><?= htmlspecialchars($a['unique_tag']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Method</label>
                            <select name="method" id="method" class="form-select">
                                <option>Natural</option>
                                <option>AI</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Breeding Date</label>
                            <input type="date" name="breeding_date" id="breeding_date" class="form-control">
                        </div>
                    </div>
                    
                    <div id="pregnancy-fields">
                        <div class="mb-3">
                            <label class="form-label">Animal</label>
                            <select name="animal_id" id="pregnancy_animal_id" class="form-select">
                                <option value="">Select animal</option>
                                <?php foreach ($females as $a): ?>
                                    <option value="<?= $a['animal_id'] ?>"><?= htmlspecialchars($a['unique_tag']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirmed Date</label>
                            <input type="date" name="confirmed_date" id="confirmed_date" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" id="due_date" class="form-control">
                        </div>
                    </div>

                    <div id="birth-fields">
                        <div class="mb-3">
                            <label class="form-label">Dam</label>
                            <select name="dam_id" id="birth_dam_id" class="form-select">
                                <option value="">Select dam</option>
                                <?php foreach ($females as $a): ?>
                                    <option value="<?= $a['animal_id'] ?>"><?= htmlspecialchars($a['unique_tag']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Birth Date</label>
                            <input type="date" name="birth_date" id="birth_date" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Offspring Count</label>
                            <input type="number" name="offspring_count" id="offspring_count" class="form-control" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Survival Status</label>
                            <input type="text" name="survival_status" id="survival_status" class="form-control" placeholder="e.g. All survived, 1 died">
                        </div>
                    </div>

                    <div id="notes-field">
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
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

<!-- Universal Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this record?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="post" style="display:inline;">
                    <input type="hidden" name="action" id="delete-action">
                    <input type="hidden" name="id" id="delete-id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Universal Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="messageModalBody">
                <!-- Message will be inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to show the universal message modal
    function showMessage(title, body) {
        const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
        document.querySelector('#messageModal .modal-title').textContent = title;
        document.getElementById('messageModalBody').textContent = body;
        messageModal.show();
    }
    
    // ================= START: CORRECTED JAVASCRIPT =================
    document.getElementById('breedingModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const action = button.getAttribute('data-action');
        const modalTitle = this.querySelector('.modal-title');
        const form = this.querySelector('form');
        form.reset();
        
        const breedingFields = document.getElementById('breeding-fields');
        const pregnancyFields = document.getElementById('pregnancy-fields');
        const birthFields = document.getElementById('birth-fields');
        const notesField = document.getElementById('notes-field');

        // Hide all fieldsets and disable their inputs to prevent them from being submitted
        [breedingFields, pregnancyFields, birthFields].forEach(fieldset => {
            fieldset.style.display = 'none';
            fieldset.querySelectorAll('input, select, textarea').forEach(input => {
                input.disabled = true;
                input.removeAttribute('required');
            });
        });

        // Always show notes field and ensure its textarea is enabled
        notesField.style.display = 'block';
        notesField.querySelector('textarea').disabled = false;

        let targetFields; // This will hold the section we want to show and enable

        if (action === 'create-breeding' || action === 'edit-breeding') {
            targetFields = breedingFields;
            document.getElementById('dam_id').setAttribute('required', 'required');
            document.getElementById('breeding_date').setAttribute('required', 'required');
        } else if (action === 'create-pregnancy' || action === 'edit-pregnancy') {
            targetFields = pregnancyFields;
            document.getElementById('pregnancy_animal_id').setAttribute('required', 'required');
            document.getElementById('confirmed_date').setAttribute('required', 'required');
        } else if (action === 'create-birth' || action === 'edit-birth') {
            targetFields = birthFields;
            document.getElementById('birth_dam_id').setAttribute('required', 'required');
            document.getElementById('birth_date').setAttribute('required', 'required');
            document.getElementById('offspring_count').setAttribute('required', 'required');
        }

        // Show the target fieldset and enable its inputs
        if (targetFields) {
            targetFields.style.display = 'block';
            targetFields.querySelectorAll('input, select, textarea').forEach(input => {
                input.disabled = false;
            });
        }

        const actionInput = form.querySelector('#breeding-action-type');
        const idInput = form.querySelector('#breeding-id');

        modalTitle.textContent = button.textContent;
        actionInput.value = action;

        if (action.startsWith('edit')) {
            const log = JSON.parse(button.getAttribute('data-log'));
            idInput.value = log[action.split('-')[1] + '_id'];
            
            if (action.includes('breeding')) {
                document.getElementById('dam_id').value = log.animal_id;
                document.getElementById('sire_id').value = log.sire_id || '';
                document.getElementById('method').value = log.method;
                document.getElementById('breeding_date').value = log.breeding_date;
                document.getElementById('notes').value = log.notes || '';
            } else if (action.includes('pregnancy')) {
                document.getElementById('pregnancy_animal_id').value = log.animal_id;
                document.getElementById('confirmed_date').value = log.confirmed_date;
                document.getElementById('due_date').value = log.due_date;
                document.getElementById('notes').value = log.notes || '';
            } else if (action.includes('birth')) {
                document.getElementById('birth_dam_id').value = log.dam_id;
                document.getElementById('birth_date').value = log.birth_date;
                document.getElementById('offspring_count').value = log.offspring_count;
                document.getElementById('survival_status').value = log.survival_status;
                document.getElementById('notes').value = log.notes || '';
            }
        }
    });
    // ================= END: CORRECTED JAVASCRIPT =================
    
    document.getElementById('deleteModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const type = button.getAttribute('data-type');
        const id = button.getAttribute('data-id');

        const form = this.querySelector('form');
        form.querySelector('#delete-action').value = `delete-${type}`;
        form.querySelector('#delete-id').value = id;
    });

    // Handle all form submissions with AJAX
    document.getElementById('breedingForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        
        // DEBUG: Log all form data
        console.log('Form data being sent:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }

        fetch('breeding_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response URL:', response.url);
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Response text:', text);
                    throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Success:', data);
            if (data.status === 'success') {
                showMessage('Success', data.message);
                // Reload the page after a short delay to allow the user to read the message
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showMessage('Error', data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error details:', error);
            showMessage('Error', 'An error occurred: ' + error.message);
        });
    });

    document.getElementById('deleteForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);

        fetch('breeding_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Response text:', text);
                    throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Success:', data);
            if (data.status === 'success') {
                showMessage('Success', data.message);
                // Reload the page after a short delay
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showMessage('Error', data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error details:', error);
            showMessage('Error', 'An error occurred: ' + error.message);
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
