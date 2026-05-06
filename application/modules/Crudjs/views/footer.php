    </div> <!-- End container -->
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> CRUD with AJAX - Built with CodeIgniter 3</p>
        </div>
    </footer>

    <!-- Create/Edit Modal -->
    <div id="formModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Create New Record</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p>Processing...</p>
                </div>
                <form id="crudForm" enctype="multipart/form-data">
                    <input type="hidden" id="recordId" name="recordId">
                    
                    <div class="form-group">
                        <label for="title">Title <span style="color: red;">*</span></label>
                        <input type="text" 
                               name="title" 
                               id="title" 
                               placeholder="Enter title" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="author">Author <span style="color: red;">*</span></label>
                        <input type="text" 
                               name="author" 
                               id="author" 
                               placeholder="Enter author name" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="article">Article <span style="color: red;">*</span></label>
                        <textarea name="article" 
                                  id="article" 
                                  placeholder="Write your content here..." 
                                  required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Image</label>
                        <div id="currentImageContainer" style="display: none; margin-bottom: 10px;">
                            <p style="margin-bottom: 10px; font-weight: 500;">Current Image:</p>
                            <img id="currentImage" 
                                 alt="Current image" 
                                 style="max-width: 200px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        </div>
                        <input type="file" 
                               name="image" 
                               id="image" 
                               accept="image/jpeg,image/jpg,image/png">
                        <small>Allowed formats: JPG, JPEG, PNG. Maximum size: 2MB</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitForm()">Save</button>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="detailTitle">Record Details</h2>
                <span class="close" onclick="closeDetailModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="loading" id="detailLoading">
                    <div class="spinner"></div>
                    <p>Loading...</p>
                </div>
                <div id="detailContent" style="display: none;">
                    <div id="detailData"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" onclick="editFromDetail()">Edit</button>
                <button type="button" class="btn btn-danger" onclick="deleteFromDetail()">Delete</button>
                <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        const baseUrl = '<?php echo base_url(); ?>';
        let isEditing = false;
        let currentRecordId = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('CRUDjs Initialized - Base URL:', baseUrl);
            loadRecords();
        }, false);

        // Load all records
        function loadRecords() {
            const url = baseUrl + 'crudjs/get_all';
            console.log('Loading records from:', url);
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Records loaded:', data);
                if (data.status === 'success') {
                    renderTable(data.data);
                } else if (data.status === 'error') {
                    showError(data.message || 'Failed to load records');
                }
            })
            .catch(error => {
                showError('Failed to load records: ' + error.message);
                console.error('Load records error:', error);
            });
        }

        // Render table
        function renderTable(records) {
            const tbody = document.querySelector('table tbody');
            if (!tbody) return;

            tbody.innerHTML = '';

            if (records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px;">No records found. Create your first record!</td></tr>';
                return;
            }

            records.forEach(record => {
                const row = document.createElement('tr');
                const imageHtml = record.image_url ? `<img src="${record.image_url}" alt="${record.title}" class="post-image">` : '<span style="color: #999;">No image</span>';
                const articlePreview = record.article.substring(0, 100) + (record.article.length > 100 ? '...' : '');

                row.innerHTML = `
                    <td>${record.id}</td>
                    <td>${imageHtml}</td>
                    <td><strong>${escapeHtml(record.title)}</strong></td>
                    <td>${escapeHtml(record.author)}</td>
                    <td>
                        <div class="article-preview">${escapeHtml(articlePreview)}</div>
                    </td>
                    <td>
                        <div class="actions">
                            <button type="button" class="btn btn-info" onclick="viewRecord(${record.id})">View</button>
                            <button type="button" class="btn btn-warning" onclick="editRecord(${record.id})">Edit</button>
                            <button type="button" class="btn btn-danger" onclick="deleteRecord(${record.id})">Delete</button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Open create modal
        function openCreateModal() {
            isEditing = false;
            currentRecordId = null;
            document.getElementById('modalTitle').textContent = 'Create New Record';
            document.getElementById('crudForm').reset();
            document.getElementById('recordId').value = '';
            document.getElementById('currentImageContainer').style.display = 'none';
            document.getElementById('formModal').classList.add('show');
        }

        // Edit record
        function editRecord(id) {
            isEditing = true;
            currentRecordId = id;
            document.getElementById('modalTitle').textContent = 'Edit Record';
            document.getElementById('recordId').value = id;
            
            // Load record data
            const url = baseUrl + 'crudjs/get_record/' + id;
            console.log('Loading record from:', url);
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Record loaded:', data);
                if (data.status === 'success') {
                    const record = data.data;
                    document.getElementById('title').value = record.title;
                    document.getElementById('author').value = record.author;
                    document.getElementById('article').value = record.article;
                    
                    if (record.image_url) {
                        document.getElementById('currentImage').src = record.image_url;
                        document.getElementById('currentImageContainer').style.display = 'block';
                    } else {
                        document.getElementById('currentImageContainer').style.display = 'none';
                    }
                    
                    document.getElementById('formModal').classList.add('show');
                } else {
                    showError(data.message || 'Failed to load record');
                }
            })
            .catch(error => {
                showError('Failed to load record: ' + error.message);
                console.error('Load record error:', error);
            });
        }

        // Submit form
        function submitForm() {
            const form = document.getElementById('crudForm');
            const formData = new FormData(form);
            
            const recordId = document.getElementById('recordId').value;
            const url = isEditing ? baseUrl + 'crudjs/update/' + recordId : baseUrl + 'crudjs/store';
            
            console.log('Submitting form to:', url, 'Edit mode:', isEditing);
            document.getElementById('loading').classList.add('show');

            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Form submitted, response:', data);
                document.getElementById('loading').classList.remove('show');
                
                if (data.status === 'success') {
                    showSuccess(data.message);
                    closeModal();
                    loadRecords();
                } else {
                    showError(data.message || 'Operation failed');
                }
            })
            .catch(error => {
                console.error('Submit error:', error);
                document.getElementById('loading').classList.remove('show');
                showError('An error occurred: ' + error.message);
            });
        }

        // View record
        function viewRecord(id) {
            console.log('Viewing record:', id);
            document.getElementById('detailModal').classList.add('show');
            document.getElementById('detailLoading').classList.add('show');
            document.getElementById('detailContent').style.display = 'none';
            
            const url = baseUrl + 'crudjs/get_record/' + id;
            console.log('Loading detail from:', url);
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Detail loaded:', data);
                if (data.status === 'success') {
                    const record = data.data;
                    currentRecordId = record.id;
                    renderDetailView(record);
                } else {
                    showError(data.message || 'Failed to load record details');
                    document.getElementById('detailLoading').classList.remove('show');
                }
            })
            .catch(error => {
                console.error('Load detail error:', error);
                showError('Failed to load record: ' + error.message);
                document.getElementById('detailLoading').classList.remove('show');
            });
        }

        // Render detail view
        function renderDetailView(record) {
            const detailData = document.getElementById('detailData');
            let detailHtml = `
                <h3>${escapeHtml(record.title)}</h3>
                <hr style="margin: 20px 0; border: none; border-top: 2px solid #e0e0e0;">
                <p><strong>Author:</strong> ${escapeHtml(record.author)}</p>
                <p><strong>Created:</strong> ${new Date(record.created_at).toLocaleString()}</p>
            `;

            if (record.updated_at && record.updated_at !== record.created_at) {
                detailHtml += `<p><strong>Updated:</strong> ${new Date(record.updated_at).toLocaleString()}</p>`;
            }

            if (record.image_url) {
                detailHtml += `<img src="${record.image_url}" alt="${record.title}" class="post-detail-image">`;
            }

            detailHtml += `
                <div style="margin-top: 30px;">
                    <h4 style="margin-bottom: 15px; color: #333;">Content</h4>
                    <div style="line-height: 1.8; color: #555; white-space: pre-wrap;">
                        ${escapeHtml(record.article)}
                    </div>
                </div>
            `;

            detailData.innerHTML = detailHtml;
            document.getElementById('detailLoading').classList.remove('show');
            document.getElementById('detailContent').style.display = 'block';
            document.getElementById('detailTitle').textContent = 'Record Details';
        }

        // Delete record
        function deleteRecord(id) {
            if (!confirm('Are you sure you want to delete this record?')) {
                return;
            }

            console.log('Deleting record:', id);
            const url = baseUrl + 'crudjs/delete/' + id;
            console.log('Delete URL:', url);

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Delete response:', data);
                if (data.status === 'success') {
                    showSuccess(data.message);
                    loadRecords();
                } else {
                    showError(data.message || 'Failed to delete record');
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                showError('Failed to delete record: ' + error.message);
            });
        }

        // Delete from detail modal
        function deleteFromDetail() {
            if (!currentRecordId) return;
            
            if (!confirm('Are you sure you want to delete this record?')) {
                return;
            }

            deleteRecord(currentRecordId);
            closeDetailModal();
        }

        // Edit from detail modal
        function editFromDetail() {
            if (!currentRecordId) return;
            
            closeDetailModal();
            editRecord(currentRecordId);
        }

        // Close modal
        function closeModal() {
            document.getElementById('formModal').classList.remove('show');
        }

        // Close detail modal
        function closeDetailModal() {
            document.getElementById('detailModal').classList.remove('show');
        }

        // Show success message
        function showSuccess(message) {
            console.log('Success:', message);
            const alert = document.getElementById('successAlert');
            alert.textContent = '✓ ' + message;
            alert.classList.add('show');
            setTimeout(() => {
                alert.classList.remove('show');
            }, 4000);
        }

        // Show error message
        function showError(message) {
            console.error('Error:', message);
            const alert = document.getElementById('errorAlert');
            alert.textContent = '✗ ' + message;
            alert.classList.add('show');
            setTimeout(() => {
                alert.classList.remove('show');
            }, 6000);
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const formModal = document.getElementById('formModal');
            const detailModal = document.getElementById('detailModal');
            
            if (event.target === formModal) {
                closeModal();
            }
            if (event.target === detailModal) {
                closeDetailModal();
            }
        }
    </script>
</body>
</html>
