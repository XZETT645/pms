<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (!empty($email) && !empty($password)) {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, full_name, email, password, role, is_active FROM users WHERE email = ? AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = 'Invalid email or password';
            }
        } else {
            $error_message = 'Invalid email or password';
        }
    } else {
        $error_message = 'Please fill in all fields';
    }
}

$page_title = 'Login - Program Management System';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <meta name="theme-color" content="#1e3a8a">
</head>
<body>
    <div class="login-container d-flex align-items-center justify-content-center">
        <div class="login-card">
            <div class="text-center mb-4">
                <div class="logo-placeholder mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <h2 class="page-title mb-2">Program Management System</h2>
                <p class="text-muted mb-0">Kerajaan Negeri Kedah</p>
                <small class="text-muted">Secure Login Portal</small>
            </div>
            
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-envelope text-kedah-blue"></i>
                        </span>
                        <input type="email" class="form-control border-start-0" id="email" name="email" required placeholder="Enter your email address">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-lock text-kedah-blue"></i>
                        </span>
                        <input type="password" class="form-control border-start-0" id="password" name="password" required placeholder="Enter your password">
                        <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-kedah-blue btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i> Sign In
                    </button>
                </div>
                
                <div class="text-center mb-3">
                    <a href="#" class="text-decoration-none text-kedah-blue">
                        <small>Forgot your password?</small>
                    </a>
                </div>
            </form>
            
            <hr class="my-4">
            
            <div class="text-center">
                <h6 class="text-kedah-blue mb-3">Demo Credentials</h6>
                <div class="row g-2 text-start">
                    <div class="col-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body p-2">
                                <small class="fw-bold text-kedah-blue">Admin</small><br>
                                <small class="text-muted">admin@kedah.gov.my</small><br>
                                <small class="text-muted">admin123</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body p-2">
                                <small class="fw-bold text-kedah-blue">Finance</small><br>
                                <small class="text-muted">finance@kedah.gov.my</small><br>
                                <small class="text-muted">admin123</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background: rgba(0,0,0,0.5); z-index: 9999;">
        <div class="d-flex align-items-center justify-content-center h-100">
            <div class="text-center text-white">
                <div class="spinner-border text-light mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Signing you in...</p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Show loading overlay on form submit
        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('loadingOverlay').classList.remove('d-none');
        });
        
        // Demo credential quick fill
        document.addEventListener('DOMContentLoaded', function() {
            const demoCards = document.querySelectorAll('.card.bg-light');
            demoCards.forEach(card => {
                card.style.cursor = 'pointer';
                card.addEventListener('click', function() {
                    const emails = {
                        'Admin': 'admin@kedah.gov.my',
                        'Finance': 'finance@kedah.gov.my'
                    };
                    
                    const role = this.querySelector('.fw-bold').textContent;
                    document.getElementById('email').value = emails[role];
                    document.getElementById('password').value = 'admin123';
                    
                    // Add visual feedback
                    this.classList.add('border-kedah-blue');
                    setTimeout(() => {
                        this.classList.remove('border-kedah-blue');
                    }, 1000);
                });
            });
        });
    </script>
</body>
</html>