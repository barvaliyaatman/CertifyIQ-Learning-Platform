<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

// Get all submissions for instructor's courses
// Add filter options
$course_filter = $_GET['course'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Modify the query to include filters
$query = "
    SELECT 
        s.*,
        a.title as assignment_title,
        c.title as course_title,
        u.name as student_name,
        u.email as student_email,
        a.max_score,
        c.id as course_id
    FROM assignment_submissions s
    JOIN assignments a ON s.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    JOIN users u ON s.user_id = u.id
    WHERE c.instructor_id = ?
";

if ($course_filter) {
    $query .= " AND c.id = ?";
}
if ($status_filter === 'graded') {
    $query .= " AND s.score IS NOT NULL";
} elseif ($status_filter === 'ungraded') {
    $query .= " AND s.score IS NULL";
}
$query .= " ORDER BY s.created_at DESC";

// Get courses for filter dropdown
$stmt = $pdo->prepare("SELECT id, title FROM courses WHERE instructor_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$courses = $stmt->fetchAll();

// Execute submissions query with filters
$params = [$_SESSION['user_id']];
if ($course_filter) {
    $params[] = $course_filter;
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$submissions = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
    <title>All Submissions - LMS</title>
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
                <h1>All Assignment Submissions</h1>
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </header>

            <div class="submissions-overview">
                <div class="submissions-stats">
                    <div class="stat-card">
                        <h3><?php echo count($submissions); ?></h3>
                        <p>Total Submissions</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($submissions, fn($s) => isset($s['score']))); ?></h3>
                        <p>Graded</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($submissions, fn($s) => !isset($s['score']))); ?></h3>
                        <p>Pending</p>
                    </div>
                </div>

                <div class="filter-section">
                    <select onchange="window.location.href='?course='+this.value+'&status=<?php echo $status_filter; ?>'">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" 
                                <?php echo $course_filter == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select onchange="window.location.href='?course=<?php echo $course_filter; ?>&status='+this.value">
                        <option value="">All Status</option>
                        <option value="graded" <?php echo $status_filter === 'graded' ? 'selected' : ''; ?>>Graded</option>
                        <option value="ungraded" <?php echo $status_filter === 'ungraded' ? 'selected' : ''; ?>>Ungraded</option>
                    </select>
                </div>
            </div>

            <div class="submissions-list">
                <?php if (empty($submissions)): ?>
                    <div class="no-submissions">
                        <i class="fas fa-inbox"></i>
                        <p>No submissions yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($submissions as $submission): ?>
                        <div class="submission-card">
                            <div class="submission-header">
                                <div class="student-info">
                                    <h3><?php echo htmlspecialchars($submission['student_name']); ?></h3>
                                    <span><?php echo htmlspecialchars($submission['student_email']); ?></span>
                                </div>
                                <div class="course-info">
                                    <h4><?php echo htmlspecialchars($submission['course_title']); ?></h4>
                                    <p><?php echo htmlspecialchars($submission['assignment_title']); ?></p>
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
                                        <label for="score-<?php echo $submission['id']; ?>">Score (out of <?php echo $submission['max_score']; ?>):</label>
                                        <input type="number" id="score-<?php echo $submission['id']; ?>" 
                                               name="score" value="<?php echo $submission['score']; ?>"
                                               min="0" max="<?php echo $submission['max_score']; ?>" step="0.1">
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

    <script src="../../assets/js/grade-submission.js"></script>
</body>
</html>