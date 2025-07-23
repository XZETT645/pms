<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Program Management System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php if (isset($_SESSION['user_id'])): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-kedah-blue">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <img src="assets/img/logo.png" alt="Logo" class="mb-3 mx-auto d-block" style="width: 50px; height: 50px; object-fit: contain;">
            <span>Program Management System</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                </li>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="user_management.php"><i class="fas fa-users"></i> User Management</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="status_tracking.php"><i class="fas fa-chart-line"></i> Status Tracking</a>
                </li>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['exco_user', 'exco_pa', 'finance'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="program_management.php"><i class="fas fa-project-diagram"></i> Program Management</a>
                </li>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'exco_pa'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="query.php"><i class="fas fa-question-circle"></i> Query</a>
                </li>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'finance'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="status_tracking.php"><i class="fas fa-chart-line"></i> Status Tracking</a>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User'; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>