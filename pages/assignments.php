<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Ensure user is logged in
require_login();

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get assignments based on user role
if ($user['role'] === 'instructor') {
    // For instructors: get assignments they created
    $stmt = $pdo->prepare("
        SELECT a.*, c.title as course_title 
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        WHERE c.instructor_id = ?
        ORDER BY a.due_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    // For students: get assignments from enrolled courses
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            c.title as course_title,
            s.id as submission_id,
            s.score,
            s.created_at as submitted_at,
            s.feedback,
            a.max_score as max_points
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.user_id = ?
        WHERE c.id IN (SELECT course_id FROM enrollments WHERE user_id = ?)
        ORDER BY a.due_date DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
}
$assignments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assignments - Learning Management System</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="content">
            <div class="page-header">
                <h1>Assignments</h1>
                <?php if ($user['role'] === 'instructor'): ?>
                    <a href="instructor/create_assignment.php" class="btn create-btn">
                        <i class="fas fa-plus"></i> Create New Assignment
                    </a>
                <?php endif; ?>
            </div>

            <div class="assignments-grid">
                <?php if (empty($assignments)): ?>
                    <div class="no-assignments">
                        <div class="no-assignments-content">
                            <div class="no-assignments-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <h3><?php echo $user['role'] === 'instructor' ? 'No Assignments Created' : 'No Assignments Available'; ?></h3>
                            <p><?php echo $user['role'] === 'instructor' ? 'Create engaging assignments to help your students learn and grow!' : 'Check back later for new assignments from your enrolled courses.'; ?></p>
                            <div class="no-assignments-actions">
                                <?php if ($user['role'] === 'instructor'): ?>
                                    <a href="instructor/create_assignment.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Create Assignment
                                    </a>
                                    <a href="instructor/dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                                    </a>
                                <?php else: ?>
                                    <a href="my-courses.php" class="btn btn-primary">
                                        <i class="fas fa-book"></i> View My Courses
                                    </a>
                                    <a href="dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-home"></i> Go to Dashboard
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <div class="assignment-card">
                            <div class="assignment-header">
                                <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                <span class="course-name">
                                    <i class="fas fa-book"></i>
                                    <?php echo htmlspecialchars($assignment['course_title']); ?>
                                </span>
                            </div>

                            <div class="assignment-body">
                                <p><?php echo nl2br(htmlspecialchars(substr($assignment['description'], 0, 150))); ?>...</p>
                                
                                <div class="assignment-meta">
                                    <span class="due-date">
                                        <i class="far fa-clock"></i>
                                        Due: <?php echo date('M d, Y h:i A', strtotime($assignment['due_date'])); ?>
                                    </span>
                                    <span class="points">
                                        <i class="fas fa-star"></i>
                                        <?php echo $assignment['max_score']; ?> points
                                    </span>
                                </div>

                                <?php if ($user['role'] === 'instructor'): ?>
                                    <div class="assignment-actions">
                                        <a href="instructor/edit_assignment.php?id=<?php echo $assignment['id']; ?>" 
                                           class="btn edit-btn">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="instructor/view_submissions.php?id=<?php echo $assignment['id']; ?>" 
                                           class="btn view-btn">
                                            <i class="fas fa-eye"></i> View Submissions
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="assignment-status">
                                        <?php if ($assignment['submitted_at']): ?>
                                            <span class="submitted">
                                                <i class="fas fa-check-circle"></i>
                                                Submitted: <?php echo date('M d, Y h:i A', strtotime($assignment['submitted_at'])); ?>
                                            </span>
                                            <?php if (isset($assignment['score'])): ?>
                                                <span class="grade">
                                                    <i class="fas fa-star"></i>
                                                    Grade: <?php echo number_format($assignment['score'], 2); ?>/<?php echo number_format($assignment['max_score'], 2); ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="assignment-actions">
                                        <a href="submit_assignment.php?id=<?php echo $assignment['id']; ?>" 
                                           class="btn submit-btn">
                                            <?php echo $assignment['submitted_at'] ? 'View Submission' : 'Submit Assignment'; ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>