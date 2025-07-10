<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

// Get instructor's courses
$stmt = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$courses = $stmt->fetchAll();

// Get total students enrolled in instructor's courses
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT e.user_id) as total_students 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.id 
    WHERE c.instructor_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$total_students = $stmt->fetch()['total_students'];

// Add this after the require statements at the top
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Add these queries after the existing ones
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_assignments 
    FROM assignments a 
    JOIN courses c ON a.course_id = c.id 
    WHERE c.instructor_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$total_assignments = $stmt->fetch()['total_assignments'];

// Replace the pending submissions query
$stmt = $pdo->prepare("
    SELECT COUNT(*) as pending_submissions 
    FROM assignment_submissions s 
    JOIN assignments a ON s.assignment_id = a.id 
    JOIN courses c ON a.course_id = c.id 
    WHERE c.instructor_id = ? AND s.score IS NULL
");
$stmt->execute([$_SESSION['user_id']]);
$pending_submissions = $stmt->fetch()['pending_submissions'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Instructor Dashboard - LMS</title>
    <link rel="stylesheet" href="../../assets/css/instructor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="instructor-container">
        <?php include '../../includes/instructor_sidebar.php'; ?>

        <main class="instructor-content">
            <header class="instructor-header">
                <h1>Welcome, <?php echo $_SESSION['user_name']; ?>!</h1>
            </header>

            <div class="instructor-stats">
                <div class="stat-box">
                    <i class="fas fa-book-open"></i>
                    <h3>Total Courses</h3>
                    <p><?php echo count($courses); ?></p>
                </div>
                <div class="stat-box">
                    <i class="fas fa-users"></i>
                    <h3>Total Students</h3>
                    <p><?php echo $total_students; ?></p>
                </div>
                <div class="stat-box">
                    <i class="fas fa-tasks"></i>
                    <h3>Total Assignments</h3>
                    <p><?php echo $total_assignments; ?></p>
                </div>
                <div class="stat-box">
                    <i class="fas fa-clock"></i>
                    <h3>Pending Submissions</h3>
                    <p><?php echo $pending_submissions; ?></p>
                </div>
            </div>

            <div class="quick-actions">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                <div class="action-grid">
                    <a href="create_assignment.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="action-content">
                            <h3>Create Assignment</h3>
                            <p>Create new assignments for your courses</p>
                        </div>
                    </a>
                    <a href="add_course.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <div class="action-content">
                            <h3>Add New Course</h3>
                            <p>Create and publish a new course</p>
                        </div>
                    </a>
                    <a href="manage_courses.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-book-reader"></i>
                        </div>
                        <div class="action-content">
                            <h3>Manage Courses</h3>
                            <p>Edit and update your existing courses</p>
                        </div>
                    </a>
                    <a href="view_all_submissions.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="action-content">
                            <h3>Review Submissions</h3>
                            <p>Grade pending assignment submissions</p>
                        </div>
                    </a>
                </div>
            </div>

            <section class="recent-courses">
                <h2>Your Courses</h2>
                <div class="course-grid">
                    <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <img src="<?php echo BASE_URL . '/' . $course['image_url']; ?>" alt="<?php echo $course['title']; ?>">
                        <div class="course-info">
                            <h3><?php echo $course['title']; ?></h3>
                            <p><?php echo substr($course['description'], 0, 100) . '...'; ?></p>
                            <div class="course-actions">
                                <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn edit-btn" onclick="editCourse(<?php echo $course['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="manage_content.php?course_id=<?php echo $course['id']; ?>" class="btn manage-btn">
                                    <i class="fas fa-book"></i> Manage Content
                                </a>
                                <a href="view_students.php?course_id=<?php echo $course['id']; ?>" class="btn view-btn">
                                    <i class="fas fa-users"></i> View Students
                                </a>
                                <a href="manage_assignments.php?id=<?php echo $course['id']; ?>" class="btn assign-btn">
                                    <i class="fas fa-tasks"></i> Assignments
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
