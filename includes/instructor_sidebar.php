<?php
require_once __DIR__ . '/../config/config.php';

if (!function_exists('is_active_page')) {
    function is_active_page($page_name) {
        return basename($_SERVER['PHP_SELF']) == $page_name ? 'active' : '';
    }
}
?>
<nav class="instructor-sidebar">
    <div class="instructor-brand">
        <h2>Instructor Panel</h2>
        <div class="user-info">
            <img src="<?php 
                if (!empty($user['profile_image'])) {
                    echo BASE_URL . '/' . $user['profile_image'];
                } else {
                    echo BASE_URL . '/assets/images/default-avatar.jpg';
                }
            ?>" alt="User Avatar">
            <h3><?php echo $_SESSION['user_name']; ?></h3>
        </div>
    </div>
    <ul>
        <li><a href="../../index.php" class="<?php echo is_active_page('index.php'); ?>"><i class="fas fa-home"></i> Back to Site</a></li>
        <li><a href="dashboard.php" class="<?php echo is_active_page('dashboard.php'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="profile.php" class="<?php echo is_active_page('profile.php'); ?>"><i class="fas fa-user"></i> My Profile</a></li>
        <li><a href="manage_courses.php" class="<?php echo is_active_page('manage_courses.php'); ?>">
            <i class="fas fa-chalkboard"></i> Manage Courses
        </a></li>
        <li><a href="manage_assignments.php" class="<?php echo is_active_page('manage_assignments.php'); ?>">
            <i class="fas fa-file-alt"></i> Manage Assignments
        </a></li>
        <li><a href="manage_completions.php" class="<?php echo is_active_page('manage_completions.php'); ?>">
            <i class="fas fa-tasks"></i> Completion Approvals
        </a></li>
        <li><a href="students.php" class="<?php echo is_active_page('students.php'); ?>">
            <i class="fas fa-users"></i> My Students
        </a></li>
        <li><a href="<?php echo BASE_URL; ?>/pages/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>