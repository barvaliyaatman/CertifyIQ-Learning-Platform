<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

require_login();

$lesson_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$lesson_id) {
    header('Location: courses.php');
    exit();
}

// Fetch lesson details and course info
$stmt = $pdo->prepare('
    SELECT l.*, c.title as course_title, c.id as course_id
    FROM lessons l
    JOIN courses c ON l.course_id = c.id
    WHERE l.id = ?
');
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch();

if (!$lesson) {
    header('Location: courses.php');
    exit();
}

// Check if lesson is already completed
$stmt = $pdo->prepare('SELECT * FROM lesson_progress WHERE lesson_id = ? AND user_id = ?');
$stmt->execute([$lesson_id, $_SESSION['user_id']]);
$progress = $stmt->fetch();

// Handle mark as complete
if (isset($_POST['mark_complete']) && !$progress) {
    $stmt = $pdo->prepare('INSERT INTO lesson_progress (lesson_id, user_id, completed, completed_at) VALUES (?, ?, 1, NOW())');
    $stmt->execute([$lesson_id, $_SESSION['user_id']]);
    header('Location: lesson.php?id=' . $lesson_id . '&completed=1');
    exit();
}

$page_title = $lesson['title'];
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($lesson['title']); ?> - Lesson</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/course.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .lesson-content-container { max-width: 900px; margin: 0 auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .lesson-title { font-size: 2em; font-weight: bold; margin-bottom: 10px; }
        .lesson-meta { color: #888; margin-bottom: 10px; }
        .lesson-description { margin-bottom: 25px; color: #444; background: #f8f9fa; border-left: 4px solid #667eea; padding: 18px 22px; border-radius: 8px; font-size: 1.13em; line-height: 1.7; box-shadow: 0 1px 4px rgba(102,126,234,0.04); }
        .video-container, .pdf-container { margin-bottom: 30px; }
        .video-container iframe, .video-container video { width: 100%; max-width: 800px; height: 400px; border-radius: 8px; }
        .pdf-container embed { width: 100%; height: 600px; border-radius: 8px; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>
    <main class="content">
        <div class="lesson-content-container">
            <a href="course.php?id=<?php echo $lesson['course_id']; ?>" style="color:#667eea;text-decoration:none;font-size:1em;">&larr; Back to Course: <?php echo htmlspecialchars($lesson['course_title']); ?></a>
            <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
            <div class="lesson-meta">
                <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($lesson['duration']); ?></span>
                <span style="margin-left:20px;"><i class="fas fa-tag"></i> <?php echo ucfirst($lesson['type']); ?></span>
            </div>
            <?php if ($lesson['type'] === 'video'): ?>
                <?php if (!empty($lesson['video_url'])): ?>
                    <div class="video-container">
                        <iframe src="<?php echo htmlspecialchars($lesson['video_url']); ?>" frameborder="0" allowfullscreen></iframe>
                    </div>
                <?php elseif (!empty($lesson['file_path'])): ?>
                    <div class="video-container">
                        <video controls>
                            <source src="../<?php echo htmlspecialchars($lesson['file_path']); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                <?php endif; ?>
            <?php elseif ($lesson['type'] === 'pdf' && !empty($lesson['file_path'])): ?>
                <div class="pdf-container">
                    <embed src="../<?php echo htmlspecialchars($lesson['file_path']); ?>" type="application/pdf">
                </div>
            <?php endif; ?>
            <?php if (!empty($lesson['description'])): ?>
                <div class="lesson-description">
                    <?php echo nl2br(htmlspecialchars($lesson['description'])); ?>
                </div>
            <?php endif; ?>
            <?php if (!$progress): ?>
                <form method="post" style="margin-top:30px;text-align:right;">
                    <button type="submit" name="mark_complete" class="btn btn-primary">
                        <i class="fas fa-check"></i> Mark as Complete
                    </button>
                </form>
            <?php else: ?>
                <div style="margin-top:30px;color:#28a745;font-weight:bold;text-align:right;">
                    <i class="fas fa-check-circle"></i> Lesson Completed
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html> 