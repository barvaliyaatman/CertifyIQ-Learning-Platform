<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

$assignment_id = $_GET['id'] ?? null;
if (!$assignment_id) {
    header("Location: dashboard.php");
    exit();
}

// Get assignment details
$stmt = $pdo->prepare("
    SELECT a.*, c.title as course_title 
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND c.instructor_id = ?
");
$stmt->execute([$assignment_id, $_SESSION['user_id']]);
$assignment = $stmt->fetch();

if (!$assignment) {
    header("Location: dashboard.php");
    exit();
}

// Get all submissions for this assignment
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        u.name as student_name,
        u.email as student_email
    FROM assignment_submissions s
    JOIN users u ON s.user_id = u.id
    WHERE s.assignment_id = ?
    ORDER BY s.created_at DESC
");
$stmt->execute([$assignment_id]);
$submissions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Submissions - LMS</title>
    <link rel="stylesheet" href="../../assets/css/instructor.css">
    <link rel="stylesheet" href="../../assets/css/submissions.css">
    <link rel="stylesheet" href="../../assets/css/instructor/submissions.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="instructor-container">
        <?php include '../../includes/instructor_sidebar.php'; ?>

        <main class="instructor-content">
            <header class="content-header">
                <h1>Assignment Submissions</h1>
                <a href="manage_assignments.php?id=<?php echo $assignment['course_id']; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Assignments
                </a>
            </header>

            <div class="assignment-info">
                <h2><?php echo htmlspecialchars($assignment['title']); ?></h2>
                <div class="meta">
                    <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($assignment['course_title']); ?></span>
                    <span><i class="fas fa-calendar"></i> Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></span>
                    <span><i class="fas fa-users"></i> <?php echo count($submissions); ?> Submissions</span>
                </div>
            </div>

            <div class="submissions-list">
                <?php if (empty($submissions)): ?>
                    <div class="no-submissions">
                        <div class="no-submissions-content">
                            <div class="no-submissions-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h3>No Submissions Yet</h3>
                            <p>Students haven't submitted their assignments for this task yet. Check back later!</p>
                            <div class="no-submissions-actions">
                                <a href="manage_assignments.php?id=<?php echo $assignment['course_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-arrow-left"></i> Back to Assignments
                                </a>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($submissions as $submission): ?>
                        <div class="submission-card">
                            <div class="submission-header">
                                <div class="student-info">
                                    <h3><?php echo htmlspecialchars($submission['student_name']); ?></h3>
                                    <span><?php echo htmlspecialchars($submission['student_email']); ?></span>
                                </div>
                                <div class="submission-meta">
                                    <span>Submitted: <?php echo date('M d, Y H:i', strtotime($submission['created_at'])); ?></span>
                                    <?php if ($submission['updated_at'] != $submission['created_at']): ?>
                                        <span>(Updated: <?php echo date('M d, Y H:i', strtotime($submission['updated_at'])); ?>)</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="submission-content">
                                <?php if ($submission['submission_text']): ?>
                                    <div class="text-submission">
                                        <h4>Submission Text:</h4>
                                        <p><?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if ($submission['file_path']): ?>
                                    <div class="file-submission">
                                        <h4>Submitted File:</h4>
                                        <a href="../../<?php echo $submission['file_path']; ?>" target="_blank" class="file-link">
                                            <i class="fas fa-file"></i> View Submission File
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="grading-section">
                                <form class="grade-form" onsubmit="submitGrade(event, <?php echo $submission['id']; ?>)">
                                    <div class="form-group">
                                        <label for="score-<?php echo $submission['id']; ?>">Score (out of <?php echo $assignment['max_score']; ?>):</label>
                                        <input type="number" id="score-<?php echo $submission['id']; ?>" 
                                               name="score" value="<?php echo $submission['score']; ?>"
                                               min="0" max="<?php echo $assignment['max_score']; ?>" step="0.1">
                                    </div>
                                    <div class="form-group">
                                        <label for="feedback-<?php echo $submission['id']; ?>">Feedback:</label>
                                        <textarea id="feedback-<?php echo $submission['id']; ?>" 
                                                  name="feedback"><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                                    </div>
                                    <button type="submit" class="save-grade-btn">
                                        <i class="fas fa-save"></i> Save Grade
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        async function submitGrade(event, submissionId) {
            event.preventDefault();
            const form = event.target;
            const score = form.querySelector('input[name="score"]').value;
            const feedback = form.querySelector('textarea[name="feedback"]').value;

            try {
                const response = await fetch('../../api/grade_submission.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        submission_id: submissionId,
                        score: score,
                        feedback: feedback
                    })
                });

                const data = await response.json();
                if (data.success) {
                    alert('Grade saved successfully!');
                } else {
                    alert(data.message || 'Failed to save grade');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to save grade');
            }
        }
    </script>
</body>
</html>