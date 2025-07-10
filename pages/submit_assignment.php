<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Ensure user is logged in and is a student
require_login();
if ($_SESSION['user_role'] !== 'student') {
    header("Location: dashboard.php");
    exit();
}

$assignment_id = $_GET['id'] ?? null;
if (!$assignment_id) {
    header("Location: dashboard.php");
    exit();
}

// Get assignment details
$stmt = $pdo->prepare("
    SELECT a.*, c.title as course_title, c.id as course_id 
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE a.id = ?
");
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch();

// Check if assignment exists and student is enrolled
$stmt = $pdo->prepare("
    SELECT * FROM enrollments 
    WHERE user_id = ? AND course_id = ?
");
$stmt->execute([$_SESSION['user_id'], $assignment['course_id']]);
if (!$stmt->fetch()) {
    header("Location: dashboard.php");
    exit();
}

// Check existing submission
$stmt = $pdo->prepare("
    SELECT * FROM assignment_submissions 
    WHERE user_id = ? AND assignment_id = ?
");
$stmt->execute([$_SESSION['user_id'], $assignment_id]);
$submission = $stmt->fetch();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Assignment - LMS</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/assignment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="content">
            <div class="assignment-header">
                <h1>Assignment Submission</h1>
                <a href="course.php?id=<?php echo $assignment['course_id']; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Course
                </a>
            </div>

            <div class="assignment-details">
                <h2><?php echo htmlspecialchars($assignment['title']); ?></h2>
                <div class="assignment-meta">
                    <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($assignment['course_title']); ?></span>
                    <span><i class="fas fa-clock"></i> Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></span>
                    <span><i class="fas fa-star"></i> Max Score: <?php echo $assignment['max_score']; ?></span>
                </div>
                <div class="assignment-description">
                    <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                </div>
            </div>

            <div class="submission-form">
                <h3><?php echo $submission ? 'Update Submission' : 'Submit Assignment'; ?></h3>
                <form id="assignmentForm" enctype="multipart/form-data">
                    <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">
                    
                    <div class="form-group">
                        <label for="submission_text">Submission Text</label>
                        <textarea id="submission_text" name="submission_text" rows="6" 
                                placeholder="Enter your submission text here..."><?php echo htmlspecialchars($submission['submission_text'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="submission_file">Attachment (optional)</label>
                        <input type="file" id="submission_file" name="submission_file">
                        <small>Supported formats: PDF, DOC, DOCX, ZIP (Max size: 10MB)</small>
                    </div>

                    <?php if ($submission): ?>
                        <div class="submission-status">
                            <p>Submitted: <?php echo date('M d, Y H:i', strtotime($submission['created_at'])); ?></p>
                            <?php if ($submission['score']): ?>
                                <p>Score: <?php echo $submission['score']; ?> / <?php echo $assignment['max_score']; ?></p>
                            <?php endif; ?>
                            <?php if ($submission['feedback']): ?>
                                <div class="feedback">
                                    <h4>Instructor Feedback:</h4>
                                    <p><?php echo nl2br(htmlspecialchars($submission['feedback'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        <?php echo $submission ? 'Update Submission' : 'Submit Assignment'; ?>
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script src="../assets/js/submit-assignment.js"></script>
</body>
</html>