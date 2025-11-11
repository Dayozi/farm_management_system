<?php
// require auth.php to handle user authentication and permissions
require_once __DIR__ . '/../includes/auth.php';
// require_role to make sure only an Admin or Manager can access this page
require_role(['Admin', 'Manager']);
// require the database connection
require_once __DIR__ . '/../config/db.php';
// include the header file
include __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-12">
        <div class="p-4 bg-white border rounded-3 shadow-sm d-flex justify-content-between align-items-center">
            <h4 class="mb-0">File Upload</h4>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <div id="alert-container"></div>
                
                <form id="uploadForm" action="/farm_management_code/modules/upload_handler.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="fileToUpload" class="form-label">Select a file to upload:</label>
                        <input type="file" class="form-control" id="fileToUpload" name="file_to_upload" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Upload File</button>
                </form>

                <div class="mt-4" id="uploadStatus"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to show an alert message
    function showAlert(message, type) {
        const alertContainer = document.getElementById('alert-container');
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        alertContainer.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 5000); // Remove alert after 5 seconds
    }

    const uploadForm = document.getElementById('uploadForm');

    uploadForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent default form submission

        const formData = new FormData(uploadForm);
        const submitBtn = uploadForm.querySelector('button[type="submit"]');
        const uploadStatus = document.getElementById('uploadStatus');
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.textContent = 'Uploading...';
        uploadStatus.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';

        fetch(uploadForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(text);
                });
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new Error("Server Error: " + text.substring(0, 100) + "...");
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                showAlert(data.message, 'success');
                uploadStatus.innerHTML = `<p class="text-success">File path: ${data.filePath}</p>`;
                uploadForm.reset();
            } else {
                showAlert(data.message, 'danger');
                uploadStatus.innerHTML = '';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred during upload. Details: ' + error.message, 'danger');
            uploadStatus.innerHTML = '';
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Upload File';
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
