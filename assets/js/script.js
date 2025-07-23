$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Enhanced file upload drag and drop
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

    // File input change handler with validation
    $('input[type="file"]').on('change', function() {
        updateFileList(this);
        validateFileSize(this);
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
            var fileIcon = getFileIcon(fileName);
            
            fileList.append(`
                <div class="d-flex justify-content-between align-items-center py-2 px-3 mb-2 bg-light rounded">
                    <div class="d-flex align-items-center">
                        <i class="${fileIcon} me-2"></i>
                        <span class="fw-medium">${fileName}</span>
                    </div>
                    <small class="text-muted">${fileSize}</small>
                </div>
            `);
        }
    }

    function validateFileSize(input) {
        var maxSize = 10 * 1024 * 1024; // 10MB
        var files = input.files;
        
        for (var i = 0; i < files.length; i++) {
            if (files[i].size > maxSize) {
                showAlert('File size must be less than 10MB', 'danger');
                input.value = '';
                return false;
            }
        }
        return true;
    }

    function getFileIcon(fileName) {
        var extension = fileName.split('.').pop().toLowerCase();
        var iconMap = {
            'pdf': 'fas fa-file-pdf text-danger',
            'doc': 'fas fa-file-word text-primary',
            'docx': 'fas fa-file-word text-primary',
            'jpg': 'fas fa-file-image text-success',
            'jpeg': 'fas fa-file-image text-success',
            'png': 'fas fa-file-image text-success',
            'gif': 'fas fa-file-image text-success',
            'xls': 'fas fa-file-excel text-success',
            'xlsx': 'fas fa-file-excel text-success'
        };
        
        return iconMap[extension] || 'fas fa-file text-muted';
    }

    // Enhanced confirm delete actions
    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        var itemName = $(this).data('item-name') || 'this item';
        
        if (confirm(`Are you sure you want to delete ${itemName}? This action cannot be undone.`)) {
            // Add loading state
            $(this).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
            $(this).prop('disabled', true);
            
            // Proceed with deletion
            window.location.href = $(this).attr('href');
        }
    });

    // Auto-hide alerts with animation
    $('.alert').each(function() {
        var alert = $(this);
        setTimeout(function() {
            alert.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    });

    // Enhanced status filter with animation
    $('#statusFilter').on('change', function() {
        var selectedStatus = $(this).val();
        var table = $('#programsTable tbody tr');
        
        table.hide();
        
        if (selectedStatus === '') {
            table.fadeIn(300);
        } else {
            table.filter('[data-status="' + selectedStatus + '"]').fadeIn(300);
        }
        
        updateResultCount();
    });

    // Enhanced search functionality with debouncing
    var searchTimeout;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        var searchTerm = $(this).val();
        
        searchTimeout = setTimeout(function() {
            performSearch(searchTerm);
        }, 300);
    });

    function performSearch(searchTerm) {
        var value = searchTerm.toLowerCase();
        var table = $('#programsTable tbody tr, #usersTable tbody tr, #statusTable tbody tr');
        
        table.each(function() {
            var row = $(this);
            var text = row.text().toLowerCase();
            
            if (text.indexOf(value) > -1) {
                row.fadeIn(200);
            } else {
                row.fadeOut(200);
            }
        });
        
        updateResultCount();
    }

    function updateResultCount() {
        var visibleRows = $('#programsTable tbody tr:visible, #usersTable tbody tr:visible, #statusTable tbody tr:visible').length;
        var totalRows = $('#programsTable tbody tr, #usersTable tbody tr, #statusTable tbody tr').length;
        
        // Update result counter if exists
        $('.result-counter').text(`Showing ${visibleRows} of ${totalRows} results`);
    }

    // Dynamic document upload with improved UX
    let documentIndex = 0;
    
    $('#addDocumentBtn').on('click', function() {
        documentIndex++;
        var documentHtml = `
            <div class="document-upload-item border rounded p-3 mb-3 bg-light" data-index="${documentIndex}">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Document Name</label>
                        <input type="text" class="form-control" name="document_names[]" required placeholder="Enter document name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Document File</label>
                        <div class="input-group">
                            <input type="file" class="form-control" name="documents[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                            <button type="button" class="btn btn-outline-danger remove-document-btn" title="Remove document">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        var container = $('#documentsContainer');
        container.append(documentHtml);
        
        // Animate the new item
        container.find('.document-upload-item').last().hide().fadeIn(300);
    });

    // Remove document upload item with animation
    $(document).on('click', '.remove-document-btn', function() {
        var item = $(this).closest('.document-upload-item');
        item.fadeOut(300, function() {
            $(this).remove();
        });
    });

    // Enhanced form validation with real-time feedback
    $('form').on('submit', function(e) {
        var form = $(this);
        var isValid = true;
        var requiredFields = form.find('[required]');
        
        // Clear previous validation states
        requiredFields.removeClass('is-invalid is-valid');
        
        requiredFields.each(function() {
            var field = $(this);
            var value = field.val().trim();
            
            if (value === '') {
                field.addClass('is-invalid');
                isValid = false;
                
                // Add error message if not exists
                if (!field.next('.invalid-feedback').length) {
                    field.after('<div class="invalid-feedback">This field is required.</div>');
                }
            } else {
                field.addClass('is-valid');
                field.next('.invalid-feedback').remove();
            }
        });

        if (!isValid) {
            e.preventDefault();
            showAlert('Please fill in all required fields.', 'danger');
            
            // Scroll to first invalid field
            var firstInvalid = form.find('.is-invalid').first();
            if (firstInvalid.length) {
                $('html, body').animate({
                    scrollTop: firstInvalid.offset().top - 100
                }, 500);
                firstInvalid.focus();
            }
            
            return false;
        }
        
        // Show loading state
        var submitBtn = form.find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...').prop('disabled', true);
        
        // Re-enable after 5 seconds as fallback
        setTimeout(function() {
            submitBtn.html(originalText).prop('disabled', false);
        }, 5000);
    });

    // Real-time field validation
    $('input[required], textarea[required], select[required]').on('blur', function() {
        var field = $(this);
        var value = field.val().trim();
        
        field.removeClass('is-invalid is-valid');
        field.next('.invalid-feedback').remove();
        
        if (value === '') {
            field.addClass('is-invalid');
            field.after('<div class="invalid-feedback">This field is required.</div>');
        } else {
            field.addClass('is-valid');
        }
    });

    // Enhanced budget formatting with validation
    $('.budget-input').on('input', function() {
        var value = $(this).val().replace(/[^\d.]/g, '');
        var parts = value.split('.');
        
        // Limit to 2 decimal places
        if (parts[1] && parts[1].length > 2) {
            parts[1] = parts[1].substring(0, 2);
        }
        
        // Format with commas
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        
        var formattedValue = parts.join('.');
        $(this).val(formattedValue);
        
        // Validate amount
        var numericValue = parseFloat(value);
        if (numericValue > 1000000) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Amount cannot exceed RM 1,000,000</div>');
            }
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    // Auto-save form data to localStorage
    $('form input, form textarea, form select').on('change', function() {
        var formId = $(this).closest('form').attr('id');
        if (formId) {
            var formData = {};
            $(this).closest('form').find('input, textarea, select').each(function() {
                if ($(this).attr('name') && $(this).attr('type') !== 'password') {
                    formData[$(this).attr('name')] = $(this).val();
                }
            });
            localStorage.setItem('form_' + formId, JSON.stringify(formData));
        }
    });

    // Restore form data from localStorage
    $('form[id]').each(function() {
        var formId = $(this).attr('id');
        var savedData = localStorage.getItem('form_' + formId);
        
        if (savedData) {
            try {
                var formData = JSON.parse(savedData);
                var form = $(this);
                
                Object.keys(formData).forEach(function(name) {
                    form.find('[name="' + name + '"]').val(formData[name]);
                });
            } catch (e) {
                console.log('Error restoring form data:', e);
            }
        }
    });

    // Clear saved form data on successful submission
    $('form').on('submit', function() {
        var formId = $(this).attr('id');
        if (formId) {
            localStorage.removeItem('form_' + formId);
        }
    });

    // Enhanced table interactions
    $('.table tbody tr').hover(
        function() {
            $(this).addClass('table-hover-highlight');
        },
        function() {
            $(this).removeClass('table-hover-highlight');
        }
    );

    // Keyboard shortcuts
    $(document).keydown(function(e) {
        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 75) {
            e.preventDefault();
            $('#searchInput').focus();
        }
        
        // Escape to clear search
        if (e.keyCode === 27) {
            $('#searchInput').val('').trigger('keyup');
        }
    });

    // Initialize result counter
    updateResultCount();
});

// Utility functions
function showAlert(message, type = 'success') {
    var alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    var container = $('#alertContainer');
    if (container.length === 0) {
        container = $('<div id="alertContainer"></div>').prependTo('.main-content');
    }
    
    container.html(alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(function() {
        container.find('.alert').fadeOut(500, function() {
            $(this).remove();
        });
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
    var statusIcon = getStatusIcon(status);
    return `<span class="status-badge status-${statusClass}"><i class="${statusIcon} me-1"></i>${status}</span>`;
}

function getStatusIcon(status) {
    var iconMap = {
        'Draft': 'fas fa-edit',
        'Under Review by Finance': 'fas fa-clock',
        'Query': 'fas fa-question-circle',
        'Query Answered': 'fas fa-reply',
        'Approved': 'fas fa-check-circle',
        'Rejected': 'fas fa-times-circle'
    };
    
    return iconMap[status] || 'fas fa-circle';
}

// Export functionality
function exportTableData(tableId, filename) {
    var table = document.getElementById(tableId);
    if (!table) return;
    
    var csv = [];
    var rows = table.querySelectorAll('tr');
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (var j = 0; j < cols.length; j++) {
            var cellText = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + cellText + '"');
        }
        
        csv.push(row.join(','));
    }
    
    var csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    var downloadLink = document.createElement('a');
    
    downloadLink.download = filename || 'export.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Print functionality
function printTable(tableId) {
    var printWindow = window.open('', '', 'height=600,width=800');
    var table = document.getElementById(tableId);
    
    if (!table) return;
    
    printWindow.document.write('<html><head><title>Print</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: Arial, sans-serif; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; }');
    printWindow.document.write('th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }');
    printWindow.document.write('th { background-color: #f2f2f2; }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<h2>Program Management System Report</h2>');
    printWindow.document.write(table.outerHTML);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    printWindow.print();
}

// Initialize page-specific functionality
$(document).ready(function() {
    // Add smooth scrolling to anchor links
    $('a[href^="#"]').on('click', function(event) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            event.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 1000);
        }
    });
    
    // Add loading states to navigation links
    $('.nav-link').on('click', function() {
        if ($(this).attr('href') !== '#' && !$(this).hasClass('dropdown-toggle')) {
            $(this).append(' <i class="fas fa-spinner fa-spin ms-1"></i>');
        }
    });
});