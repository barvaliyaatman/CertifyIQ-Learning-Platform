<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

// Handle course deletion
if (isset($_POST['delete_course'])) {
    $course_id = $_POST['course_id'];
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ? AND instructor_id = ?");
    $stmt->execute([$course_id, $_SESSION['user_id']]);
}

// Get instructor's courses
$stmt = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$courses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Courses - Instructor Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/instructor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="instructor-container">
    <?php include '../../includes/instructor_sidebar.php'; ?>

        <main class="instructor-content">
            <header class="instructor-header">
                <h1>Manage Courses</h1>
                <a href="add_course.php" class="add-course-btn">
                    <i class="fas fa-plus"></i> Add New Course
                </a>
            </header>

            <div class="courses-table-container">
                <table class="courses-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Students</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): 
                            // Get number of enrolled students
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
                            $stmt->execute([$course['id']]);
                            $enrolled = $stmt->fetch()['count'];
                        ?>
                        <tr>
                            <td>
                                <img src="../../<?php echo $course['image_url']; ?>" 
                                     alt="<?php echo $course['title']; ?>" 
                                     class="course-thumbnail">
                            </td>
                            <td><?php echo $course['title']; ?></td>
                            <td><?php echo $course['category']; ?></td>
                            <td>$<?php echo number_format($course['price'], 2); ?></td>
                            <td><?php echo $enrolled; ?></td>
                            <td>
                                <span class="status-badge <?php echo $course['status'] ?? 'active'; ?>">
                                    <?php echo ucfirst($course['status'] ?? 'active'); ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="edit_course.php?id=<?php echo $course['id']; ?>" 
                                   class="action-btn edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="view_students.php?course_id=<?php echo $course['id']; ?>" 
                                   class="action-btn view">
                                    <i class="fas fa-users"></i>
                                </a>
                                <form method="POST" class="delete-form" 
                                      onsubmit="return confirm('Are you sure you want to delete this course?');">
                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                    <button type="submit" name="delete_course" class="action-btn delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>