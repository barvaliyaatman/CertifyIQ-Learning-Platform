<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

// Get student ID from URL
if (!isset($_GET['id'])) {
    header("Location: students.php");
    exit();
}

$student_id = $_GET['id'];

// Get student details and their enrolled courses
$stmt = $pdo->prepare("
    SELECT u.*, 
           c.title as course_title,
           c.id as course_id
    FROM users u
    JOIN enrollments e ON u.id = e.user_id
    JOIN courses c ON e.course_id = c.id
    WHERE u.id = ? AND c.instructor_id = ?
");
$stmt->execute([$student_id, $_SESSION['user_id']]);
$enrollments = $stmt->fetchAll();

if (empty($enrollments)) {
    header("Location: students.php");
    exit();
}

$student = $enrollments[0]; // Basic student info
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Details - Instructor Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/instructor/students.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="instructor-container">
        <nav class="instructor-sidebar">
            <div class="instructor-brand">
                <h2>Instructor Panel</h2>
                <div class="user-info">
                    <img src="<?php 
                        if (!empty($user['profile_image'])) {
                            echo '../../' . $user['profile_image'];
                        } else {
                            echo '../../assets/images/default-avatar.jpg';
                        }
                    ?>" alt="User Avatar">
                    <h3><?php echo $_SESSION['user_name']; ?></h3>
                </div>
            </div>
            <ul>
                <li><a href="../../index.php">Back to Site</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="manage_courses.php">My Courses</a></li>
                <li class="active"><a href="students.php">My Students</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>

        <main class="instructor-content">
            <header class="instructor-header">
                <a href="students.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
                <h1>Student Details</h1>
            </header>

            <div class="student-profile">
                <div class="profile-header">
                    <img src="<?php 
                        echo !empty($student['profile_image']) 
                            ? '../../' . $student['profile_image']
                            : '../../assets/images/default-avatar.jpg';
                    ?>" alt="Student Avatar" class="profile-avatar">
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($student['name']); ?></h2>
                        <p class="email">
                            <i class="fas fa-envelope"></i>
                            <?php echo htmlspecialchars($student['email']); ?>
                        </p>
                        <a href="mailto:<?php echo $student['email']; ?>" class="contact-btn">
                            <i class="fas fa-paper-plane"></i> Contact Student
                        </a>
                    </div>
                </div>

                <div class="enrolled-courses">
                    <h3>Enrolled Courses</h3>
                    <div class="courses-grid">
                        <?php foreach ($enrollments as $enrollment): ?>
                        <div class="course-card">
                            <div class="course-info">
                                <h4><?php echo htmlspecialchars($enrollment['course_title']); ?></h4>
                                <p class="enrollment-status">
                                    <i class="fas fa-check-circle"></i>
                                    Currently Enrolled
                                </p>
                            </div>
                            <div class="course-progress">
                                <div class="progress-info">
                                    <span>Progress</span>
                                    <span>60%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress" style="width: 60%"></div>
                                </div>
                            </div>
                            <a href="course.php?id=<?php echo $enrollment['course_id']; ?>" class="view-course-btn">
                                View Course Details
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>