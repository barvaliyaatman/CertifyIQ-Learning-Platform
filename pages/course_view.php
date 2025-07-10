<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/course_progress.php';

// Ensure user is logged in
require_login();

$course_id = $_GET['id'] ?? null;
if (!$course_id) {
    header('Location: dashboard.php');
    exit;
}

// Get course details and progress
$course = getCourseDetails($pdo, $course_id);
$progress = getCourseProgress($pdo, $_SESSION['user_id'], $course_id);
$sections = getCourseSections($pdo, $course_id);
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="course-container">
        <h1><?php echo htmlspecialchars($course['title']); ?></h1>
        
        <!-- Progress Bar -->
        <div class="progress-bar">
            <div class="progress" style="width: <?php echo $progress['percentage']; ?>%">
                <?php echo $progress['percentage']; ?>% Complete
            </div>
        </div>

        <!-- Course Sections -->
        <?php foreach ($sections as $section): ?>
            <div class="section">
                <h2><?php echo htmlspecialchars($section['title']); ?></h2>
                <?php foreach ($section['lessons'] as $lesson): ?>
                    <div class="lesson <?php echo isLessonLocked($progress, $lesson['id']) ? 'locked' : ''; ?>">
                        <a href="lesson.php?id=<?php echo $lesson['id']; ?>"
                           class="<?php echo isLessonCompleted($progress, $lesson['id']) ? 'completed' : ''; ?>">
                            <?php echo htmlspecialchars($lesson['title']); ?>
                        </a>
                        <?php if (isLessonCompleted($progress, $lesson['id'])): ?>
                            <span class="checkmark">✓</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <?php if ($progress['percentage'] === 100): ?>
            <div class="course-complete">
                <h2>🎉 Course Complete!</h2>
                <a href="download_certificate.php?course_id=<?php echo $course_id; ?>" 
                   class="btn btn-primary">Download Certificate</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/course.js"></script>
</body>
</html>