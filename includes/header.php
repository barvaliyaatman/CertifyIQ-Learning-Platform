<?php
// No need for session_start() here as it's already in index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - LMS' : 'LMS'; ?></title>
    <link rel="stylesheet" href="/lms0.1/assets/css/style.css">
    <link rel="stylesheet" href="/lms0.1/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <nav class="main-nav">
        <div class="nav-brand">
            <a href="/lms0.1/index.php">
                <!-- <img src="/lms0.1/assets/images/certifyiq-logo.png" alt="CertifyIQ Logo" class="brand-logo"> -->
                <span></span>
            </a>
        </div>
        <div class="nav-links">
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'instructor'): ?>
                <a href="/lms0.1/pages/instructor/dashboard.php"><i class="fas fa-book"></i> Courses</a>
            <?php else: ?>
                <a href="/lms0.1/pages/courses.php"><i class="fas fa-book"></i> Courses</a>
            <?php endif; ?>
            <a href="/lms0.1/pages/instructors.php"><i class="fas fa-chalkboard-teacher"></i> Instructors</a>
            <?php if (is_logged_in()): ?>
                <a href="/lms0.1/pages/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="/lms0.1/pages/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="/lms0.1/pages/login.php" class="login-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="/lms0.1/pages/register.php" class="register-btn"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
        </div>
    </nav>