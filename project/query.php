<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkRole(['exco_pa']);

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle query response
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['respond_query'])) {
    $query_id = $_POST['query_id'];
    $response_text = trim($_POST['response_text']);
    
    $update_query = "UPDATE queries SET response_text = ?, status = 'Answered', responded_by = ?, responded_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($update_query);
    
    if ($stmt->execute([$response_text, $_SESSION['user_id'], $query_id])) {
        // Update program status to Query Answered
        $program_query = "UPDATE programs SET status = 'Query Answered' WHERE id = (SELECT program_id FROM queries WHERE id = ?)";
        $program_stmt = $db->prepare($program_query);
        $program_stmt->execute([$query_id]);
        
        $success_message = 'Query response submitted successfully';
    } else {
        $error_message = 'Failed to submit query response';
    }
}

// Get all programs that have queries (including past queries even if approved)
$queries_query = "
    SELECT DISTINCT p.id as program_id, p.program_name, p.recipient_name, p.budget, 
           p.status, p.exco_letter_ref_number, u.full_name as created_by_name
    FROM programs p
    JOIN queries q ON p.id = q.program_id
    JOIN users u ON p.created_by = u.id
    WHERE u.role IN ('exco_user', 'exco_pa')
    ORDER BY p.updated_at DESC
";

$stmt = $db->prepare($queries_query);
$stmt->execute();
$programs_with_queries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Query Management - Program Management System';
include 'includes/header.php';
?>

<div class="container-fluid main-content">
    <div class="page-header">
        <h1 class="page-title">Query Management</h1>
        <p class="text-muted">Manage queries and responses for your programs</p>
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
    
    <!-- Filter -->
    <div class="row mb-4">
        <div class="col-md-4">
            <select id="statusFilter" class="form-select">
                <option value="">All Programs with Queries</option>
                <option value="Query">Active Queries Only</option>
                <option value="Query Answered">Answered Queries</option>
                <option value="Approved">Approved Programs</option>
            </select>
        </div>
        <div class="col-md-8">
            <input type="text" id="searchInput" class="form-control" placeholder="Search programs...">
        </div>
    </div>
    
    <!-- Programs with Queries -->
    <div class="row">
        <?php if (empty($programs_with_queries)): ?>
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No Queries Found</h4>
                <p class="text-muted">You don't have any programs with queries yet.</p>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($programs_with_queries as $program): ?>
        <div class="col-lg-6 mb-4" data-status="<?php echo $program['status']; ?>">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><?php echo htmlspecialchars($program['program_name']); ?></h6>
                    <?php
                    $status = $program['status'];
                    $status_class = strtolower(str_replace(' ', '-', $status));
                    echo "<span class='status-badge status-{$status_class}'>{$status}</span>";
                    ?>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted">Recipient:</small><br>
                            <strong><?php echo htmlspecialchars($program['recipient_name']); ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Budget:</small><br>
                            <strong>RM <?php echo number_format($program['budget'], 2); ?></strong>
                        </div>
                    </div>
                    
                    <!-- Get queries for this program -->
                    <?php
                    $program_queries = "
                        SELECT q.*, u.full_name as created_by_name, ur.full_name as responded_by_name
                        FROM queries q
                        JOIN users u ON q.created_by = u.id
                        LEFT JOIN users ur ON q.responded_by = ur.id
                        WHERE q.program_id = ?
                        ORDER BY q.created_at DESC
                    ";
                    $q_stmt = $db->prepare($program_queries);
                    $q_stmt->execute([$program['program_id']]);
                    $queries = $q_stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <div class="queries-section">
                        <?php foreach ($queries as $query): ?>
                        <div class="query-item">
                            <div class="query-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small><strong><?php echo htmlspecialchars($query['created_by_name']); ?></strong></small>
                                    <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($query['created_at'])); ?></small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Query:</strong>
                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($query['query_text'])); ?></p>
                            </div>
                            
                            <?php if ($query['response_text']): ?>
                            <div class="mb-3 bg-light p-3 rounded">
                                <strong>Response:</strong>
                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($query['response_text'])); ?></p>
                                <small class="text-muted">
                                    Responded by <?php echo htmlspecialchars($query['responded_by_name']); ?> 
                                    on <?php echo date('M j, Y g:i A', strtotime($query['responded_at'])); ?>
                                </small>
                            </div>
                            <?php else: ?>
                            <div class="mb-3">
                                <?php if ($program['status'] == 'Query'): ?>
                                <button type="button" class="btn btn-kedah-blue btn-sm" onclick="respondToQuery(<?php echo $query['id']; ?>, '<?php echo htmlspecialchars($program['program_name']); ?>')">
                                    <i class="fas fa-reply"></i> Respond to Query
                                </button>
                                <?php else: ?>
                                <small class="text-muted">No response yet</small>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="viewProgramDetails(<?php echo $program['program_id']; ?>)">
                            <i class="fas fa-eye"></i> View Program
                        </button>
                        
                        <?php if ($program['status'] == 'Query'): ?>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="editProgram(<?php echo $program['program_id']; ?>)">
                            <i class="fas fa-edit"></i> Edit Program
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Query Response Modal -->
<div class="modal fade" id="queryResponseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Respond to Query</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="query_id" id="queryId">
                    
                    <div class="mb-3">
                        <label class="form-label">Program:</label>
                        <div id="programName" class="fw-bold text-kedah-blue"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="response_text" class="form-label">Your Response</label>
                        <textarea class="form-control" id="response_text" name="response_text" rows="5" required placeholder="Enter your response to the query..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> After submitting your response, the program status will change to "Query Answered" and will be reviewed by the finance team.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="respond_query" class="btn btn-kedah-blue">Submit Response</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function respondToQuery(queryId, programName) {
    $('#queryId').val(queryId);
    $('#programName').text(programName);
    $('#response_text').val('');
    $('#queryResponseModal').modal('show');
}

function viewProgramDetails(programId) {
    window.location.href = 'program_details.php?id=' + programId;
}

function editProgram(programId) {
    window.location.href = 'program_management.php?edit=' + programId;
}

// Filter functionality
$('#statusFilter').on('change', function() {
    var selectedStatus = $(this).val();
    var cards = $('.col-lg-6[data-status]');
    
    if (selectedStatus === '') {
        cards.show();
    } else {
        cards.hide();
        cards.filter('[data-status="' + selectedStatus + '"]').show();
    }
});

$('#searchInput').on('keyup', function() {
    var searchTerm = $(this).val().toLowerCase();
    var cards = $('.col-lg-6');
    
    cards.each(function() {
        var cardText = $(this).text().toLowerCase();
        $(this).toggle(cardText.indexOf(searchTerm) > -1);
    });
});
</script>

<?php include 'includes/footer.php'; ?>