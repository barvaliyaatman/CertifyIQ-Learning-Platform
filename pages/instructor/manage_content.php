<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) {
    header("Location: dashboard.php");
    exit();
}

// Get user data for sidebar
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get course details
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

// Fetch all sections and their lessons for the course in one go
$stmt = $pdo->prepare("
    SELECT 
        s.id as section_id, s.title as section_title, s.order_number as section_order,
        l.id as lesson_id, l.title as lesson_title, l.type as lesson_type, l.order_number as lesson_order
    FROM sections s
    LEFT JOIN lessons l ON s.id = l.section_id
    WHERE s.course_id = ?
    ORDER BY s.order_number, l.order_number
");
$stmt->execute([$course_id]);
$content_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize content into a nested array
$sections = [];
foreach ($content_data as $row) {
    if (!isset($sections[$row['section_id']])) {
        $sections[$row['section_id']] = [
            'id' => $row['section_id'],
            'title' => $row['section_title'],
            'order' => $row['section_order'],
            'lessons' => []
        ];
    }
    if ($row['lesson_id']) {
        $sections[$row['section_id']]['lessons'][] = [
            'id' => $row['lesson_id'],
            'title' => $row['lesson_title'],
            'type' => $row['lesson_type'],
            'order' => $row['lesson_order']
        ];
    }
}

// Helper function for lesson icons
function getLessonIcon($type) {
    switch ($type) {
        case 'video': return 'video';
        case 'pdf': return 'file-pdf';
        case 'text': return 'file-alt';
        case 'quiz': return 'question-circle';
        default: return 'file';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Course Content</title>
    <link rel="stylesheet" href="../../assets/css/instructor.css">
    <link rel="stylesheet" href="../../assets/css/instructor/manage_content.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="instructor-container">
        <?php include '../../includes/instructor_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="instructor-content">
            <header class="content-header">
                <h1>Manage Content: <?php echo htmlspecialchars($course['title']); ?></h1>
                <a href="manage_courses.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Courses
                </a>
            </header>

            <div class="content-actions">
                <button class="add-btn" onclick="addSection()">
                    <i class="fas fa-plus"></i>
                    Add Section
                </button>
                <button class="add-btn" onclick="addLesson()">
                    <i class="fas fa-plus"></i>
                    Add Lesson
                </button>
            </div>

            <div id="course-content" class="sortable-content">
                <div class="course-sections">
                    <div class="course-info">
                        <div class="course-header">
                            <h2><?php echo htmlspecialchars($course['title']); ?></h2>
                            <p class="course-description"><?php echo htmlspecialchars($course['description'] ?? ''); ?></p>
                        </div>
                    </div>

                    <div class="sections-list">
                        <?php if (empty($sections)): ?>
                            <div class="no-sections">
                                <p>No sections added yet. Click "Add Section" to create your first section.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($sections as $section): ?>
                                <div class="section-container" data-id="<?php echo $section['id']; ?>">
                                    <div class="section-header">
                                        <h3>
                                            <i class="fas fa-grip-vertical drag-handle"></i>
                                            <span class="section-number">Section <?php echo $section['order']; ?>:</span>
                                            <?php echo htmlspecialchars($section['title']); ?>
                                        </h3>
                                        <div class="section-actions">
                                            <button class="btn-edit" onclick="editSection(<?php echo $section['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-delete" onclick="deleteSection(<?php echo $section['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="section-lessons">
                                        <?php if (empty($section['lessons'])): ?>
                                            <div class="no-lessons">
                                                <p>No lessons in this section yet.</p>
                                            </div>
                                        <?php else:
                                            foreach ($section['lessons'] as $lesson): ?>
                                                <div class="lesson-item" data-id="<?php echo $lesson['id']; ?>">
                                                    <div class="lesson-header">
                                                        <span class="lesson-title">
                                                            <i class="fas fa-<?php echo getLessonIcon($lesson['type']); ?>"></i>
                                                            <span class="lesson-number"><?php echo $lesson['order']; ?>.</span>
                                                            <?php echo htmlspecialchars($lesson['title']); ?>
                                                        </span>
                                                        <div class="lesson-actions">
                                                            <button class="btn-edit" onclick="editLesson(<?php echo $lesson['id']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn-delete" onclick="deleteLesson(<?php echo $lesson['id']; ?>)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach;
                                        endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Section Modal -->
    <div id="sectionModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('sectionModal')">&times;</span>
            <h2>Add Section</h2>
            <form id="sectionForm">
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">
                <input type="text" name="title" placeholder="Section Title" required>
                <input type="number" name="order" placeholder="Order Number">
                <button type="submit">Save Section</button>
            </form>
        </div>
    </div>

    <!-- Add Lesson Modal -->
    <div id="lessonModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('lessonModal')">&times;</span>
            <h2>Add Lesson</h2>
            <form id="lessonForm" enctype="multipart/form-data">
                <!-- Add this hidden input for course_id -->
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">
                
                <select name="section_id" required>
                    <option value="">Select Section</option>
                    <?php foreach ($sections as $section): ?>
                        <option value="<?php echo $section['id']; ?>">
                            <?php echo htmlspecialchars($section['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" name="title" placeholder="Lesson Title" required>
                
                <select name="type" required>
                    <option value="">Select Content Type</option>
                    <option value="video">Video</option>
                    <option value="pdf">PDF Document</option>
                    <option value="text">Text Content</option>
                    <option value="quiz">Quiz</option>
                </select>
                
                <textarea name="content" placeholder="Lesson Content"></textarea>
                
                <div class="file-input">
                    <input type="file" name="file" id="lessonFile">
                    <small>Supported formats: MP4, PDF, DOC, DOCX</small>
                </div>
                
                <input type="number" name="order" placeholder="Order Number" min="1">
                
                <div class="form-actions">
                    <button type="button" onclick="closeModal('lessonModal')" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save Lesson</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Sortable.js library -->
    <!-- At the bottom of the file, before </body> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <script src="../../assets/js/course-content.js"></script>
    </body>
    </html>