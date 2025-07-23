$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // File upload drag and drop
    $('.upload-area').on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });

    $('.upload-area').on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });

    $('.upload-area').on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        
        var files = e.originalEvent.dataTransfer.files;
        var fileInput = $(this).find('input[type="file"]')[0];
        fileInput.files = files;
        
        updateFileList(fileInput);
    });

    // File input change handler
    $('input[type="file"]').on('change', function() {
        updateFileList(this);
    });

    function updateFileList(input) {
        var files = input.files;
        var fileList = $(input).closest('.upload-area').find('.file-list');
        
        if (fileList.length === 0) {
            fileList = $('<div class="file-list mt-2"></div>');
            $(input).closest('.upload-area').append(fileList);
        }
        
        fileList.empty();
        
        for (var i = 0; i < files.length; i++) {
            var fileName = files[i].name;
            var fileSize = (files[i].size / 1024 / 1024).toFixed(2) + ' MB';
            
            fileList.append(`
                <div class="d-flex justify-content-between align-items-center py-1">
                    <span><i class="fas fa-file"></i> ${fileName}</span>
                    <small class="text-muted">${fileSize}</small>
                </div>
            `);
        }
    }

    // Confirm delete actions
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });

    // Auto-hide alerts
    $('.alert').delay(5000).fadeOut();

    // Status filter
    $('#statusFilter').on('change', function() {
        var selectedStatus = $(this).val();
        var table = $('#programsTable tbody tr');
        
        if (selectedStatus === '') {
            table.show();
        } else {
            table.hide();
            table.filter('[data-status="' + selectedStatus + '"]').show();
        }
    });

    // Search functionality
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        var table = $('#programsTable tbody tr');
        
        table.filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Dynamic document upload
    let documentIndex = 0;
    
    $('#addDocumentBtn').on('click', function() {
        documentIndex++;
        var documentHtml = `
            <div class="document-upload-item border p-3 mb-3 rounded" data-index="${documentIndex}">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Document Name</label>
                        <input type="text" class="form-control" name="document_names[]" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Document File</label>
                        <div class="input-group">
                            <input type="file" class="form-control" name="documents[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                            <button type="button" class="btn btn-outline-danger remove-document-btn">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#documentsContainer').append(documentHtml);
    });

    // Remove document upload item
    $(document).on('click', '.remove-document-btn', function() {
        $(this).closest('.document-upload-item').remove();
    });

    // Form validation
    $('form').on('submit', function() {
        var isValid = true;
        var requiredFields = $(this).find('[required]');
        
        requiredFields.each(function() {
            if ($(this).val() === '') {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            alert('Please fill in all required fields.');
            return false;
        }
    });

    // Budget formatting
    $('.budget-input').on('input', function() {
        var value = $(this).val().replace(/[^\d.]/g, '');
        var parts = value.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        $(this).val(parts.join('.'));
    });
});

// Utility functions
function showAlert(message, type = 'success') {
    var alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('#alertContainer').html(alertHtml);
    
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

function formatCurrency(amount) {
    return 'RM ' + parseFloat(amount).toLocaleString('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function getStatusBadge(status) {
    var statusClass = status.toLowerCase().replace(/\s+/g, '-');
    return `<span class="status-badge status-${statusClass}">${status}</span>`;
}