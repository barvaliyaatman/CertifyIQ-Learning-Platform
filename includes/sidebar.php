<div class="sidebar">
    <div class="sidebar-header">
        <a href="../index.php" class="logo">
            <img src="../assets/images/certifyiq-logo.png" alt="CertifyIQ Logo" style="width: 80%; height: 80%;">
            <span></span>
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <div class="user-info">
            <img src="<?php 
                if (!empty($user['profile_image'])) {
                    echo '../' . $user['profile_image'];
                } else {
                    echo '../assets/images/default-avatar.jpg';
                }
            ?>" alt="User Avatar">
            <h3><?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
        </div>
        <ul>
            <li>
                <a href="../index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-home"></i>
                    <span>Home Page</span>
                </a>
            </li>
            <li>
                <a href="../pages/profile.php" <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li>
                <a href="../pages/dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li>
                <a href="../pages/my-courses.php" <?php echo basename($_SERVER['PHP_SELF']) == 'my-courses.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-graduation-cap"></i>
                    <span>My Courses</span>
                </a>
            </li>
            <li>
                <a href="../pages/assignments.php" <?php echo basename($_SERVER['PHP_SELF']) == 'assignments.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-tasks"></i>
                    <span>Assignments</span>
                </a>
            </li>
            <li>
                <a href="../pages/grades.php" <?php echo basename($_SERVER['PHP_SELF']) == 'grades.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-star"></i>
                    <span>Grades</span>
                </a>
            </li>
            <li>
                <a href="../pages/calendar.php" <?php echo basename($_SERVER['PHP_SELF']) == 'calendar.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-calendar-alt"></i>
                    <span>Calendar</span>
                </a>
            </li>
            <li>
                <a href="../pages/messages.php" <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </li>
           
            <li>
                <a href="../pages/settings.php" <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li>
                <a href="../pages/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</div>