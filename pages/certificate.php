<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_login();

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if (!$course_id) {
    die('Invalid course.');
}

// Get user data
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get course data
$stmt = $pdo->prepare('SELECT c.*, u.name as instructor_name FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.id = ?');
$stmt->execute([$course_id]);
$course = $stmt->fetch();
if (!$course) {
    die('Course not found.');
}

// Check if user completed the course
$stmt = $pdo->prepare('SELECT * FROM course_completions WHERE user_id = ? AND course_id = ?');
$stmt->execute([$_SESSION['user_id'], $course_id]);
$completion = $stmt->fetch();
if (!$completion) {
    die('<h2 style="color:#dc3545;">You have not completed this course yet.</h2>');
}

$completion_date = date('F d, Y', strtotime($completion['completion_date']));
$certificate_id = strtoupper(substr(md5($user['id'] . $course_id . $completion['completion_date']), 0, 10));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Certificate of Completion - <?php echo htmlspecialchars($course['title']); ?></title>
    <style>
        body { background: #f4f6fa; }
        .certificate-container {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(102,126,234,0.13);
            padding: 40px 50px 30px 50px;
            text-align: center;
            position: relative;
        }
        .certificate-title {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }
        .certificate-line {
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            margin: 18px auto 28px auto;
            border-radius: 2px;
        }
        .certificate-body {
            font-size: 1.25em;
            color: #444;
            margin-bottom: 30px;
        }
        .certificate-name {
            font-size: 2em;
            font-weight: 600;
            color: #222;
            margin: 18px 0 10px 0;
        }
        .certificate-course {
            font-size: 1.3em;
            color: #667eea;
            font-weight: 500;
            margin-bottom: 18px;
        }
        .certificate-date {
            color: #888;
            font-size: 1.1em;
            margin-bottom: 18px;
        }
        .certificate-instructor {
            color: #333;
            font-size: 1.1em;
            margin-bottom: 30px;
        }
        .certificate-id {
            color: #aaa;
            font-size: 0.95em;
            margin-bottom: 10px;
        }
        .print-btn {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 32px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 18px;
            transition: background 0.2s;
        }
        .print-btn:hover {
            background: linear-gradient(90deg, #5a67d8 0%, #6b47b6 100%);
        }
        @media (max-width: 600px) {
            .certificate-container { padding: 18px 5vw; }
            .certificate-title { font-size: 1.5em; }
        }
    </style>
</head>
<body>
    <div class="certificate-container" id="certificateArea">
        <div class="certificate-title">Certificate of Completion</div>
        <div class="certificate-line"></div>
        <div class="certificate-body">
            This is to certify that
            <div class="certificate-name"><?php echo htmlspecialchars($user['name']); ?></div>
            has successfully completed the course
            <div class="certificate-course">“<?php echo htmlspecialchars($course['title']); ?>”</div>
            on <span class="certificate-date"><?php echo $completion_date; ?></span>
            <?php if (!empty($course['instructor_name'])): ?>
                <div class="certificate-instructor">Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></div>
            <?php endif; ?>
            <div class="certificate-id">Certificate ID: <?php echo $certificate_id; ?></div>
        </div>
        <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Download / Print Certificate</button>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
</body>
</html> 