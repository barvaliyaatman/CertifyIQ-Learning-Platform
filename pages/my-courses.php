<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Ensure user is logged in
require_login();

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get courses based on user role
if ($user['role'] === 'instructor') {
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(e.id) as student_count 
        FROM courses c 
        LEFT JOIN enrollments e ON c.id = e.course_id 
        WHERE c.instructor_id = ? 
        GROUP BY c.id 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as instructor_name, e.enrollment_date
        FROM courses c
        JOIN enrollments e ON c.id = e.course_id
        JOIN users u ON c.instructor_id = u.id
        WHERE e.user_id = ?
        ORDER BY e.enrollment_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
}
$courses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Courses - Learning Management System</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/components/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="content">
            <div class="page-header">
                <h1><?php echo $user['role'] === 'instructor' ? 'My Courses' : 'Enrolled Courses'; ?></h1>
                <?php if ($user['role'] === 'instructor'): ?>
                    <a href="instructor/create_course.php" class="btn create-btn">
                        <i class="fas fa-plus"></i> Create New Course
                    </a>
                <?php endif; ?>
            </div>

            <div class="courses-grid">
                <?php if (empty($courses)): ?>
                    <div class="no-courses">
                        <div class="no-courses-content">
                            <div class="no-courses-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h3><?php echo $user['role'] === 'instructor' ? 'No Courses Created' : 'No Courses Yet'; ?></h3>
                            <p><?php echo $user['role'] === 'instructor' ? 'Start creating amazing courses to share your knowledge with students!' : 'Start your learning journey by enrolling in your first course!'; ?></p>
                            <div class="no-courses-actions">
                                <?php if ($user['role'] === 'student'): ?>
                                    <a href="courses.php" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Browse Courses
                                    </a>
                                    <a href="instructors.php" class="btn btn-secondary">
                                        <i class="fas fa-chalkboard-teacher"></i> Meet Instructors
                                    </a>
                                <?php else: ?>
                                    <a href="instructor/add_course.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Create Your First Course
                                    </a>
                                    <a href="instructor/dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <div class="course-image">
                                <img src="<?php echo !empty($course['image_url']) ? '../' . $course['image_url'] : '../assets/images/default-course.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($course['title']); ?>">
                                <?php if ($user['role'] === 'instructor'): ?>
                                    <div class="course-overlay">
                                        <span class="student-count">
                                            <i class="fas fa-users"></i> <?php echo $course['student_count']; ?> students
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="course-content">
                                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                
                                <?php if ($user['role'] === 'student'): ?>
                                    <p class="instructor">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <?php echo htmlspecialchars($course['instructor_name']); ?>
                                    </p>
                                <?php endif; ?>

                                <p class="description"><?php echo nl2br(htmlspecialchars(substr($course['description'], 0, 100))); ?>...</p>

                                <div class="course-meta">
                                    <?php if (!empty($course['duration'])): ?>
                                        <span class="duration">
                                            <i class="far fa-clock"></i> <?php echo htmlspecialchars($course['duration']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($course['level'])): ?>
                                        <span class="level">
                                            <i class="fas fa-signal"></i> <?php echo htmlspecialchars($course['level']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="course-actions">
                                    <?php if ($user['role'] === 'instructor'): ?>
                                        <a href="instructor/edit_course.php?id=<?php echo $course['id']; ?>" class="btn edit-btn">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="instructor/manage_course.php?id=<?php echo $course['id']; ?>" class="btn manage-btn">
                                            <i class="fas fa-cog"></i> Manage
                                        </a>
                                    <?php else: ?>
                                        <a href="course.php?id=<?php echo $course['id']; ?>" class="btn view-btn">
                                            <i class="fas fa-eye"></i> View Course
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>