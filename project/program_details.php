<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkLogin();

$database = new Database();
$db = $database->getConnection();

$program_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Get program details
$program_query = "
    SELECT p.*, u.full_name as created_by_name, u.email as created_by_email
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

// Handle remark submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_remark'])) {
    $remark_text = trim($_POST['remark_text']);
    
    if (!empty($remark_text)) {
        $remark_query = "INSERT INTO remarks (program_id, remark_text, created_by) VALUES (?, ?, ?)";
        $stmt = $db->prepare($remark_query);
        
        if ($stmt->execute([$program_id, $remark_text, $_SESSION['user_id']])) {
            $success_message = 'Remark added successfully';
        } else {
            $error_message = 'Failed to add remark';
        }
    } else {
        $error_message = 'Please enter a remark';
    }
}

// Get program documents
$docs_query = "SELECT * FROM documents WHERE program_id = ? ORDER BY created_at DESC";
$docs_stmt = $db->prepare($docs_query);
$docs_stmt->execute([$program_id]);
$documents = $docs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get program remarks
$remarks_query = "
    SELECT r.*, u.full_name as created_by_name 
    FROM remarks r 
    JOIN users u ON r.created_by = u.id 
    WHERE r.program_id = ? 
    ORDER BY r.created_at DESC
";
$remarks_stmt = $db->prepare($remarks_query);
$remarks_stmt->execute([$program_id]);
$remarks = $remarks_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get program queries
$queries_query = "
    SELECT q.*, u.full_name as created_by_name, ur.full_name as responded_by_name
    FROM queries q
    JOIN users u ON q.created_by = u.id
    LEFT JOIN users ur ON q.responded_by = ur.id
    WHERE q.program_id = ?
    ORDER BY q.created_at DESC
";
$queries_stmt = $db->prepare($queries_query);
$queries_stmt->execute([$program_id]);
$queries = $queries_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Program Details - ' . $program['program_name'];
include 'includes/header.php';
?>

<div class="container-fluid main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title"><?php echo htmlspecialchars($program['program_name']); ?></h1>
                <p class="text-muted mb-0">Program Details and Information</p>
            </div>
            <div>
                <?php
                $status = $program['status'];
                $status_class = strtolower(str_replace(' ', '-', $status));
                echo "<span class='status-badge status-{$status_class} fs-6'>{$status}</span>";
                ?>
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
    
    <div class="row">
        <!-- Program Information -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header bg-kedah-blue text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Program Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Program Name</label>
                            <p class="fw-bold"><?php echo htmlspecialchars($program['program_name']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Budget</label>
                            <p class="fw-bold text-kedah-blue">RM <?php echo number_format($program['budget'], 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Recipient Name</label>
                            <p class="fw-bold"><?php echo htmlspecialchars($program['recipient_name']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">EXCO Letter Reference</label>
                            <p class="fw-bold"><?php echo htmlspecialchars($program['exco_letter_ref_number']); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($program['voucher_number'] || $program['eft_number']): ?>
                    <div class="row">
                        <?php if ($program['voucher_number']): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Voucher Number</label>
                            <p class="fw-bold"><?php echo htmlspecialchars($program['voucher_number']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($program['eft_number']): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">EFT Number</label>
                            <p class="fw-bold"><?php echo htmlspecialchars($program['eft_number']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Created By</label>
                            <p class="fw-bold"><?php echo htmlspecialchars($program['created_by_name']); ?></p>
                            <small class="text-muted"><?php echo htmlspecialchars($program['created_by_email']); ?></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Created Date</label>
                            <p class="fw-bold"><?php echo date('F j, Y g:i A', strtotime($program['created_at'])); ?></p>
                            <?php if ($program['submitted_at']): ?>
                            <small class="text-muted">Submitted: <?php echo date('F j, Y g:i A', strtotime($program['submitted_at'])); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Documents Section -->
            <div class="card mt-4">
                <div class="card-header bg-kedah-gold text-dark">
                    <h5 class="mb-0"><i class="fas fa-file-alt"></i> Documents (<?php echo count($documents); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($documents)): ?>
                    <div class="row">
                        <?php foreach ($documents as $doc): ?>
                        <div class="col-md-6 mb-3">
                            <div class="document-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($doc['document_name']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?> • 
                                            <?php echo date('M j, Y', strtotime($doc['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <a href="<?php echo $doc['document_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="<?php echo $doc['document_path']; ?>" download class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No documents uploaded yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Queries Section -->
            <?php if (!empty($queries)): ?>
            <div class="card mt-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-question-circle"></i> Queries & Responses</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($queries as $query): ?>
                    <div class="query-item">
                        <div class="query-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong><i class="fas fa-user"></i> <?php echo htmlspecialchars($query['created_by_name']); ?></strong>
                                <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($query['created_at'])); ?></small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Query:</strong>
                            <p class="mb-2"><?php echo nl2br(htmlspecialchars($query['query_text'])); ?></p>
                        </div>
                        
                        <?php if ($query['response_text']): ?>
                        <div class="bg-light p-3 rounded">
                            <strong>Response:</strong>
                            <p class="mb-2"><?php echo nl2br(htmlspecialchars($query['response_text'])); ?></p>
                            <small class="text-muted">
                                Responded by <?php echo htmlspecialchars($query['responded_by_name']); ?> 
                                on <?php echo date('M j, Y g:i A', strtotime($query['responded_at'])); ?>
                            </small>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock"></i> Waiting for response
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Remarks Section -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-comments"></i> Remarks</h5>
                </div>
                <div class="card-body">
                    <!-- Add Remark Form -->
                    <form method="POST" action="" class="mb-4">
                        <div class="mb-3">
                            <textarea class="form-control" name="remark_text" rows="3" placeholder="Add a remark..." required></textarea>
                        </div>
                        <button type="submit" name="add_remark" class="btn btn-info btn-sm w-100">
                            <i class="fas fa-plus"></i> Add Remark
                        </button>
                    </form>
                    
                    <!-- Remarks List -->
                    <div class="remarks-list" style="max-height: 400px; overflow-y: auto;">
                        <?php if (!empty($remarks)): ?>
                        <?php foreach ($remarks as $remark): ?>
                        <div class="remark-item">
                            <div class="remark-meta">
                                <strong><?php echo htmlspecialchars($remark['created_by_name']); ?></strong>
                                <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($remark['created_at'])); ?></small>
                            </div>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($remark['remark_text'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comments fa-2x text-muted mb-2"></i>
                            <p class="text-muted">No remarks yet</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="program_management.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Programs
                        </a>
                        
                        <a href="program_documents.php?id=<?php echo $program_id; ?>" class="btn btn-outline-info">
                            <i class="fas fa-file"></i> Manage Documents
                        </a>
                        
                        <?php if (in_array($_SESSION['role'], ['exco_user', 'exco_pa']) && $program['created_by'] == $_SESSION['user_id'] && $program['status'] == 'Draft'): ?>
                        <a href="program_management.php?edit=<?php echo $program_id; ?>" class="btn btn-outline-warning">
                            <i class="fas fa-edit"></i> Edit Program
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>