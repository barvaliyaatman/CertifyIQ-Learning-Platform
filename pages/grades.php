<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Ensure user is logged in
require_login();

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get student's grades for all assignments
$stmt = $pdo->prepare("
    SELECT 
        c.title as course_title,
        a.title as assignment_title,
        s.score,
        a.max_score,
        s.created_at as submission_date,
        s.feedback
    FROM assignment_submissions s
    JOIN assignments a ON s.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.user_id = ?
    ORDER BY c.title, s.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$grades = $stmt->fetchAll();

// Calculate overall performance
$total_score = 0;
$total_possible = 0;
$course_averages = [];

foreach ($grades as $grade) {
    $total_score += $grade['score'];
    $total_possible += $grade['max_score'];
    
    // Calculate course averages
    if (!isset($course_averages[$grade['course_title']])) {
        $course_averages[$grade['course_title']] = [
            'total' => 0,
            'possible' => 0
        ];
    }
    $course_averages[$grade['course_title']]['total'] += $grade['score'];
    $course_averages[$grade['course_title']]['possible'] += $grade['max_score'];
}

$overall_percentage = $total_possible > 0 ? ($total_score / $total_possible) * 100 : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Grades - Learning Management System</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/components/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="content">
            <div class="page-header">
                <h1>My Grades</h1>
            </div>

            <div class="grades-overview">
                <div class="overall-grade">
                    <h2>Overall Performance</h2>
                    <div class="grade-circle" style="--percentage: <?php echo $overall_percentage; ?>">
                        <span class="percentage"><?php echo number_format($overall_percentage, 1); ?>%</span>
                    </div>
                </div>

                <div class="course-averages">
                    <h2>Course Averages</h2>
                    <?php foreach ($course_averages as $course => $data): ?>
                        <?php $percentage = ($data['possible'] > 0) ? ($data['total'] / $data['possible']) * 100 : 0; ?>
                        <div class="course-grade">
                            <h3><?php echo htmlspecialchars($course); ?></h3>
                            <div class="progress-bar">
                                <div class="progress" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <span class="grade-value"><?php echo number_format($percentage, 1); ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grades-table">
                <h2>Detailed Grades</h2>
                <?php if (empty($grades)): ?>
                    <div class="no-grades">
                        <i class="fas fa-chart-bar"></i>
                        <p>No grades available yet.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Assignment</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Submission Date</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grades as $grade): ?>
                                <?php $percentage = ($grade['max_score'] > 0) ? ($grade['score'] / $grade['max_score']) * 100 : 0; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($grade['course_title']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['assignment_title']); ?></td>
                                    <td><?php echo $grade['score'] . '/' . $grade['max_score']; ?></td>
                                    <td>
                                        <div class="grade-percentage <?php echo $percentage >= 70 ? 'good' : ($percentage >= 50 ? 'average' : 'poor'); ?>">
                                            <?php echo number_format($percentage, 1); ?>%
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($grade['submission_date'])); ?></td>
                                    <td>
                                        <?php if (!empty($grade['feedback'])): ?>
                                            <button class="feedback-btn" onclick="showFeedback('<?php echo htmlspecialchars($grade['feedback']); ?>')">
                                                <i class="fas fa-comment"></i> View
                                            </button>
                                        <?php else: ?>
                                            <span class="no-feedback">No feedback</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Instructor Feedback</h2>
            <p id="feedbackText"></p>
        </div>
    </div>

    <script>
        function showFeedback(feedback) {
            const modal = document.getElementById('feedbackModal');
            const feedbackText = document.getElementById('feedbackText');
            feedbackText.textContent = feedback;
            modal.style.display = 'block';
        }

        document.querySelector('.close').onclick = function() {
            document.getElementById('feedbackModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('feedbackModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>