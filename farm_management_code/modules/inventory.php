<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <h4 class="d-flex justify-content-between align-items-center">
                    Inventory
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#inventoryModal" id="addItemBtn">
                        <i class="fas fa-plus me-2"></i>Add New Item
                    </button>
                </h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="inventoryTable">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Min Threshold</th>
                                <th>Last Updated</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Inventory items will be loaded here via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding and editing inventory items -->
<div class="modal fade" id="inventoryModal" tabindex="-1" aria-labelledby="inventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inventoryModalLabel">Add New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="inventoryForm">
                    <input type="hidden" name="action" id="formAction">
                    <input type="hidden" name="item_id" id="itemId">
                    <div class="mb-3">
                        <label for="item_name" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="item_name" name="item_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option>Feed</option>
                            <option>Medicine</option>
                            <option>Equipment</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" step="0.01" class="form-control" id="quantity" name="quantity" required>
                    </div>
                    <div class="mb-3">
                        <label for="unit" class="form-label">Unit (e.g., kg, L, pcs)</label>
                        <input type="text" class="form-control" id="unit" name="unit" placeholder="kg, L, pcs" required>
                    </div>
                    <div class="mb-3">
                        <label for="min_threshold" class="form-label">Minimum Threshold</label>
                        <input type="number" step="0.01" class="form-control" id="min_threshold" name="min_threshold" value="0">
                    </div>
                    <button type="submit" class="btn btn-primary" id="formSubmitBtn">Save Item</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const inventoryTableBody = document.querySelector('#inventoryTable tbody');
        const inventoryModal = new bootstrap.Modal(document.getElementById('inventoryModal'));
        const inventoryForm = document.getElementById('inventoryForm');
        const formAction = document.getElementById('formAction');
        const modalLabel = document.getElementById('inventoryModalLabel');
        const itemIdInput = document.getElementById('itemId');
        const formSubmitBtn = document.getElementById('formSubmitBtn');

        // Function to fetch and display inventory items
        async function fetchInventory() {
            try {
                const response = await fetch('inventory_api.php?action=fetch');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const result = await response.json();

                if (result.status === 'success') {
                    displayInventory(result.data);
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Fetch error:', error);
                alert('An error occurred while fetching data. Check the console for details.');
            }
        }

        // Function to render the table rows
        function displayInventory(items) {
            inventoryTableBody.innerHTML = '';
            items.forEach(item => {
                const row = document.createElement('tr');
                const isLowStock = parseFloat(item.quantity) < parseFloat(item.min_threshold);
                if (isLowStock) {
                    row.classList.add('table-warning');
                }

                row.innerHTML = `
                    <td>${item.item_name}</td>
                    <td>${item.category}</td>
                    <td>${item.quantity}</td>
                    <td>${item.unit}</td>
                    <td>${item.min_threshold}</td>
                    <td>${new Date(item.last_updated).toLocaleString()}</td>
                    <td>${isLowStock ? '<span class="badge bg-danger">Low Stock</span>' : '<span class="badge bg-success">In Stock</span>'}</td>
                    <td>
                        <button class="btn btn-primary btn-sm edit-btn" data-id="${item.item_id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="${item.item_id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                inventoryTableBody.appendChild(row);
            });
        }

        // Event listener for the "Add New Item" button
        document.getElementById('addItemBtn').addEventListener('click', function() {
            modalLabel.textContent = 'Add New Item';
            formAction.value = 'create';
            inventoryForm.reset();
            itemIdInput.value = '';
            formSubmitBtn.textContent = 'Save Item';
        });

        // Event listener for Edit and Delete buttons on the table
        inventoryTableBody.addEventListener('click', function(e) {
            const id = e.target.closest('button').dataset.id;
            if (e.target.closest('.edit-btn')) {
                const item = findItemById(id);
                if (item) {
                    modalLabel.textContent = 'Edit Item';
                    formAction.value = 'edit';
                    itemIdInput.value = item.item_id;
                    document.getElementById('item_name').value = item.item_name;
                    document.getElementById('category').value = item.category;
                    document.getElementById('quantity').value = item.quantity;
                    document.getElementById('unit').value = item.unit;
                    document.getElementById('min_threshold').value = item.min_threshold;
                    formSubmitBtn.textContent = 'Update Item';
                    inventoryModal.show();
                }
            } else if (e.target.closest('.delete-btn')) {
                if (confirm('Are you sure you want to delete this item?')) {
                    deleteItem(id);
                }
            }
        });

        // Helper function to find an item by its ID (for editing)
        async function findItemById(id) {
            try {
                const response = await fetch('inventory_api.php?action=fetch');
                const result = await response.json();
                if (result.status === 'success') {
                    return result.data.find(item => item.item_id == id);
                }
            } catch (error) {
                console.error('Error finding item:', error);
            }
            return null;
        }

        // Form submission handler
        inventoryForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const response = await fetch('inventory_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.status === 'success') {
                    alert(result.message);
                    inventoryModal.hide();
                    fetchInventory(); // Reload data
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Form submission error:', error);
                alert('An error occurred. Check the console for details.');
            }
        });

        // Function to handle item deletion
        async function deleteItem(id) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('item_id', id);

            try {
                const response = await fetch('inventory_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.status === 'success') {
                    alert(result.message);
                    fetchInventory(); // Reload data
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert('An error occurred. Check the console for details.');
            }
        }

        // Initial data load
        fetchInventory();
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
