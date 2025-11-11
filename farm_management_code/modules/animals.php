<?php
// This is the main page for managing animals.

// We need to require auth.php for session and role checks.
require_once __DIR__ . '/../includes/auth.php';
// We need to require the database connection.
require_once __DIR__ . '/../config/db.php';

// Check if a user is logged in and has the required role
require_role(['Admin', 'Manager', 'Veterinarian']);

// --- DATABASE SETUP ---
// Check if the 'animals' table exists. If not, create it.
try {
    $sql = "SELECT 1 FROM `animals` LIMIT 1";
    $pdo->query($sql);
} catch (PDOException $e) {
    // Table does not exist, so we create it
    $sql = "CREATE TABLE `animals` (
        `animal_id` INT AUTO_INCREMENT PRIMARY KEY,
        `unique_tag` VARCHAR(50) UNIQUE NOT NULL,
        `species` VARCHAR(50),
        `breed` VARCHAR(50),
        `sex` ENUM('Male','Female'),
        `birth_date` DATE,
        `color` VARCHAR(50),
        `origin` VARCHAR(100),
        `sire_id` INT NULL,
        `dam_id` INT NULL,
        `photo_path` VARCHAR(255),
        `created_by` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sire_id) REFERENCES animals(animal_id) ON DELETE SET NULL,
        FOREIGN KEY (dam_id) REFERENCES animals(animal_id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    try {
        $pdo->exec($sql);
        // We can optionally log this, but we'll let the user know via the app's success message
    } catch (PDOException $e) {
        die("Failed to create database table: " . $e->getMessage());
    }
}

// Fetch all animals for the dropdown menus only, as the main table will be populated by JS
try {
    $stmt = $pdo->query("SELECT animal_id, unique_tag FROM animals ORDER BY unique_tag ASC");
    $all_animals_for_dropdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}

// Include the header file.
include __DIR__ . '/../includes/header.php';
?>

<!-- Link to print stylesheet -->
<link rel="stylesheet" href="/farm_management_code/assets/css/print.css" media="print">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Animals</h4>
    <div>
        <button class="btn btn-secondary me-2" onclick="window.print()">Print</button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEditModal" data-action="create">Add Animal</button>
    </div>
</div>

<!-- Filter Form -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="card-title">Track Animals</h5>
        <form id="filterForm" class="row g-3">
            <div class="col-md-3">
                <label for="filter_unique_tag" class="form-label">Unique Tag</label>
                <input type="text" class="form-control" id="filter_unique_tag" name="unique_tag">
            </div>
            <div class="col-md-3">
                <label for="filter_species" class="form-label">Species</label>
                <input type="text" class="form-control" id="filter_species" name="species">
            </div>
            <div class="col-md-3">
                <label for="filter_breed" class="form-label">Breed</label>
                <input type="text" class="form-control" id="filter_breed" name="breed">
            </div>
            <div class="col-md-3">
                <label for="filter_sex" class="form-label">Sex</label>
                <select id="filter_sex" name="sex" class="form-select">
                    <option value="">All</option>
                    <option>Male</option>
                    <option>Female</option>
                </select>
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary">Track</button>
                <button type="button" class="btn btn-secondary" id="resetFilterBtn">Reset</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tag</th>
                    <th>Species</th>
                    <th>Breed</th>
                    <th>Sex</th>
                    <th>DOB</th>
                    <th>Color</th>
                    <th>Origin</th>
                    <th>Photo</th>
                    <th class="text-center no-print">Actions</th>
                </tr>
            </thead>
            <!-- The tbody will be populated dynamically by JavaScript -->
            <tbody id="animals-table-body">
                <tr>
                    <td colspan="9" class="text-center text-muted">Loading animals...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addEditModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="animalForm" method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEditModalLabel">Add/Edit Animal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="action-type" value="create">
                    <input type="hidden" name="animal_id" id="animal-id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Unique Tag</label>
                            <input name="unique_tag" id="unique_tag" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Species</label>
                            <input name="species" id="species" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Breed</label>
                            <input name="breed" id="breed" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sex</label>
                            <select name="sex" id="sex" class="form-select">
                                <option value="">Select</option>
                                <option>Male</option>
                                <option>Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Birth Date</label>
                            <input type="date" name="birth_date" id="birth_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Color</label>
                            <input name="color" id="color" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Origin</label>
                            <input name="origin" id="origin" class="form-control">
                        </div>
                     <!--   <div class="col-md-6">
                            <label class="form-label">Sire</label>
                            <select name="sire_id" id="sire_id" class="form-select">
                                <option value="">Unknown</option>
                                <?php foreach ($all_animals_for_dropdown as $animal_option): ?>
                                    <option value="<?= htmlspecialchars($animal_option['animal_id']) ?>">
                                        <?= htmlspecialchars($animal_option['unique_tag']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dam</label>
                            <select name="dam_id" id="dam_id" class="form-select">
                                <option value="">Unknown</option>
                                <?php foreach ($all_animals_for_dropdown as $animal_option): ?>
                                    <option value="<?= htmlspecialchars($animal_option['animal_id']) ?>">
                                        <?= htmlspecialchars($animal_option['unique_tag']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>-->
                        <div class="col-md-12">
                            <label class="form-label">Photo</label>
                            <input type="file" name="photo" id="photo" class="form-control">
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
                Are you sure you want to delete this animal?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="post" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="animal_id" id="delete-animal-id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to fetch animals from the backend with optional filters
    const fetchAnimals = (filters = {}) => {
        const tableBody = document.getElementById('animals-table-body');
        tableBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Loading animals...</td></tr>';

        // Construct the URL with filter parameters
        const params = new URLSearchParams();
        for (const key in filters) {
            if (filters[key]) {
                params.append(key, filters[key]);
            }
        }
        const url = `/farm_management_code/modules/animals_api.php?${params.toString()}`;

        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                tableBody.innerHTML = ''; // Clear the loading message
                if (data.status === 'success' && data.data.length > 0) {
                    data.data.forEach(animal => {
                        const row = document.createElement('tr');
                        
                        // *** THIS IS THE FIX ***
                        // We construct the full, correct path to the image here.
                        const photoSrc = animal.photo_path ? `../uploads/animal_photos/${animal.photo_path}` : '';

                        row.innerHTML = `
                            <td>${animal.unique_tag}</td>
                            <td>${animal.species}</td>
                            <td>${animal.breed}</td>
                            <td>${animal.sex}</td>
                            <td>${animal.birth_date}</td>
                            <td>${animal.color}</td>
                            <td>${animal.origin}</td>
                            <td>
                                ${photoSrc ? `<img src="${photoSrc}" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">` : '-'}
                            </td>
                            <td class="text-center no-print">
                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#addEditModal" data-action="edit" data-animal='${JSON.stringify(animal)}'>Edit</button>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-animal-id="${animal.animal_id}">Delete</button>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No animals found.</td></tr>';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                tableBody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">An error occurred while fetching data.</td></tr>';
            });
    };

    // Event listener for the filter form submission
    document.getElementById('filterForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        const filters = Object.fromEntries(formData.entries());
        fetchAnimals(filters);
    });

    // Event listener for the "Reset" button to clear filters and refetch all animals
    document.getElementById('resetFilterBtn').addEventListener('click', function () {
        document.getElementById('filterForm').reset();
        fetchAnimals();
    });

    // Event listener for the "Add Animal" and "Edit" buttons
    document.getElementById('addEditModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const action = button.getAttribute('data-action');
        const modalTitle = this.querySelector('.modal-title');
        const form = this.querySelector('form');
        const actionInput = form.querySelector('#action-type');
        const animalIdInput = form.querySelector('#animal-id');

        if (action === 'create') {
            modalTitle.textContent = 'Add Animal';
            actionInput.value = 'create';
            animalIdInput.value = '';
            form.reset(); // Clear form fields
        } else if (action === 'edit') {
            modalTitle.textContent = 'Edit Animal';
            actionInput.value = 'edit';
            const animal = JSON.parse(button.getAttribute('data-animal'));
            animalIdInput.value = animal.animal_id;
            // Populate form fields with animal data
            document.getElementById('unique_tag').value = animal.unique_tag;
            document.getElementById('species').value = animal.species;
            document.getElementById('breed').value = animal.breed;
            document.getElementById('sex').value = animal.sex;
            document.getElementById('birth_date').value = animal.birth_date;
            document.getElementById('color').value = animal.color;
            document.getElementById('origin').value = animal.origin;

            // Set the correct option for sire and dam dropdowns
            document.getElementById('sire_id').value = animal.sire_id || '';
            document.getElementById('dam_id').value = animal.dam_id || '';
        }
    });

    // Event listener for the "Delete" button
    document.getElementById('deleteModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const animalId = button.getAttribute('data-animal-id');
        const form = this.querySelector('form');
        form.querySelector('#delete-animal-id').value = animalId;
    });

    // Handle AJAX form submissions
    document.getElementById('animalForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);

        fetch('/farm_management_code/modules/animals_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                // Instead of reloading the page, just re-fetch the data
                fetchAnimals();
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addEditModal'));
                modal.hide();
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

        fetch('/farm_management_code/modules/animals_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                // Instead of reloading the page, just re-fetch the data
                fetchAnimals();
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                modal.hide();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('An error occurred. See console for details.');
        });
    });

    // Call fetchAnimals on page load to populate the table initially
    window.addEventListener('load', fetchAnimals);
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
