<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $expertise = ($role === 'instructor') ? ($_POST['expertise'] ?? '') : '';
    
    // Handle file upload for all users
    $profile_image = '';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "../uploads/profiles/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $file_name = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            $profile_image = 'uploads/profiles/' . $file_name;
        }
    }
    
    if (empty($name) || empty($email) || empty($password) || empty($phone)) {
        $error = "All fields are required";
    } else {
        try {
            if (register_user($name, $email, $password, $role, $expertise, $profile_image, $phone)) {
                header("Location: login.php");
                exit();
            } else {
                $error = "Registration failed";
            }
        } catch (PDOException $e) {
            $error = "Email already exists";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Learning Management System</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h2>Create Account</h2>
            <?php if ($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="profile-upload">
                    <div class="preview-area">
                        <img id="imagePreview" src="../assets/images/default-avatar.jpg" alt="Profile Preview">
                    </div>
                    <div class="upload-controls">
                        <label for="profile_image" class="upload-btn">
                            <i class="fas fa-camera"></i> Choose Photo
                        </label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display: none;">
                        <small class="upload-info">Maximum file size: 2MB (JPG, PNG)</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i>
                        Full Name
                    </label>
                    <input type="text" id="name" name="name" required placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>
                    
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input type="password" id="password" name="password" required placeholder="Create a password">
                    <span class="toggle-password">
                        <i class="fas fa-eye"></i>
                    </span>
                    <div id="password-error" style="color: red; display: none; font-size: 0.9em; margin-top: 5px;"></div>
                </div>

                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i>
                        Phone Number
                    </label>
                    <input type="tel" id="phone" name="phone" required placeholder="Enter your phone number" pattern="[0-9\-\+\s]{10,15}">
                </div>

                <div class="form-group">
                    <label for="role">
                        <i class="fas fa-user-tag"></i>
                        Select Role
                    </label>
                    <select name="role" id="role" required>
                        <option value="student">Student</option>
                        <option value="instructor">Instructor</option>
                    </select>
                </div>

                <div class="form-group expertise-field" style="display: none;">
                    <label for="expertise">
                        <i class="fas fa-graduation-cap"></i>
                        Area of Expertise
                    </label>
                    <input type="text" id="expertise" name="expertise" placeholder="Enter your area of expertise">
                </div>

                <button type="submit">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>
            </form>
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script>

        
        // Password visibility toggle
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.querySelector('#password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Role change handler
        document.getElementById('role').addEventListener('change', function() {
            const expertiseField = document.querySelector('.expertise-field');
            expertiseField.style.display = this.value === 'instructor' ? 'block' : 'none';
        });

        // Image preview handler
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    this.value = '';
                    return;
                }

                if (!file.type.match('image.*')) {
                    alert('Please select an image file');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Password validation on form submit
        document.querySelector('form[method="POST"]').addEventListener('submit', function(e) {
            const passwordInput = document.getElementById('password');
            const errorDiv = document.getElementById('password-error');
            const password = passwordInput.value;
            let errorMsg = '';
            if (password.length < 8) {
                errorMsg = 'Password must be at least 8 characters long.';
            } else if (!/[A-Z]/.test(password)) {
                errorMsg = 'Password must contain at least one uppercase letter.';
            } else if (!/[^A-Za-z0-9]/.test(password)) {
                errorMsg = 'Password must contain at least one symbol.';
            }
            // Phone validation
            const phoneInput = document.getElementById('phone');
            if (phoneInput && !/^([0-9\-\+\s]{10,15})$/.test(phoneInput.value)) {
                errorMsg = 'Please enter a valid phone number (10-15 digits, numbers, spaces, + or -).';
                phoneInput.focus();
            }
            if (errorMsg) {
                errorDiv.textContent = errorMsg;
                errorDiv.style.display = 'block';
                passwordInput.focus();
                e.preventDefault();
            } else {
                errorDiv.style.display = 'none';
            }
        });
    </script>
</body>
</html>