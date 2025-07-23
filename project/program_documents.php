<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkLogin();

$database = new Database();
$db = $database->getConnection();

$program_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Get program details
$program_query = "
    SELECT p.*, u.full_name as created_by_name
    FROM programs p 
    JOIN users u ON p.created_by = u.id 
    WHERE p.id = ?
";

$stmt = $db->prepare($program_query);
$stmt->execute([$program_id]);
$program = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$program) {
    header('Location: program_management.php');
    exit();
}

// Check access permissions
if ($_SESSION['role'] == 'exco_user') {
    if ($program['created_by'] != $_SESSION['user_id']) {
        header('Location: unauthorized.php');
        exit();
    }
} elseif ($_SESSION['role'] == 'exco_pa') {
    // Get creator's role
    $creator_role_query = "SELECT role FROM users WHERE id = ?";
    $creator_stmt = $db->prepare($creator_role_query);
    $creator_stmt->execute([$program['created_by']]);
    $creator = $creator_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$creator || !in_array($creator['role'], ['exco_user', 'exco_pa'])) {
        header('Location: unauthorized.php');
        exit();
    }
}

$success_message = '';
$error_message = '';

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_document'])) {
    $document_name = trim($_POST['document_name']);
    $document_type = $_POST['document_type'];
    
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = ($_SESSION['role'] == 'finance' && $document_type == 'signed_document') 
            ? 'uploads/signed_documents/' 
            : 'uploads/documents/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['document_file']['tmp_name'], $file_path)) {
            $doc_query = "INSERT INTO documents (program_id, document_name, document_path, document_type, uploaded_by) VALUES (?, ?, ?, ?, ?)";
            $doc_stmt = $db->prepare($doc_query);
            
            if ($doc_stmt->execute([$program_id, $document_name, $file_path, $document_type, $_SESSION['user_id']])) {
                $success_message = 'Document uploaded successfully';
            } else {
                $error_message = 'Failed to save document information';
                unlink($file_path); // Delete uploaded file if database insert fails
            }
        } else {
            $error_message = 'Failed to upload document';
        }
    } else {
        $error_message = 'Please select a file to upload';
    }
}

// Handle document deletion
if (isset($_GET['delete']) && isset($_GET['doc_id'])) {
    $doc_id = $_GET['doc_id'];
    
    // Get document info first
    $doc_query = "SELECT * FROM documents WHERE id = ? AND program_id = ?";
    $doc_stmt = $db->prepare($doc_query);
    $doc_stmt->execute([$doc_id, $program_id]);
    $document = $doc_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($document) {
        // Check if user has permission to delete
        $can_delete = false;
        if ($_SESSION['role'] == 'admin') {
            $can_delete = true;
        } elseif (in_array($_SESSION['role'], ['exco_user', 'exco_pa']) && $document['uploaded_by'] == $_SESSION['user_id']) {
            $can_delete = true;
        } elseif ($_SESSION['role'] == 'finance' && $document['document_type'] == 'signed_document') {
            $can_delete = true;
        }
        
        if ($can_delete) {
            $delete_query = "DELETE FROM documents WHERE id = ?";
            $delete_stmt = $db->prepare($delete_query);
            
            if ($delete_stmt->execute([$doc_id])) {
                // Delete physical file
                if (file_exists($document['document_path'])) {
                    unlink($document['document_path']);
                }
                $success_message = 'Document deleted successfully';
            } else {
                $error_message = 'Failed to delete document';
            }
        } else {
            $error_message = 'You do not have permission to delete this document';
        }
    }
}

// Get all documents for this program
$docs_query = "
    SELECT d.*, u.full_name as uploaded_by_name 
    FROM documents d 
    JOIN users u ON d.uploaded_by = u.id 
    WHERE d.program_id = ? 
    ORDER BY d.created_at DESC
";
$docs_stmt = $db->prepare($docs_query);
$docs_stmt->execute([$program_id]);
$documents = $docs_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Documents - ' . $program['program_name'];
include 'includes/header.php';
?>

<div class="container-fluid main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Program Documents</h1>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($program['program_name']); ?></p>
            </div>
            <div>
                <button type="button" class="btn btn-kedah-blue" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-upload"></i> Upload Document
                </button>
                <a href="program_details.php?id=<?php echo $program_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Details
                </a>
            </div>
        </div>
    </div>
    
    <div id="alertContainer">
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Documents Grid -->
    <div class="row">
        <?php if (!empty($documents)): ?>
        <?php foreach ($documents as $doc): ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo htmlspecialchars($doc['document_name']); ?></h6>
                        <small class="text-muted">
                            <?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?>
                        </small>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $doc['document_path']; ?>" target="_blank">
                                <i class="fas fa-eye"></i> View
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo $doc['document_path']; ?>" download>
                                <i class="fas fa-download"></i> Download
                            </a></li>
                            <?php
                            $can_delete = false;
                            if ($_SESSION['role'] == 'admin') {
                                $can_delete = true;
                            } elseif (in_array($_SESSION['role'], ['exco_user', 'exco_pa']) && $doc['uploaded_by'] == $_SESSION['user_id']) {
                                $can_delete = true;
                            } elseif ($_SESSION['role'] == 'finance' && $doc['document_type'] == 'signed_document') {
                                $can_delete = true;
                            }
                            
                            if ($can_delete):
                            ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="?id=<?php echo $program_id; ?>&delete=1&doc_id=<?php echo $doc['id']; ?>" onclick="return confirm('Are you sure you want to delete this document?')">
                                <i class="fas fa-trash"></i> Delete
                            </a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <?php
                        $file_extension = strtolower(pathinfo($doc['document_path'], PATHINFO_EXTENSION));
                        $icon_class = 'fas fa-file';
                        $icon_color = 'text-muted';
                        
                        switch ($file_extension) {
                            case 'pdf':
                                $icon_class = 'fas fa-file-pdf';
                                $icon_color = 'text-danger';
                                break;
                            case 'doc':
                            case 'docx':
                                $icon_class = 'fas fa-file-word';
                                $icon_color = 'text-primary';
                                break;
                            case 'jpg':
                            case 'jpeg':
                            case 'png':
                            case 'gif':
                                $icon_class = 'fas fa-file-image';
                                $icon_color = 'text-success';
                                break;
                            case 'xls':
                            case 'xlsx':
                                $icon_class = 'fas fa-file-excel';
                                $icon_color = 'text-success';
                                break;
                        }
                        ?>
                        <i class="<?php echo $icon_class; ?> fa-4x <?php echo $icon_color; ?>"></i>
                    </div>
                    
                    <div class="small text-muted">
                        <div class="mb-1">
                            <strong>Uploaded by:</strong> <?php echo htmlspecialchars($doc['uploaded_by_name']); ?>
                        </div>
                        <div class="mb-1">
                            <strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($doc['created_at'])); ?>
                        </div>
                        <div>
                            <strong>Type:</strong> 
                            <?php if ($doc['document_type'] == 'signed_document'): ?>
                                <span class="badge bg-success">Signed Document</span>
                            <?php else: ?>
                                <span class="badge bg-primary">Program Document</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo $doc['document_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="<?php echo $doc['document_path']; ?>" download class="btn btn-sm btn-outline-success">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No Documents Found</h4>
                <p class="text-muted">No documents have been uploaded for this program yet.</p>
                <button type="button" class="btn btn-kedah-blue" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-upload"></i> Upload First Document
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="document_name" class="form-label">Document Name</label>
                        <input type="text" class="form-control" id="document_name" name="document_name" required placeholder="Enter document name">
                    </div>
                    
                    <div class="mb-3">
                        <label for="document_type" class="form-label">Document Type</label>
                        <select class="form-select" id="document_type" name="document_type" required>
                            <?php if ($_SESSION['role'] == 'finance'): ?>
                            <option value="signed_document">Signed Document</option>
                            <option value="program_document">Program Document</option>
                            <?php else: ?>
                            <option value="program_document">Program Document</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="document_file" class="form-label">Select File</label>
                        <input type="file" class="form-control" id="document_file" name="document_file" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx">
                        <div class="form-text">Supported formats: PDF, DOC, DOCX, JPG, PNG, XLS, XLSX (Max: 10MB)</div>
                    </div>
                    
                    <div class="upload-area text-center p-4 border-2 border-dashed rounded" style="border-color: #dee2e6;">
                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Drag and drop your file here or click to browse</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="upload_document" class="btn btn-kedah-blue">
                        <i class="fas fa-upload"></i> Upload Document
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// File upload drag and drop functionality
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.querySelector('.upload-area');
    const fileInput = document.getElementById('document_file');
    
    uploadArea.addEventListener('click', () => fileInput.click());
    
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#007bff';
        uploadArea.style.backgroundColor = '#f8f9fa';
    });
    
    uploadArea.addEventListener('dragleave', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#dee2e6';
        uploadArea.style.backgroundColor = 'transparent';
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#dee2e6';
        uploadArea.style.backgroundColor = 'transparent';
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            updateFileName(files[0].name);
        }
    });
    
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            updateFileName(this.files[0].name);
        }
    });
    
    function updateFileName(fileName) {
        const uploadText = uploadArea.querySelector('p');
        uploadText.textContent = `Selected: ${fileName}`;
        uploadText.classList.remove('text-muted');
        uploadText.classList.add('text-success');
    }
});
</script>

<?php include 'includes/footer.php'; ?>