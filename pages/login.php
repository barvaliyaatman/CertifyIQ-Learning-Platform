<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "All fields are required";
    } else {
        if (login_user($email, $password)) {
            // Check user role and redirect accordingly
            if ($_SESSION['user_role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } elseif ($_SESSION['user_role'] === 'instructor') {
                header("Location: instructor/dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Learning Management System</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h2>Welcome Back</h2>
            <?php if ($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                    <span class="toggle-password">
                        <i class="fas fa-eye"></i>
                    </span>
                    <div id="password-error" style="color: red; display: none; font-size: 0.9em; margin-top: 5px;"></div>
                </div>
                <button type="submit">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            </form>
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>

    <script>
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

        // Password validation on form submit
        document.getElementById('loginForm').addEventListener('submit', function(e) {
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