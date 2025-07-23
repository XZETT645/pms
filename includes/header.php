@@ .. @@
 <!DOCTYPE html>
 <html lang="en">
 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title><?php echo isset($page_title) ? $page_title : 'Program Management System'; ?></title>
+    <link rel="preconnect" href="https://fonts.googleapis.com">
+    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
+    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
     <link href="assets/css/style.css" rel="stylesheet">
+    <meta name="theme-color" content="#1e3a8a">
 </head>
 <body>
 <?php if (isset($_SESSION['user_id'])): ?>
-<nav class="navbar navbar-expand-lg navbar-dark bg-kedah-blue">
+<nav class="navbar navbar-expand-lg navbar-dark bg-kedah-blue sticky-top">
     <div class="container-fluid">
         <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
-            <img src="assets/img/logo.png" alt="Logo" class="mb-3 mx-auto d-block" style="width: 50px; height: 50px; object-fit: contain;">
-            <span>Program Management System</span>
+            <div class="logo-placeholder me-2">
+                <i class="fas fa-project-diagram"></i>
+            </div>
+            <span class="d-none d-md-inline">Program Management System</span>
+            <span class="d-md-none">PMS</span>
         </a>
         
         <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
@@ -1,17 +1,25 @@
         <div class="collapse navbar-collapse" id="navbarNav">
             <ul class="navbar-nav me-auto">
                 <li class="nav-item">
-                    <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
+                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
+                        <i class="fas fa-tachometer-alt me-1"></i> 
+                        <span class="d-none d-lg-inline">Dashboard</span>
+                    </a>
                 </li>
                 
                 <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                 <li class="nav-item">
-                    <a class="nav-link" href="user_management.php"><i class="fas fa-users"></i> User Management</a>
+                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : ''; ?>" href="user_management.php">
+                        <i class="fas fa-users me-1"></i> 
+                        <span class="d-none d-lg-inline">Users</span>
+                    </a>
                 </li>
                 <li class="nav-item">
-                    <a class="nav-link" href="status_tracking.php"><i class="fas fa-chart-line"></i> Status Tracking</a>
+                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'status_tracking.php' ? 'active' : ''; ?>" href="status_tracking.php">
+                        <i class="fas fa-chart-line me-1"></i> 
+                        <span class="d-none d-lg-inline">Status</span>
+                    </a>
                 </li>
                 <?php endif; ?>
                 
                 <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['exco_user', 'exco_pa', 'finance'])): ?>
                 <li class="nav-item">
-                    <a class="nav-link" href="program_management.php"><i class="fas fa-project-diagram"></i> Program Management</a>
+                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'program_management.php' ? 'active' : ''; ?>" href="program_management.php">
+                        <i class="fas fa-project-diagram me-1"></i> 
+                        <span class="d-none d-lg-inline">Programs</span>
+                    </a>
                 </li>
                 <?php endif; ?>
                 
                 <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'exco_pa'): ?>
                 <li class="nav-item">
-                    <a class="nav-link" href="query.php"><i class="fas fa-question-circle"></i> Query</a>
+                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'query.php' ? 'active' : ''; ?>" href="query.php">
+                        <i class="fas fa-question-circle me-1"></i> 
+                        <span class="d-none d-lg-inline">Queries</span>
+                    </a>
                 </li>
                 <?php endif; ?>
                 
                 <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'finance'): ?>
                 <li class="nav-item">
-                    <a class="nav-link" href="status_tracking.php"><i class="fas fa-chart-line"></i> Status Tracking</a>
+                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'status_tracking.php' ? 'active' : ''; ?>" href="status_tracking.php">
+                        <i class="fas fa-chart-line me-1"></i> 
+                        <span class="d-none d-lg-inline">Status</span>
+                    </a>
                 </li>
                 <?php endif; ?>
             </ul>
             
             <ul class="navbar-nav">
+                <!-- Notifications -->
+                <li class="nav-item dropdown me-2">
+                    <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown">
+                        <i class="fas fa-bell"></i>
+                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
+                            3
+                        </span>
+                    </a>
+                    <ul class="dropdown-menu dropdown-menu-end" style="min-width: 300px;">
+                        <li><h6 class="dropdown-header">Notifications</h6></li>
+                        <li><a class="dropdown-item" href="#">
+                            <div class="d-flex">
+                                <div class="flex-shrink-0">
+                                    <i class="fas fa-info-circle text-info"></i>
+                                </div>
+                                <div class="flex-grow-1 ms-2">
+                                    <div class="fw-bold">New Program Submitted</div>
+                                    <small class="text-muted">Program ABC requires review</small>
+                                </div>
+                            </div>
+                        </a></li>
+                        <li><hr class="dropdown-divider"></li>
+                        <li><a class="dropdown-item text-center" href="#">View all notifications</a></li>
+                    </ul>
+                </li>
+                
+                <!-- User Menu -->
                 <li class="nav-item dropdown">
-                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
-                        <i class="fas fa-user"></i> <?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User'; ?>
+                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
+                        <div class="bg-white text-kedah-blue rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.875rem; font-weight: 600;">
+                            <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?>
+                        </div>
+                        <span class="d-none d-md-inline"><?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User'; ?></span>
                     </a>
                     <ul class="dropdown-menu">
-                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
+                        <li><a class="dropdown-item" href="profile.php">
+                            <i class="fas fa-user-edit me-2"></i> Profile
+                        </a></li>
+                        <li><a class="dropdown-item" href="#">
+                            <i class="fas fa-cog me-2"></i> Settings
+                        </a></li>
                         <li><hr class="dropdown-divider"></li>
-                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
+                        <li><a class="dropdown-item text-danger" href="logout.php">
+                            <i class="fas fa-sign-out-alt me-2"></i> Logout
+                        </a></li>
                     </ul>
                 </li>
             </ul>
         </div>
     </div>
 </nav>
+
+<!-- Breadcrumb -->
+<div class="container-fluid">
+    <nav aria-label="breadcrumb" class="mt-3">
+        <ol class="breadcrumb bg-transparent">
+            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Home</a></li>
+            <?php
+            $current_page = basename($_SERVER['PHP_SELF'], '.php');
+            $page_names = [
+                'dashboard' => 'Dashboard',
+                'user_management' => 'User Management',
+                'program_management' => 'Program Management',
+                'status_tracking' => 'Status Tracking',
+                'query' => 'Query Management',
+                'profile' => 'Profile',
+                'program_details' => 'Program Details',
+                'program_documents' => 'Documents'
+            ];
+            
+            if (isset($page_names[$current_page]) && $current_page !== 'dashboard'):
+            ?>
+            <li class="breadcrumb-item active" aria-current="page"><?php echo $page_names[$current_page]; ?></li>
+            <?php endif; ?>
+        </ol>
+    </nav>
+</div>
 <?php endif; ?>