<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkRole(['exco_user', 'exco_pa', 'finance']);

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle program creation/update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_program'])) {
    $program_name = trim($_POST['program_name']);
    $budget = floatval(str_replace(',', '', $_POST['budget']));
    $recipient_name = trim($_POST['recipient_name']);
    $exco_letter_ref = trim($_POST['exco_letter_ref']);
    $program_id = isset($_POST['program_id']) ? $_POST['program_id'] : null;
    
    if ($program_id) {
        // Update existing program
        $update_query = "UPDATE programs SET program_name = ?, budget = ?, recipient_name = ?, exco_letter_ref_number = ? WHERE id = ? AND created_by = ?";
        $stmt = $db->prepare($update_query);
        $stmt->execute([$program_name, $budget, $recipient_name, $exco_letter_ref, $program_id, $_SESSION['user_id']]);
        $success_message = 'Program updated successfully';
    } else {
        // Create new program
        $insert_query = "INSERT INTO programs (program_name, budget, recipient_name, exco_letter_ref_number, created_by) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($insert_query);
        $stmt->execute([$program_name, $budget, $recipient_name, $exco_letter_ref, $_SESSION['user_id']]);
        $program_id = $db->lastInsertId();
        $success_message = 'Program created successfully';
    }
    
    // Handle document uploads
    if (isset($_FILES['documents']) && isset($_POST['document_names'])) {
        $upload_dir = 'uploads/documents/';
        
        for ($i = 0; $i < count($_FILES['documents']['name']); $i++) {
            if ($_FILES['documents']['error'][$i] == UPLOAD_ERR_OK) {
                $document_name = $_POST['document_names'][$i];
                $file_extension = pathinfo($_FILES['documents']['name'][$i], PATHINFO_EXTENSION);
                $file_name = uniqid() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['documents']['tmp_name'][$i], $file_path)) {
                    $doc_query = "INSERT INTO documents (program_id, document_name, document_path, uploaded_by) VALUES (?, ?, ?, ?)";
                    $doc_stmt = $db->prepare($doc_query);
                    $doc_stmt->execute([$program_id, $document_name, $file_path, $_SESSION['user_id']]);
                }
            }
        }
    }
}

// Handle program submission
if (isset($_GET['submit']) && isset($_GET['id'])) {
    $program_id = $_GET['id'];
    $submit_query = "UPDATE programs SET status = 'Under Review by Finance', submitted_at = NOW() WHERE id = ? AND created_by = ?";
    $stmt = $db->prepare($submit_query);
    $stmt->execute([$program_id, $_SESSION['user_id']]);
    $success_message = 'Program submitted for review';
}

// Handle finance actions
if ($_SESSION['role'] == 'finance') {
    // Handle approval
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_program'])) {
        $program_id = $_POST['program_id'];
        $voucher_number = trim($_POST['voucher_number']);
        $eft_number = trim($_POST['eft_number']);
        
        $approve_query = "UPDATE programs SET status = 'Approved', voucher_number = ?, eft_number = ? WHERE id = ?";
        $stmt = $db->prepare($approve_query);
        $stmt->execute([$voucher_number, $eft_number, $program_id]);
        $success_message = 'Program approved successfully';
    }
    
    // Handle rejection
    if (isset($_GET['reject']) && isset($_GET['id'])) {
        $program_id = $_GET['id'];
        $reject_query = "UPDATE programs SET status = 'Rejected' WHERE id = ?";
        $stmt = $db->prepare($reject_query);
        $stmt->execute([$program_id]);
        $success_message = 'Program rejected';
    }
    
    // Handle query submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_query'])) {
        $program_id = $_POST['program_id'];
        $query_text = trim($_POST['query_text']);
        
        $query_insert = "INSERT INTO queries (program_id, query_text, created_by) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query_insert);
        $stmt->execute([$program_id, $query_text, $_SESSION['user_id']]);
        
        $update_status = "UPDATE programs SET status = 'Query' WHERE id = ?";
        $stmt = $db->prepare($update_status);
        $stmt->execute([$program_id]);
        
        $success_message = 'Query submitted successfully';
    }
}

// Get programs based on role
$where_clause = '';
$params = [];

if ($_SESSION['role'] == 'finance') {
    $where_clause = "WHERE p.status IN ('Under Review by Finance', 'Query', 'Query Answered', 'Approved', 'Rejected')";
} elseif ($_SESSION['role'] == 'exco_pa') {
    // exco_pa sees programs created by exco_pa and exco_user
    $where_clause = "WHERE u.role IN ('exco_user', 'exco_pa')";
} else {
    // exco_user sees only their own programs
    $where_clause = "WHERE p.created_by = ?";
    $params[] = $_SESSION['user_id'];
}

// Add status filter
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status = $_GET['status'];
    if (empty($where_clause)) {
        $where_clause = "WHERE p.status = ?";
    } else {
        $where_clause .= " AND p.status = ?";
    }
    $params[] = $status;
}

$programs_query = "
    SELECT p.*, u.full_name as created_by_name 
    FROM programs p 
    JOIN users u ON p.created_by = u.id 
    $where_clause
    ORDER BY p.created_at DESC
";

$stmt = $db->prepare($programs_query);
$stmt->execute($params);
$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Program Management - Program Management System';
include 'includes/header.php';
?>

<div class="container-fluid main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="page-title">Program Management</h1>
            <?php if (in_array($_SESSION['role'], ['exco_user', 'exco_pa'])): ?>
            <button type="button" class="btn btn-kedah-blue" data-bs-toggle="modal" data-bs-target="#programModal">
                <i class="fas fa-plus"></i> Add Program
            </button>
            <?php endif; ?>
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
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="d-flex gap-2">
                <select id="statusFilter" class="form-select">
                    <option value="">All Status</option>
                    <option value="Draft" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="Under Review by Finance" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Under Review by Finance') ? 'selected' : ''; ?>>Under Review by Finance</option>
                    <option value="Query" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Query') ? 'selected' : ''; ?>>Query</option>
                    <option value="Query Answered" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Query Answered') ? 'selected' : ''; ?>>Query Answered</option>
                    <option value="Approved" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                    <option value="Rejected" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                </select>
                <button type="button" class="btn btn-outline-kedah-blue" onclick="window.location.href='program_management.php'">
                    <i class="fas fa-sync"></i> Reset
                </button>
            </div>
        </div>
        <div class="col-md-6">
            <input type="text" id="searchInput" class="form-control" placeholder="Search programs...">
        </div>
    </div>
    
    <!-- Programs Table -->
    <div class="row">
        <div class="col-12">
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="programsTable">
                        <thead>
                            <tr>
                                <th>Program Name</th>
                                <th>Recipient</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programs as $program): ?>
                            <tr data-status="<?php echo $program['status']; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($program['program_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($program['exco_letter_ref_number']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($program['recipient_name']); ?></td>
                                <td>RM <?php echo number_format($program['budget'], 2); ?></td>
                                <td>
                                    <?php
                                    $status = $program['status'];
                                    $status_class = strtolower(str_replace(' ', '-', $status));
                                    echo "<span class='status-badge status-{$status_class}'>{$status}</span>";
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($program['created_by_name']); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <!-- View Details Button -->
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewProgram(<?php echo $program['id']; ?>)">
                                            <i class="fas fa-eye"></i> Details
                                        </button>
                                        
                                        <!-- Documents Button -->
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="viewDocuments(<?php echo $program['id']; ?>)">
                                            <i class="fas fa-file"></i> Documents
                                        </button>
                                        
                                        <?php if (in_array($_SESSION['role'], ['exco_user', 'exco_pa']) && $program['created_by'] == $_SESSION['user_id']): ?>
                                            <?php if ($program['status'] == 'Draft'): ?>
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="editProgram(<?php echo $program['id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            
                                            <!-- Submit Button -->
                                            <a href="?submit=1&id=<?php echo $program['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to submit this program?')">
                                                <i class="fas fa-paper-plane"></i> Submit
                                            </a>
                                            
                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete" onclick="deleteProgram(<?php echo $program['id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php if ($_SESSION['role'] == 'finance' && $program['status'] == 'Under Review by Finance'): ?>
                                        <!-- Finance Actions -->
                                        <button type="button" class="btn btn-sm btn-success" onclick="approveProgram(<?php echo $program['id']; ?>)">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        
                                        <a href="?reject=1&id=<?php echo $program['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this program?')">
                                            <i class="fas fa-times"></i> Reject
                                        </a>
                                        
                                        <button type="button" class="btn btn-sm btn-warning" onclick="submitQuery(<?php echo $program['id']; ?>)">
                                            <i class="fas fa-question"></i> Query
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Program Modal -->
<div class="modal fade" id="programModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Program</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="program_id" id="programId">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="program_name" class="form-label">Program Name</label>
                            <input type="text" class="form-control" id="program_name" name="program_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="budget" class="form-label">Budget (RM)</label>
                            <input type="text" class="form-control budget-input" id="budget" name="budget" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="recipient_name" class="form-label">Recipient Name</label>
                            <input type="text" class="form-control" id="recipient_name" name="recipient_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="exco_letter_ref" class="form-label">EXCO Letter Reference Number</label>
                            <input type="text" class="form-control" id="exco_letter_ref" name="exco_letter_ref" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Documents</label>
                        <div id="documentsContainer">
                            <!-- Documents will be added here dynamically -->
                        </div>
                        <button type="button" class="btn btn-outline-kedah-blue btn-sm" id="addDocumentBtn">
                            <i class="fas fa-plus"></i> Add Document
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_program" class="btn btn-kedah-blue">Save Program</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewProgram(programId) {
    // Implementation for viewing program details
    window.location.href = 'program_details.php?id=' + programId;
}

function viewDocuments(programId) {
    // Implementation for viewing documents
    window.location.href = 'program_documents.php?id=' + programId;
}

function editProgram(programId) {
    // Implementation for editing program
    // This would populate the modal with existing data
    $('#programModal').modal('show');
}

function deleteProgram(programId) {
    if (confirm('Are you sure you want to delete this program?')) {
        window.location.href = 'delete_program.php?id=' + programId;
    }
}

function approveProgram(programId) {
    // Show approval modal with voucher and EFT number fields
    var modalHtml = `
        <div class="modal fade" id="approvalModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Approve Program</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body">
                            <input type="hidden" name="program_id" value="${programId}">
                            <div class="mb-3">
                                <label class="form-label">Voucher Number</label>
                                <input type="text" class="form-control" name="voucher_number" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">EFT Number</label>
                                <input type="text" class="form-control" name="eft_number" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="approve_program" class="btn btn-success">Approve</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    $('body').append(modalHtml);
    $('#approvalModal').modal('show');
    
    $('#approvalModal').on('hidden.bs.modal', function() {
        $(this).remove();
    });
}

function submitQuery(programId) {
    // Show query modal
    var modalHtml = `
        <div class="modal fade" id="queryModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Submit Query</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body">
                            <input type="hidden" name="program_id" value="${programId}">
                            <div class="mb-3">
                                <label class="form-label">Query</label>
                                <textarea class="form-control" name="query_text" rows="4" required placeholder="Enter your query or questions about this program..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="submit_query" class="btn btn-warning">Submit Query</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    $('body').append(modalHtml);
    $('#queryModal').modal('show');
    
    $('#queryModal').on('hidden.bs.modal', function() {
        $(this).remove();
    });
}

// Status filter functionality
$('#statusFilter').on('change', function() {
    var selectedStatus = $(this).val();
    if (selectedStatus) {
        window.location.href = 'program_management.php?status=' + encodeURIComponent(selectedStatus);
    } else {
        window.location.href = 'program_management.php';
    }
});
</script>

<?php include 'includes/footer.php'; ?>