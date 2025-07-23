$(document).ready(function() {
    // Initialize AOS (Animate On Scroll) if available
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
    }
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Enhanced loading states
    function showLoading(element) {
        element.addClass('loading').prop('disabled', true);
        const originalText = element.html();
        element.data('original-text', originalText);
        element.html('<i class="fas fa-spinner fa-spin me-2"></i>Loading...');
    }
    
    function hideLoading(element) {
        element.removeClass('loading').prop('disabled', false);
        element.html(element.data('original-text'));
    }
    
    // Enhanced form submissions
    $('form').on('submit', function() {
        const submitBtn = $(this).find('button[type="submit"]');
        if (submitBtn.length) {
            showLoading(submitBtn);
        }
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
        if (fileInput) {
            fileInput.files = files;
            updateFileList(fileInput);
            
            // Add success animation
            $(this).addClass('border-success bg-success bg-opacity-10');
            setTimeout(() => {
                $(this).removeClass('border-success bg-success bg-opacity-10');
            }, 1000);
        }
    });

    // File input change handler
    $('input[type="file"]').on('change', function() {
        updateFileList(this);
    });

    // Enhanced file list display
    function updateFileList(input) {
        var files = input.files;
        var fileList = $(input).closest('.upload-area').find('.file-list');
        
        if (fileList.length === 0) {
            fileList = $('<div class="file-list mt-3"></div>');
            $(input).closest('.upload-area').append(fileList);
        }
        
        fileList.empty();
        
        for (var i = 0; i < files.length; i++) {
            var fileName = files[i].name;
            var fileSize = (files[i].size / 1024 / 1024).toFixed(2) + ' MB';
            var fileExtension = fileName.split('.').pop().toLowerCase();
            var fileIcon = getFileIcon(fileExtension);
            
            fileList.append(`
                <div class="d-flex justify-content-between align-items-center py-2 px-3 bg-light rounded mb-2">
                    <div class="d-flex align-items-center">
                        <i class="${fileIcon} me-2 text-kedah-blue"></i>
                        <div>
                            <div class="fw-medium">${fileName}</div>
                            <small class="text-muted">${fileSize}</small>
                        </div>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            `);
        }
    }
    
    // Get appropriate file icon
    function getFileIcon(extension) {
        const iconMap = {
            'pdf': 'fas fa-file-pdf text-danger',
            'doc': 'fas fa-file-word text-primary',
            'docx': 'fas fa-file-word text-primary',
            'xls': 'fas fa-file-excel text-success',
            'xlsx': 'fas fa-file-excel text-success',
            'jpg': 'fas fa-file-image text-info',
            'jpeg': 'fas fa-file-image text-info',
            'png': 'fas fa-file-image text-info',
            'gif': 'fas fa-file-image text-info'
        };
        
        return iconMap[extension] || 'fas fa-file text-muted';
    }

    // Confirm delete actions
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const item = $(this).data('item') || 'item';
        
        // Enhanced confirmation dialog
        if (!confirm(`Are you sure you want to delete this ${item}? This action cannot be undone.`)) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        showLoading($(this));
    });

    // Enhanced alert handling
    $('.alert').each(function() {
        const alert = $(this);
        
        // Add slide-in animation
        alert.addClass('animate__animated animate__slideInDown');
        
        // Auto-hide after delay
        setTimeout(() => {
            alert.addClass('animate__slideOutUp');
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
    
    // Manual alert dismissal
    $('.alert .btn-close').on('click', function() {
        const alert = $(this).closest('.alert');
        alert.addClass('animate__animated animate__slideOutUp');
        setTimeout(() => {
            alert.remove();
        }, 300);
    });

    // Enhanced status filter
    $('#statusFilter').on('change', function() {
        var selectedStatus = $(this).val();
        var table = $('#programsTable tbody tr');
        
        if (selectedStatus === '') {
            table.fadeIn(300);
        } else {
            table.fadeOut(200);
            setTimeout(() => {
                table.filter('[data-status="' + selectedStatus + '"]').fadeIn(300);
            }, 200);
        }
        
        // Update result count
        updateResultCount();
    });

    // Enhanced search functionality
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        var table = $('#programsTable tbody tr');
        var visibleCount = 0;
        
        table.filter(function() {
            const isVisible = $(this).text().toLowerCase().indexOf(value) > -1;
            $(this).toggle(isVisible);
            if (isVisible) visibleCount++;
            return isVisible;
        });
        
        // Show search results count
        showSearchResults(visibleCount, table.length);
    });
    
    // Show search results count
    function showSearchResults(visible, total) {
        let resultText = '';
        if (visible === total) {
            resultText = `Showing all ${total} results`;
        } else {
            resultText = `Showing ${visible} of ${total} results`;
        }
        
        // Update or create result counter
        let counter = $('.search-results-count');
        if (counter.length === 0) {
            counter = $('<small class="search-results-count text-muted ms-2"></small>');
            $('#searchInput').parent().append(counter);
        }
        counter.text(resultText);
    }
    
    // Update result count for filters
    function updateResultCount() {
        const visible = $('#programsTable tbody tr:visible').length;
        const total = $('#programsTable tbody tr').length;
        showSearchResults(visible, total);
    }

    // Dynamic document upload
    let documentIndex = 0;
    
    $('#addDocumentBtn').on('click', function() {
        documentIndex++;
        var documentHtml = `
            <div class="document-upload-item border p-3 mb-3 rounded animate__animated animate__fadeInUp" data-index="${documentIndex}">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Document Name</label>
                        <input type="text" class="form-control" name="document_names[]" required placeholder="Enter document name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Document File</label>
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
        $('#documentsContainer').append(documentHtml);
        
        // Initialize tooltip for new button
        $('[title]').tooltip();
    });

    // Remove document upload item
    $(document).on('click', '.remove-document-btn', function() {
        const item = $(this).closest('.document-upload-item');
        item.addClass('animate__animated animate__fadeOutUp');
        setTimeout(() => {
            item.remove();
        }, 300);
    });

    // Enhanced form validation
    $('form').on('submit', function() {
        var isValid = true;
        var requiredFields = $(this).find('[required]');
        var firstInvalidField = null;
        
        requiredFields.each(function() {
            const field = $(this);
            const value = field.val().trim();
            
            if ($(this).val() === '') {
                field.addClass('is-invalid');
                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
                isValid = false;
            } else {
                field.removeClass('is-invalid').addClass('is-valid');
            }
        });

        if (!isValid) {
            // Focus on first invalid field
            if (firstInvalidField) {
                firstInvalidField.focus();
            }
            
            // Show enhanced error message
            showAlert('Please fill in all required fields.', 'danger');
            return false;
        }
        
        return true;
    });
    
    // Real-time validation feedback
    $('input[required], select[required], textarea[required]').on('blur', function() {
        const field = $(this);
        const value = field.val().trim();
        
        if (value === '') {
            field.addClass('is-invalid').removeClass('is-valid');
        } else {
            field.addClass('is-valid').removeClass('is-invalid');
        }
    });

    // Enhanced budget formatting
    $('.budget-input').on('input', function() {
        var value = $(this).val().replace(/[^\d.]/g, '');
        var parts = value.split('.');
        
        // Limit decimal places to 2
        if (parts[1] && parts[1].length > 2) {
            parts[1] = parts[1].substring(0, 2);
        }
        
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        $(this).val(parts.join('.'));
        
        // Add visual feedback for large amounts
        const numericValue = parseFloat(value);
        if (numericValue > 1000000) {
            $(this).addClass('border-warning');
        } else {
            $(this).removeClass('border-warning');
        }
    });
    
    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $($(this).attr('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });
    
    // Enhanced table row interactions
    $('.table tbody tr').hover(
        function() {
            $(this).addClass('table-hover-effect');
        },
        function() {
            $(this).removeClass('table-hover-effect');
        }
    );
    
    // Auto-save form data to localStorage
    $('form input, form select, form textarea').on('change', function() {
        const form = $(this).closest('form');
        const formId = form.attr('id') || 'default-form';
        const formData = form.serialize();
        localStorage.setItem(`form-data-${formId}`, formData);
    });
    
    // Restore form data from localStorage
    $('form').each(function() {
        const form = $(this);
        const formId = form.attr('id') || 'default-form';
        const savedData = localStorage.getItem(`form-data-${formId}`);
        
        if (savedData) {
            // Parse and restore form data
            const params = new URLSearchParams(savedData);
            params.forEach((value, key) => {
                const field = form.find(`[name="${key}"]`);
                if (field.length) {
                    field.val(value);
                }
            });
        }
    });
    
    // Clear saved form data on successful submission
    $('form').on('submit', function() {
        const formId = $(this).attr('id') || 'default-form';
        localStorage.removeItem(`form-data-${formId}`);
    });
});

// Utility functions
function showAlert(message, type = 'success', duration = 5000) {
    const alertId = 'alert-' + Date.now();
    var alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show animate__animated animate__slideInDown" role="alert">
            <i class="fas fa-${getAlertIcon(type)} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Create alert container if it doesn't exist
    if ($('#alertContainer').length === 0) {
        $('body').prepend('<div id="alertContainer" class="container-fluid mt-3"></div>');
    }
    
    $('#alertContainer').append(alertHtml);
    
    // Auto-hide alert
    setTimeout(function() {
        const alert = $(`#${alertId}`);
        alert.addClass('animate__slideOutUp');
        setTimeout(() => {
            alert.remove();
        }, 300);
    }, duration);
}

function getAlertIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Enhanced currency formatting
function formatCurrency(amount) {
    const formatted = parseFloat(amount).toLocaleString('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    return `RM ${formatted}`;
}

// Enhanced status badge generation
function getStatusBadge(status) {
    var statusClass = status.toLowerCase().replace(/\s+/g, '-');
    var icon = getStatusIcon(status);
    return `<span class="status-badge status-${statusClass}">
                <i class="${icon} me-1"></i>${status}
            </span>`;
}

function getStatusIcon(status) {
    const icons = {
        'Draft': 'fas fa-edit',
        'Under Review by Finance': 'fas fa-search',
        'Query': 'fas fa-question-circle',
        'Query Answered': 'fas fa-reply',
        'Approved': 'fas fa-check-circle',
        'Rejected': 'fas fa-times-circle'
    };
    return icons[status] || 'fas fa-circle';
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Enhanced data export functionality
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            let text = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + text + '"');
        }
        csv.push(row.join(','));
    }
    
    // Download CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Print functionality
function printTable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Print Table</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { font-family: Arial, sans-serif; }
                    .table { font-size: 12px; }
                    @media print { .no-print { display: none; } }
                </style>
            </head>
            <body>
                <div class="container-fluid">
                    <h2>Program Management System Report</h2>
                    <p>Generated on: ${new Date().toLocaleString()}</p>
                    ${table.outerHTML}
                </div>
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}