<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../config/config.php';

// Ensure user is an instructor
require_login();
if ($_SESSION['user_role'] !== 'instructor') {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit();
}

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) {
    header("Location: manage_courses.php");
    exit();
}

// Get instructor user data for the sidebar
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch course details and verify ownership
$stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error_message'] = "Course not found or you don't have permission to view its students.";
    header("Location: manage_courses.php");
    exit();
}
$course_title = $course['title'];

// Fetch all students enrolled in this course
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, u.profile_image, e.enrollment_date
    FROM users u
    JOIN enrollments e ON u.id = e.user_id
    WHERE e.course_id = ?
    ORDER BY u.name ASC
");
$stmt->execute([$course_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <title>View Students: <?php echo htmlspecialchars($course_title); ?></title>
    <link rel="stylesheet" href="../../assets/css/instructor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .student-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        .student-info {
            display: flex;
            align-items: center;
        }
        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .student-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.3s ease;
        }
        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px burdensome-pixels rgba(0,0,0,0.1);
        }
        .student-card .student-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #eef;
        }
        .student-card .student-name {
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .student-card .student-email {
            font-size: 0.95em;
            color: #777;
            margin-bottom: 15px;
        }
        .student-card .enrollment-date {
            font-size: 0.85em;
            color: #888;
            margin-bottom: 20px;
        }
        .student-card .actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="instructor-container">
        <?php include '../../includes/instructor_sidebar.php'; ?>

        <main class="instructor-content">
            <header class="instructor-header">
                <h1><i class="fas fa-users"></i> Students in "<?php echo htmlspecialchars($course_title); ?>"</h1>
                <a href="manage_courses.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Courses</a>
            </header>

            <div class="card">
                <div class="card-body">
                    <?php if (empty($students)): ?>
                        <div class="no-content" style="text-align: center; padding: 40px;">
                            <i class="fas fa-user-slash" style="font-size: 3em; color: #ccc; margin-bottom: 15px;"></i>
                            <p>No students are enrolled in this course yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="students-grid">
                            <?php foreach ($students as $student): ?>
                                <div class="student-card">
                                    <img src="<?php echo BASE_URL . '/' . ($student['profile_image'] ?: 'assets/images/default-avatar.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($student['name']); ?>" 
                                         class="student-avatar">
                                    <h3 class="student-name"><?php echo htmlspecialchars($student['name']); ?></h3>
                                    <p class="student-email"><?php echo htmlspecialchars($student['email']); ?></p>
                                    <p class="enrollment-date">
                                        <i class="fas fa-calendar-alt"></i> Enrolled: <?php echo date('M d, Y', strtotime($student['enrollment_date'])); ?>
                                    </p>
                                    <div class="actions">
                                        <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>" class="action-btn email" title="Email Student">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                        <!-- Future functionality:
                                        <a href="view_student.php?id=<?php echo $student['id']; ?>" class="action-btn view" title="View Progress">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                        -->
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 