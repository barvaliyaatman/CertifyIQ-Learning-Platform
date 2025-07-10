<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

// Get course ID
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify course belongs to instructor
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    header("Location: manage_courses.php");
    exit();
}

// Get course content
$stmt = $pdo->prepare("
    SELECT * FROM lessons 
    WHERE course_id = ? 
    ORDER BY lesson_order ASC
");
$stmt->execute([$course_id]);
$lessons = $stmt->fetchAll();

// Get enrolled students
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, e.enrollment_date,
           (SELECT COUNT(*) FROM assignment_submissions s 
            JOIN assignments a ON s.assignment_id = a.id 
            WHERE a.course_id = ? AND s.user_id = u.id) as submissions_count
    FROM users u
    JOIN enrollments e ON u.id = e.user_id
    WHERE e.course_id = ?
    ORDER BY e.enrollment_date DESC
");
$stmt->execute([$course_id, $course_id]);
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Course Content - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="../../assets/css/instructor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="instructor-container">
        <?php include '../../includes/instructor_sidebar.php'; ?>
        
        <main class="instructor-content">
            <header class="content-header">
                <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                <button class="btn add-lesson-btn" onclick="showAddLessonModal()">
                    <i class="fas fa-plus"></i> Add New Lesson
                </button>
            </header>

            <div class="content-tabs">
                <button class="tab-btn active" onclick="showTab('content')">Course Content</button>
                <button class="tab-btn" onclick="showTab('students')">Enrolled Students</button>
            </div>

            <div id="content-tab" class="tab-content active">
                <div class="lessons-list">
                    <?php foreach ($lessons as $lesson): ?>
                    <div class="lesson-item" data-id="<?php echo $lesson['id']; ?>">
                        <div class="lesson-header">
                            <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                            <h3><?php echo htmlspecialchars($lesson['title']); ?></h3>
                            <div class="lesson-actions">
                                <button onclick="editLesson(<?php echo $lesson['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteLesson(<?php echo $lesson['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="lesson-content">
                            <p><?php echo htmlspecialchars($lesson['description']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="students-tab" class="tab-content">
                <div class="students-list">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Enrolled Date</th>
                                <th>Submissions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($student['enrollment_date'])); ?></td>
                                <td><?php echo $student['submissions_count']; ?></td>
                                <td>
                                    <a href="view_student_progress.php?course_id=<?php echo $course_id; ?>&student_id=<?php echo $student['id']; ?>" 
                                       class="btn view-btn">View Progress</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Lesson Modal -->
    <div id="lessonModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Lesson</h2>
            <form id="lessonForm">
                <input type="hidden" name="lesson_id" id="lessonId">
                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                
                <div class="form-group">
                    <label for="lessonTitle">Title</label>
                    <input type="text" id="lessonTitle" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="lessonDescription">Description</label>
                    <textarea id="lessonDescription" name="description" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="lessonContent">Content</label>
                    <textarea id="lessonContent" name="content" rows="8"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn save-btn">Save Lesson</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../assets/js/course_management.js"></script>
</body>
</html>