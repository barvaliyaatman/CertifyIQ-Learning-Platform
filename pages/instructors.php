<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Ensure user is logged in
require_login();

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Check if user is student or instructor
if ($user['role'] !== 'student') {
    header("Location: " . ($user['role'] === 'instructor' ? 'instructor/dashboard.php' : 'admin/dashboard.php'));
    exit();
}

// Get all instructors with their course counts
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(DISTINCT c.id) as course_count,
           COUNT(DISTINCT e.user_id) as student_count
    FROM users u
    LEFT JOIN courses c ON u.id = c.instructor_id
    LEFT JOIN enrollments e ON c.id = e.course_id
    WHERE u.role = 'instructor'
    GROUP BY u.id
    ORDER BY course_count DESC
");
$stmt->execute();
$instructors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Instructors - Learning Management System</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/components/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="content">
            <div class="page-header">
                <h1>Our Instructors</h1>
            </div>

            <div class="instructors-grid">
                <?php foreach ($instructors as $instructor): ?>
                    <div class="instructor-card">
                        <div class="instructor-image">
                            <img src="<?php 
                                echo !empty($instructor['profile_image']) 
                                    ? '../' . $instructor['profile_image'] 
                                    : '../assets/images/default-avatar.jpg'; 
                            ?>" alt="<?php echo htmlspecialchars($instructor['name']); ?>">
                        </div>
                        <div class="instructor-content">
                            <h3><?php echo htmlspecialchars($instructor['name']); ?></h3>
                            <p class="instructor-title"><?php echo htmlspecialchars($instructor['title'] ?? 'Instructor'); ?></p>
                            
                            <?php if (!empty($instructor['bio'])): ?>
                                <p class="instructor-bio"><?php echo htmlspecialchars($instructor['bio']); ?></p>
                            <?php endif; ?>

                            <div class="instructor-stats">
                                <div class="stat">
                                    <i class="fas fa-book-reader"></i>
                                    <span><?php echo $instructor['course_count']; ?> Courses</span>
                                </div>
                                <div class="stat">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $instructor['student_count']; ?> Students</span>
                                </div>
                            </div>

                            <a href="instructor-profile.php?id=<?php echo $instructor['id']; ?>" class="view-profile-btn">
                                <i class="fas fa-user"></i> View Profile
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html>