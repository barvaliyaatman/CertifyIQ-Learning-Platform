<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Ensure user is logged in
require_login();

// Add after require_login()
// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get course ID from URL
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch course details with instructor information
$stmt = $pdo->prepare("
    SELECT c.*, u.name as instructor_name, u.profile_image as instructor_image, 
           u.expertise as instructor_expertise,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

// Check if course exists
if (!$course) {
    header("Location: courses.php");
    exit();
}

// Check if user is enrolled
$stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
$stmt->execute([$_SESSION['user_id'], $course_id]);
$is_enrolled = $stmt->fetch() !== false;

// Get course content (lessons/modules)
// Update the query to fetch sections and lessons
$stmt = $pdo->prepare("
    SELECT 
        s.id as section_id,
        s.title as section_title,
        s.order_number as section_order,
        l.id as lesson_id,
        l.title as lesson_title,
        l.type as lesson_type,
        l.description as lesson_description,
        l.duration as lesson_duration,
        l.file_path,
        l.video_url
    FROM sections s
    LEFT JOIN lessons l ON s.id = l.section_id
    WHERE s.course_id = ?
    ORDER BY s.order_number, l.order_number
");
$stmt->execute([$course_id]);
$content_data = $stmt->fetchAll();

// Organize content by sections
$organized_sections = [];
foreach ($content_data as $row) {
    if (!isset($organized_sections[$row['section_id']])) {
        $organized_sections[$row['section_id']] = [
            'id' => $row['section_id'],
            'title' => $row['section_title'],
            'order' => $row['section_order'],
            'lessons' => []
        ];
    }
    
    if ($row['lesson_id']) {
        $organized_sections[$row['section_id']]['lessons'][] = [
            'id' => $row['lesson_id'],
            'title' => $row['lesson_title'],
            'type' => $row['lesson_type'],
            'description' => $row['lesson_description'],
            'duration' => $row['lesson_duration'],
            'file_path' => $row['file_path'],
            'video_url' => $row['video_url']
        ];
    }
}

// Fetch completed lessons for this user in this course
$stmt = $pdo->prepare('SELECT lesson_id FROM lesson_progress WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$completed_lessons = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'lesson_id');

// Count total lessons in the course
$total_lessons = 0;
foreach ($organized_sections as $section) {
    $total_lessons += count($section['lessons']);
}
$total_completed = 0;
foreach ($organized_sections as $section) {
    foreach ($section['lessons'] as $lesson) {
        if (in_array($lesson['id'], $completed_lessons)) {
            $total_completed++;
        }
    }
}

$page_title = $course['title'];
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($course['title']); ?> - LMS</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/course.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
    .lesson-item {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(102,126,234,0.07);
        margin-bottom: 18px;
        padding: 18px 22px;
        transition: box-shadow 0.2s, transform 0.2s;
        border-left: 5px solid #667eea;
        display: flex;
        align-items: center;
    }
    .lesson-item:hover {
        box-shadow: 0 6px 24px rgba(102,126,234,0.13);
        transform: translateY(-2px) scale(1.01);
        border-left: 5px solid #5a67d8;
    }
    .lesson-header {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .lesson-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .lesson-number {
        font-weight: bold;
        color: #667eea;
        font-size: 1.1em;
    }
    .lesson-header h4 {
        margin: 0;
        font-size: 1.1em;
        font-weight: 600;
    }
    .lesson-completed-badge {
        background: #e6f9ed;
        color: #28a745;
        border-radius: 20px;
        padding: 3px 14px 3px 10px;
        font-size: 0.97em;
        font-weight: 600;
        margin-left: 12px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .lesson-completed-badge i {
        color: #28a745;
    }

    .completion-status-box {
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        text-align: center;
        border: 1px solid;
    }
    .completion-status-box i {
        font-size: 2.5em;
        margin-bottom: 10px;
    }
    .completion-status-box h4 {
        font-size: 1.3em;
        margin-bottom: 8px;
    }
    .completion-status-box.approved {
        background-color: #e6f9ed;
        border-color: #b7e4c7;
        color: #1d7a46;
    }
    .completion-status-box.pending {
        background-color: #fff3cd;
        border-color: #ffeeba;
        color: #856404;
    }
    .completion-status-box.rejected {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
    }
    .completion-status-box .btn {
        margin-top: 15px;
    }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="content">
            <div class="course-header">
                <div class="course-banner">
                    <img src="<?php echo !empty($course['image_url']) ? '../' . $course['image_url'] : '../assets/images/default-course.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($course['title']); ?>">
                </div>
                <div class="course-info">
                    <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                    <div class="course-meta">
                        <span><i class="fas fa-users"></i> <?php echo $course['student_count']; ?> students</span>
                        <span><i class="far fa-clock"></i> <?php echo htmlspecialchars($course['duration']); ?></span>
                        <span><i class="fas fa-signal"></i> <?php echo ucfirst(htmlspecialchars($course['level'])); ?></span>
                        <span><i class="fas fa-globe"></i> <?php echo htmlspecialchars($course['language'] ?? 'English'); ?></span>
                    </div>
                </div>
            </div>

            <div class="course-content">
                <div class="course-main">
                    <div class="course-description">
                        <h2>Course Description</h2>
                        <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                    </div>

                    <?php if ($is_enrolled): ?>
                        <div class="course-lessons">
                            <h2>Course Content</h2>
                            <div class="lesson-list">
                                <?php if (empty($organized_sections)): ?>
                                    <p class="no-content">No content available for this course yet.</p>
                                <?php else: ?>
                                    <?php foreach ($organized_sections as $sidx => $section): ?>
                                        <div class="section-container">
                                            <div class="section-header section-toggle" style="cursor:pointer;user-select:none;" data-section="section-<?php echo $section['id']; ?>">
                                                <h3 style="display:inline-block;">
                                                    <i class="fas fa-folder"></i>
                                                    <?php echo htmlspecialchars($section['title']); ?>
                                                </h3>
                                                <span class="lesson-count">
                                                    <?php echo count($section['lessons']); ?> lessons
                                                </span>
                                                <span class="dropdown-arrow" style="float:right;font-size:1.2em;margin-top:5px;">
                                                    <i class="fas fa-chevron-<?php echo $sidx === 0 ? 'down' : 'right'; ?>"></i>
                                                </span>
                                            </div>
                                            <div class="section-lessons" id="section-<?php echo $section['id']; ?>" style="display:<?php echo $sidx === 0 ? 'block' : 'none'; ?>;">
                                                <?php foreach ($section['lessons'] as $index => $lesson): ?>
                                                    <?php
                                                    $is_first = ($index === 0);
                                                    $prev_completed = $is_first || in_array($section['lessons'][$index-1]['id'], $completed_lessons);
                                                    $is_unlocked = $is_first || $prev_completed;
                                                    ?>
                                                    <div class="lesson-item" data-type="<?php echo $lesson['type']; ?>" 
                                                         data-video="<?php echo htmlspecialchars($lesson['video_url']); ?>"
                                                         data-file="<?php echo htmlspecialchars($lesson['file_path']); ?>">
                                                        <div class="lesson-header">
                                                            <div class="lesson-info">
                                                                <i class="fas fa-<?php echo getLessonIcon($lesson['type']); ?>"></i>
                                                                <span class="lesson-number"><?php echo ($index + 1); ?>.</span>
                                                                <h4>
                                                                    <?php if ($is_unlocked): ?>
                                                                        <a href="lesson.php?id=<?php echo $lesson['id']; ?>" style="color:inherit;text-decoration:underline;">
                                                                            <?php echo htmlspecialchars($lesson['title']); ?>
                                                                        </a>
                                                                        <?php if (in_array($lesson['id'], $completed_lessons)): ?>
                                                                            <span class="lesson-completed-badge"><i class="fas fa-check-circle"></i> Lesson Completed</span>
                                                                        <?php endif; ?>
                                                                    <?php else: ?>
                                                                        <span style="color:#aaa;cursor:not-allowed;"><i class="fas fa-lock"></i> <?php echo htmlspecialchars($lesson['title']); ?> <small>(Complete previous lesson to unlock)</small></span>
                                                                    <?php endif; ?>
                                                                </h4>
                                                            </div>
                                                            <?php if ($lesson['duration']): ?>
                                                                <span class="duration">
                                                                    <i class="far fa-clock"></i>
                                                                    <?php echo htmlspecialchars($lesson['duration']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if ($is_unlocked): ?>
                                                            <?php if ($lesson['type'] === 'video' && !empty($lesson['video_url'])): ?>
                                                                <div class="video-container">
                                                                    <iframe src="<?php echo htmlspecialchars($lesson['video_url']); ?>" 
                                                                            frameborder="0" allowfullscreen></iframe>
                                                                </div>
                                                            <?php elseif ($lesson['type'] === 'pdf' && !empty($lesson['file_path'])): ?>
                                                                <div class="pdf-container">
                                                                    <embed src="../<?php echo htmlspecialchars($lesson['file_path']); ?>" 
                                                                           type="application/pdf" width="100%" height="600px">
                                                                </div>
                                                            <?php elseif (!empty($lesson['description'])): ?>
                                                                <div class="lesson-description">
                                                                    <?php echo htmlspecialchars($lesson['description']); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="course-sidebar">
                    <div class="instructor-card">
                        <h2>Instructor</h2>
                        <div class="instructor-info">
                            <img src="<?php echo !empty($course['instructor_image']) ? '../' . $course['instructor_image'] : '../assets/images/default-avatar.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($course['instructor_name']); ?>">
                            <h3><?php echo htmlspecialchars($course['instructor_name']); ?></h3>
                            <p><?php echo htmlspecialchars($course['instructor_expertise']); ?></p>
                        </div>
                    </div>

                    <?php
                    $stmt = $pdo->prepare("
                        SELECT * FROM course_completions 
                        WHERE user_id = ? AND course_id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id'], $course_id]);
                    $completion = $stmt->fetch(PDO::FETCH_ASSOC);
                    ?>

                    <?php if (!$is_enrolled): ?>
                        <div class="enrollment-card">
                            <form action="enroll.php" method="POST">
                                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                                <button type="submit" class="btn enroll-btn">
                                    <i class="fas fa-graduation-cap"></i> Enroll Now
                                </button>
                            </form>
                        </div>
                    <?php elseif ($user['role'] === 'student'): ?>
                        <div class="completion-card">
                            <?php if ($completion): ?>
                                <?php if ($completion['status'] === 'approved'): ?>
                                    <div class="completion-status-box approved">
                                        <i class="fas fa-check-circle"></i>
                                        <h4>Course Completed!</h4>
                                        <p>Congratulations! Your completion has been approved.</p>
                                        <a href="certificate.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary" target="_blank">
                                            <i class="fas fa-certificate"></i> Download Certificate
                                        </a>
                                    </div>
                                <?php elseif ($completion['status'] === 'pending'): ?>
                                    <div class="completion-status-box pending">
                                        <i class="fas fa-hourglass-half"></i>
                                        <h4>Pending Approval</h4>
                                        <p>Your course completion is pending instructor approval.</p>
                                    </div>
                                <?php elseif ($completion['status'] === 'rejected'): ?>
                                    <div class="completion-status-box rejected">
                                        <i class="fas fa-times-circle"></i>
                                        <h4>Completion Rejected</h4>
                                        <?php if (!empty($completion['rejection_reason'])): ?>
                                            <p><strong>Reason:</strong> <?php echo htmlspecialchars($completion['rejection_reason']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ($total_lessons > 0 && $total_completed === $total_lessons): ?>
                                    <button id="completeBtn" class="btn complete-btn" data-course="<?php echo $course_id; ?>">
                                        <i class="fas fa-flag-checkered"></i> Mark as Completed
                                    </button>
                                <?php else: ?>
                                    <button class="btn complete-btn" disabled style="opacity:0.6;cursor:not-allowed;">
                                        <i class="fas fa-flag-checkered"></i> Mark as Completed
                                    </button>
                                    <div style="color:#dc3545;margin-top:8px;font-size:0.98em;">
                                        <i class="fas fa-info-circle"></i> Complete all lessons to mark the course as completed.
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>


    <script src="../assets/js/course.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.section-toggle').forEach(function(header) {
            header.addEventListener('click', function() {
                var sectionId = this.getAttribute('data-section');
                var lessonsDiv = document.getElementById(sectionId);
                var arrow = this.querySelector('.dropdown-arrow i');
                if (lessonsDiv.style.display === 'none') {
                    lessonsDiv.style.display = 'block';
                    arrow.classList.remove('fa-chevron-right');
                    arrow.classList.add('fa-chevron-down');
                } else {
                    lessonsDiv.style.display = 'none';
                    arrow.classList.remove('fa-chevron-down');
                    arrow.classList.add('fa-chevron-right');
                }
            });
        });
    });
    </script>
</body>
</html>

<?php
// Add this helper function after the requires
function getLessonIcon($type) {
    switch ($type) {
        case 'video':
            return 'video';
        case 'pdf':
            return 'file-pdf';
        case 'text':
            return 'file-alt';
        case 'quiz':
            return 'question-circle';
        default:
            return 'file';    }
        }
        ?>