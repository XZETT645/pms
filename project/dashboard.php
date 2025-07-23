<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkLogin();

$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_programs,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_programs,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected_programs,
        SUM(CASE WHEN status IN ('Draft', 'Under Review by Finance', 'Query', 'Query Answered') THEN 1 ELSE 0 END) as pending_programs,
        SUM(budget) as total_budget,
        SUM(CASE WHEN status = 'Approved' THEN budget ELSE 0 END) as approved_budget
    FROM programs
";

$stmt = $db->prepare($stats_query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$remaining_budget = $stats['total_budget'] - $stats['approved_budget'];

// Get recent programs
$recent_query = "
    SELECT p.*, u.full_name as created_by_name 
    FROM programs p 
    JOIN users u ON p.created_by = u.id 
    ORDER BY p.created_at DESC 
    LIMIT 10
";

$stmt = $db->prepare($recent_query);
$stmt->execute();
$recent_programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Dashboard - Program Management System';
include 'includes/header.php';
?>

<div class="container-fluid main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="page-title">Dashboard</h1>
            <div class="text-muted">
                <i class="fas fa-calendar"></i> <?php echo date('l, F j, Y'); ?>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="dashboard-card p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1 text-kedah-blue"><?php echo number_format($stats['total_programs']); ?></h3>
                        <p class="mb-0 text-muted">Total Programs</p>
                    </div>
                    <div class="text-kedah-blue">
                        <i class="fas fa-project-diagram fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="dashboard-card success p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1 text-success"><?php echo number_format($stats['approved_programs']); ?></h3>
                        <p class="mb-0 text-muted">Approved Programs</p>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="dashboard-card danger p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1 text-danger"><?php echo number_format($stats['rejected_programs']); ?></h3>
                        <p class="mb-0 text-muted">Rejected Programs</p>
                    </div>
                    <div class="text-danger">
                        <i class="fas fa-times-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="dashboard-card warning p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1 text-warning"><?php echo number_format($stats['pending_programs']); ?></h3>
                        <p class="mb-0 text-muted">Pending Programs</p>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Budget Cards -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="dashboard-card gold p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1 text-kedah-blue">RM <?php echo number_format($stats['total_budget'], 2); ?></h3>
                        <p class="mb-0 text-muted">Total Budget</p>
                    </div>
                    <div class="text-kedah-gold">
                        <i class="fas fa-wallet fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-3">
            <div class="dashboard-card p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1 text-kedah-blue">RM <?php echo number_format($remaining_budget, 2); ?></h3>
                        <p class="mb-0 text-muted">Remaining Budget</p>
                    </div>
                    <div class="text-kedah-blue">
                        <i class="fas fa-piggy-bank fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Programs Table -->
    <div class="row">
        <div class="col-12">
            <div class="table-container">
                <div class="p-3 border-bottom">
                    <h5 class="mb-0 text-kedah-blue">
                        <i class="fas fa-history"></i> Recent Programs
                    </h5>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Program Name</th>
                                <th>Recipient</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Created Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_programs)): ?>
                                <?php foreach ($recent_programs as $program): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($program['program_name']); ?></strong>
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
                                    <td><?php echo date('M j, Y', strtotime($program['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    No programs found
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>