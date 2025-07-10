<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

// Get all students enrolled in instructor's courses
$stmt = $pdo->prepare("
    SELECT DISTINCT u.*, 
           c.title as course_title
    FROM users u
    JOIN enrollments e ON u.id = e.user_id
    JOIN courses c ON e.course_id = c.id
    WHERE c.instructor_id = ?
    ORDER BY u.name ASC
");
$stmt->execute([$_SESSION['user_id']]);
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Students - Instructor Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/instructor.css">
    <link rel="stylesheet" href="../../assets/css/instructor/students.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="instructor-container">
        <?php include '../../includes/instructor_sidebar.php'; ?>

        <main class="instructor-content">
            <header class="instructor-header">
                <h1>My Students</h1>
                <div class="search-box">
                    <input type="text" id="studentSearch" placeholder="Search students...">
                    <i class="fas fa-search"></i>
                </div>
            </header>

            <div class="students-table-container">
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th>Progress</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td class="student-info">
                                <img src="<?php 
                                    echo !empty($student['profile_image']) 
                                        ? '../../' . $student['profile_image']
                                        : '../../assets/images/default-avatar.jpg';
                                ?>" alt="Student Avatar" class="student-avatar">
                                <span><?php echo htmlspecialchars($student['name']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['course_title']); ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress" style="width: 60%"></div>
                                </div>
                                <span class="progress-text">60%</span>
                            </td>
                            <td class="actions">
                                <a href="view_student.php?id=<?php echo $student['id']; ?>" 
                                   class="action-btn view" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="mailto:<?php echo $student['email']; ?>" 
                                   class="action-btn email" title="Send Email">
                                    <i class="fas fa-envelope"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Search functionality
        document.getElementById('studentSearch').addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            document.querySelectorAll('.students-table tbody tr').forEach(row => {
                const name = row.querySelector('.student-info span').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const course = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                row.style.display = (name.includes(search) || email.includes(search) || course.includes(search))
                    ? '' 
                    : 'none';
            });
        });
    </script>
</body>
</html>