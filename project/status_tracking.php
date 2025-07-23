<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkRole(['admin', 'finance']);

$database = new Database();
$db = $database->getConnection();

// Get all programs with user information
$programs_query = "
    SELECT p.*, u.full_name as created_by_name, u.email as created_by_email
    FROM programs p 
    JOIN users u ON p.created_by = u.id 
    ORDER BY p.updated_at DESC
";

$stmt = $db->prepare($programs_query);
$stmt->execute();
$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status statistics
$stats_query = "
    SELECT 
        status,
        COUNT(*) as count,
        SUM(budget) as total_budget
    FROM programs 
    GROUP BY status
";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$status_stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Status Tracking - Program Management System';
include 'includes/header.php';
?>

<div class="container-fluid main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="page-title">Status Tracking</h1>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-kedah-blue" onclick="exportData()">
                    <i class="fas fa-download"></i> Export
                </button>
                <button type="button" class="btn btn-outline-kedah-blue" onclick="refreshData()">
                    <i class="fas fa-sync"></i> Refresh
                </button>
            </div>
        </div>
    </div>
    
    <!-- Status Statistics -->
    <div class="row mb-4">
        <?php foreach ($status_stats as $stat): ?>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="dashboard-card p-3">
                <div class="text-center">
                    <h4 class="mb-1 text-kedah-blue"><?php echo $stat['count']; ?></h4>
                    <p class="mb-1 small"><?php echo $stat['status']; ?></p>
                    <small class="text-muted">RM <?php echo number_format($stat['total_budget'], 0); ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Filters and Search -->
    <div class="row mb-4">
        <div class="col-md-3">
            <select id="statusFilter" class="form-select">
                <option value="">All Status</option>
                <option value="Draft">Draft</option>
                <option value="Under Review by Finance">Under Review by Finance</option>
                <option value="Query">Query</option>
                <option value="Query Answered">Query Answered</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
            </select>
        </div>
        <div class="col-md-3">
            <select id="userFilter" class="form-select">
                <option value="">All Users</option>
                <?php
                $users_query = "SELECT DISTINCT u.id, u.full_name FROM users u JOIN programs p ON u.id = p.created_by ORDER BY u.full_name";
                $users_stmt = $db->prepare($users_query);
                $users_stmt->execute();
                $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($users as $user):
                ?>
                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" id="dateFilter" class="form-control">
        </div>
        <div class="col-md-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search programs...">
        </div>
    </div>
    
    <!-- Programs Table -->
    <div class="row">
        <div class="col-12">
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="statusTable">
                        <thead>
                            <tr>
                                <th>Program Name</th>
                                <th>Recipient</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Created Date</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programs as $program): ?>
                            <tr data-status="<?php echo $program['status']; ?>" data-user="<?php echo $program['created_by']; ?>" data-date="<?php echo date('Y-m-d', strtotime($program['created_at'])); ?>">
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($program['program_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($program['exco_letter_ref_number']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($program['recipient_name']); ?></td>
                                <td>
                                    <div>
                                        <strong>RM <?php echo number_format($program['budget'], 2); ?></strong>
                                        <?php if ($program['voucher_number'] || $program['eft_number']): ?>
                                        <br>
                                        <?php if ($program['voucher_number']): ?>
                                        <small class="text-muted">Voucher: <?php echo htmlspecialchars($program['voucher_number']); ?></small><br>
                                        <?php endif; ?>
                                        <?php if ($program['eft_number']): ?>
                                        <small class="text-muted">EFT: <?php echo htmlspecialchars($program['eft_number']); ?></small>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $status = $program['status'];
                                    $status_class = strtolower(str_replace(' ', '-', $status));
                                    echo "<span class='status-badge status-{$status_class}'>{$status}</span>";
                                    
                                    if ($program['submitted_at']):
                                        echo '<br><small class="text-muted">Submitted: ' . date('M j, Y', strtotime($program['submitted_at'])) . '</small>';
                                    endif;
                                    ?>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($program['created_by_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($program['created_by_email']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($program['created_at'])); ?></td>
                                <td>
                                    <span data-bs-toggle="tooltip" title="<?php echo date('M j, Y g:i A', strtotime($program['updated_at'])); ?>">
                                        <?php echo date('M j, Y', strtotime($program['updated_at'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewProgramDetails(<?php echo $program['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="viewProgramHistory(<?php echo $program['id']; ?>)">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        
                                        <?php if ($program['status'] == 'Query'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="viewQueries(<?php echo $program['id']; ?>)">
                                            <i class="fas fa-question-circle"></i>
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

<!-- Program Details Modal -->
<div class="modal fade" id="programDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Program Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="programDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewProgramDetails(programId) {
    // Load program details via AJAX
    $('#programDetailsModal').modal('show');
    $('#programDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    
    // You would implement AJAX call here to load program details
    setTimeout(() => {
        $('#programDetailsContent').html('<p>Program details would be loaded here via AJAX.</p>');
    }, 1000);
}

function viewProgramHistory(programId) {
    // Load program history
    alert('Program history for ID: ' + programId);
}

function viewQueries(programId) {
    // Load queries for the program
    alert('Queries for program ID: ' + programId);
}

function exportData() {
    // Export functionality
    alert('Export functionality would be implemented here');
}

function refreshData() {
    location.reload();
}

// Filter functionality
$('#statusFilter, #userFilter, #dateFilter').on('change', function() {
    filterTable();
});

$('#searchInput').on('keyup', function() {
    filterTable();
});

function filterTable() {
    var statusFilter = $('#statusFilter').val();
    var userFilter = $('#userFilter').val();
    var dateFilter = $('#dateFilter').val();
    var searchTerm = $('#searchInput').val().toLowerCase();
    
    $('#statusTable tbody tr').each(function() {
        var row = $(this);
        var show = true;
        
        // Status filter
        if (statusFilter && row.data('status') !== statusFilter) {
            show = false;
        }
        
        // User filter
        if (userFilter && row.data('user') != userFilter) {
            show = false;
        }
        
        // Date filter
        if (dateFilter && row.data('date') !== dateFilter) {
            show = false;
        }
        
        // Search filter
        if (searchTerm && row.text().toLowerCase().indexOf(searchTerm) === -1) {
            show = false;
        }
        
        row.toggle(show);
    });
}
</script>

<?php include 'includes/footer.php'; ?>