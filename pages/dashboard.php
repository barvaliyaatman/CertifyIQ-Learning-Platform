<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Ensure user is logged in
require_login();

// Get user data first
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Check if user has taken the placement quiz
$stmt = $pdo->prepare("SELECT * FROM placement_quiz_attempts WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$quizAttempt = $stmt->fetch();
$showQuizPopup = !$quizAttempt && $user['role'] === 'student';

// Ensure user is a student
if ($user['role'] !== 'student') {
    header("Location: " . ($user['role'] === 'instructor' ? 'instructor/dashboard.php' : 'admin/dashboard.php'));
    exit();
}

// Get user's enrolled courses with instructor information
// Replace the existing enrolled courses query with this updated version
$stmt = $pdo->prepare("
    SELECT c.*, u.name as instructor_name,
           (SELECT COUNT(*) FROM course_completions 
            WHERE course_id = c.id AND user_id = ?) as is_completed
    FROM courses c 
    JOIN enrollments e ON c.id = e.course_id 
    JOIN users u ON c.instructor_id = u.id
    WHERE e.user_id = ?
    ORDER BY e.enrollment_date DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$enrolled_courses = $stmt->fetchAll();

// Update the completed courses calculation
$completed_courses = array_filter($enrolled_courses, function($course) {
    return $course['is_completed'] > 0;
});
$completed_count = count($completed_courses);

// Get assignments count for instructors
$total_assignments = 0;
if ($_SESSION['user_role'] === 'instructor') {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM assignments a 
        JOIN courses c ON a.course_id = c.id 
        WHERE c.instructor_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $total_assignments = $stmt->fetch()['total'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Learning Management System</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/components/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .quiz-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
        }
        .quiz-modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .start-quiz-btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Add this before the dashboard-container -->
    <?php if ($showQuizPopup): ?>
    <div id="quizModal" class="quiz-modal" style="display: block;">
        <div class="quiz-modal-content">
            <h2>Welcome to CertifyIQ!</h2>
            <p>Take our placement quiz to unlock exclusive discounts on all courses.</p>
            <p>This is a one-time opportunity to save on your learning journey!</p>
            <a href="../pages/placement-quiz.php" class="start-quiz-btn">Start Quiz</a>
        </div>
    </div>
    <?php endif; ?>

    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="content">
            <!-- <header class="dashboard-header">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
                <div class="user-profile">
                    <img src="<?php 
                        echo !empty($user['profile_image']) 
                            ? '../' . $user['profile_image'] 
                            : '../assets/images/default-avatar.jpg'; 
                    ?>" alt="Profile">
                </div>
            </header> -->
            
            <div class="stats-container">
                <div class="stat-card">
                    <i class="fas fa-book-reader"></i>
                    <div class="stat-info">
                        <h3>Enrolled Courses</h3>
                        <p><?php echo count($enrolled_courses); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle"></i>
                    <div class="stat-info">
                        <h3>Completed</h3>
                        <p><?php echo $completed_count; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-spinner"></i>
                    <div class="stat-info">
                        <h3>In Progress</h3>
                        <p><?php echo count($enrolled_courses) - $completed_count; ?></p>
                    </div>
                </div>
            </div>

            <section class="my-courses">
                <div class="section-header">
                    <h2><i class="fas fa-graduation-cap"></i> My Courses</h2>
                    <a href="courses.php" class="view-all">View All Courses</a>
                </div>
                
                <div class="course-grid">
                    <?php if (empty($enrolled_courses)): ?>
                        <div class="no-courses">
                            <div class="no-courses-content">
                                <div class="no-courses-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <h3>No Courses Yet</h3>
                                <p>Start your learning journey by enrolling in your first course!</p>
                                <div class="no-courses-actions">
                                    <a href="courses.php" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Browse Courses
                                    </a>
                                    <a href="instructors.php" class="btn btn-secondary">
                                        <i class="fas fa-chalkboard-teacher"></i> Meet Instructors
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($enrolled_courses as $course): ?>
                            <div class="course-card">
                                <div class="course-image">
                                    <img src="<?php 
                                        echo !empty($course['image_url']) 
                                            ? '../' . ltrim($course['image_url'], '/') 
                                            : '../assets/images/default-course.jpg'; 
                                    ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                </div>
                                <div class="course-content">
                                    <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                    <p class="instructor">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <?php echo htmlspecialchars($course['instructor_name']); ?>
                                    </p>
                                    <p class="description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                                    <a href="course.php?id=<?php echo $course['id']; ?>" class="btn continue-btn">
                                        <i class="fas fa-play-circle"></i> Continue Learning
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
