<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

$course_id = $_GET['id'] ?? null;
if (!$course_id) {
    header("Location: dashboard.php");
    exit();
}

// Get course details
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

// Get assignments
$stmt = $pdo->prepare("SELECT * FROM assignments WHERE course_id = ? ORDER BY due_date");
$stmt->execute([$course_id]);
$assignments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Assignments</title>
    <link rel="stylesheet" href="../../assets/css/instructor.css">
    <link rel="stylesheet" href="../../assets/css/assignment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="instructor-container">
        <?php include '../../includes/instructor_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="instructor-content">
            <header class="content-header">
                <h1>Manage Assignments: <?php echo htmlspecialchars($course['title']); ?></h1>
                <a href="manage_courses.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Courses
                </a>
            </header>

            <div class="content-actions">
                <button class="add-btn" onclick="addAssignment()">
                    <i class="fas fa-plus"></i>
                    Add Assignment
                </button>
            </div>

            <div class="assignments-list">
                <?php if (empty($assignments)): ?>
                    <div class="no-assignments">
                        <i class="fas fa-tasks"></i>
                        <p>No assignments created yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <div class="assignment-card" data-assignment-id="<?php echo $assignment['id']; ?>">
                            <div class="assignment-header">
                                <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                <div class="assignment-actions">
                                    <button onclick="editAssignment(<?php echo $assignment['id']; ?>)" class="btn-edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteAssignment(<?php echo $assignment['id']; ?>)" class="btn-delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="assignment-details">
                                <p><?php echo htmlspecialchars($assignment['description']); ?></p>
                                <div class="assignment-meta">
                                    <span class="due-date">
                                        <i class="fas fa-clock"></i>
                                        Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>
                                    </span>
                                    <span class="max-score">
                                        <i class="fas fa-star"></i>
                                        Max Score: <?php echo $assignment['max_score']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Assignment Modal -->
    <div id="assignmentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('assignmentModal')">&times;</span>
            <h2>Add Assignment</h2>
            <form id="assignmentForm">
                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                <input type="text" name="title" placeholder="Assignment Title" required>
                <textarea name="description" placeholder="Assignment Description" required></textarea>
                <input type="number" name="max_score" placeholder="Maximum Score" min="0" max="100" required>
                <input type="datetime-local" name="due_date" required>
                <div class="form-actions">
                    <button type="button" onclick="closeModal('assignmentModal')" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function deleteAssignment(assignmentId) {
            if (confirm('Are you sure you want to delete this assignment?')) {
                fetch(`delete_assignment.php?id=${assignmentId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the assignment card from DOM
                        const assignmentCard = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
                        assignmentCard.remove();
                        alert('Assignment deleted successfully');
                    } else {
                        alert('Error deleting assignment');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting assignment');
                });
            }
        }

        function addAssignment() {
            document.getElementById('assignmentModal').style.display = 'block';
            document.getElementById('assignmentForm').reset();
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Handle form submission
        document.getElementById('assignmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('save_assignment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload to show new assignment
                } else {
                    alert('Error saving assignment: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving assignment');
            });
        });

        // Set minimum date to today for the due date input
        const dueDateInput = document.querySelector('input[name="due_date"]');
        const today = new Date();
        const minDate = today.toISOString().slice(0, 16);
        dueDateInput.min = minDate;
    </script>
</body>
</html>