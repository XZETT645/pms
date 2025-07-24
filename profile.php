<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkLogin();

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Get user data
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($user_query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    
    // Handle profile photo upload as BLOB
    $profile_photo_blob = $user['profile_photo_blob'];
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_photo']['tmp_name'];
        $file_size = $_FILES['profile_photo']['size'];
        $file_type = $_FILES['profile_photo']['type'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file_type, $allowed_types)) {
            $error_message = 'Only JPG, PNG, and GIF files are allowed';
        }
        // Validate file size (5MB max)
        elseif ($file_size > 5 * 1024 * 1024) {
            $error_message = 'File size must be less than 5MB';
        }
        else {
            // Read file content as BLOB
            $profile_photo_blob = file_get_contents($file_tmp);
            if ($profile_photo_blob === false) {
                $error_message = 'Failed to read uploaded file';
            }
        }
    }
    
    // Update profile if no errors
    if (empty($error_message)) {
        $update_query = "UPDATE users SET full_name = ?, email = ?, phone_number = ?, profile_photo_blob = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        
        try {
            $stmt->bindParam(1, $full_name, PDO::PARAM_STR);
            $stmt->bindParam(2, $email, PDO::PARAM_STR);
            $stmt->bindParam(3, $phone_number, PDO::PARAM_STR);
            $stmt->bindParam(4, $profile_photo_blob, PDO::PARAM_LOB);
            $stmt->bindParam(5, $_SESSION['user_id'], PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                $success_message = 'Profile updated successfully';
                
                // Refresh user data
                $stmt = $db->prepare($user_query);
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error_info = $stmt->errorInfo();
                $error_message = 'Failed to update profile: ' . $error_info[2];
            }
        } catch (PDOException $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $password_query = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $db->prepare($password_query);
                
                if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                    $success_message = 'Password changed successfully';
                } else {
                    $error_message = 'Failed to change password';
                }
            } else {
                $error_message = 'Password must be at least 6 characters long';
            }
        } else {
            $error_message = 'New passwords do not match';
        }
    } else {
        $error_message = 'Current password is incorrect';
    }
}

$page_title = 'Profile - Program Management System';
include 'includes/header.php';
?>

<div class="container-fluid main-content">
    <div class="page-header">
        <h1 class="page-title">Profile</h1>
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
        <!-- Profile Information -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header bg-kedah-blue text-white">
                    <h5 class="mb-0"><i class="fas fa-user"></i> Profile Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data" id="profileForm">
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <div class="profile-photo-container">
                                    <?php if (!empty($user['profile_photo_blob'])): ?>
                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($user['profile_photo_blob']); ?>" class="profile-photo mb-3" alt="Profile Photo">
                                    <?php else: ?>
                                        <div class="profile-photo mb-3 bg-kedah-blue text-white d-flex align-items-center justify-content-center" style="font-size: 3rem;">
                                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="profile_photo" class="form-label">Change Photo</label>
                                        <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                                        <div class="form-text">Max size: 5MB. Formats: JPG, PNG, GIF</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone_number" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="role" class="form-label">Role</label>
                                        <input type="text" class="form-control" id="role" value="<?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Member Since</label>
                                        <input type="text" class="form-control" value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" readonly>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Account Status</label>
                                        <input type="text" class="form-control" value="<?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="update_profile" class="btn btn-kedah-blue">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Change Password -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header bg-kedah-gold text-dark">
                    <h5 class="mb-0"><i class="fas fa-lock"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="passwordForm">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-kedah-gold w-100">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Account Information -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-kedah-blue"><i class="fas fa-info-circle"></i> Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>User ID:</strong> #<?php echo str_pad($user['id'], 6, '0', STR_PAD_LEFT); ?>
                    </div>
                    <div class="mb-2">
                        <strong>Last Updated:</strong><br>
                        <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($user['updated_at'])); ?></small>
                    </div>
                    <div class="mb-2">
                        <strong>Account Created:</strong><br>
                        <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Preview profile photo before upload
document.getElementById('profile_photo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validate file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            this.value = '';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Only JPG, PNG, and GIF files are allowed');
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const profilePhoto = document.querySelector('.profile-photo');
            if (profilePhoto.tagName === 'IMG') {
                profilePhoto.src = e.target.result;
            } else {
                // Replace placeholder with image
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'profile-photo mb-3';
                img.alt = 'Profile Photo';
                profilePhoto.parentNode.replaceChild(img, profilePhoto);
            }
        };
        reader.readAsDataURL(file);
    }
});

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    }
});

// Real-time password validation
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const confirmPassword = document.getElementById('confirm_password');
    
    if (password.length < 6) {
        this.setCustomValidity('Password must be at least 6 characters long');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    }
    
    // Re-validate confirm password
    if (confirmPassword.value) {
        confirmPassword.dispatchEvent(new Event('input'));
    }
});

// Form submission validation
document.getElementById('profileForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('profile_photo');
    const file = fileInput.files[0];
    
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            e.preventDefault();
            alert('File size must be less than 5MB');
            return false;
        }
        
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            e.preventDefault();
            alert('Only JPG, PNG, and GIF files are allowed');
            return false;
        }
    }
});

document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match');
        return false;
    }
    
    if (newPassword.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long');
        return false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>