<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

// Get instructor's courses for dropdown
$stmt = $pdo->prepare("SELECT id, title FROM courses WHERE instructor_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$courses = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $max_score = $_POST['max_score'];

    try {
        $stmt = $pdo->prepare("INSERT INTO assignments (course_id, title, description, due_date, max_score) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$course_id, $title, $description, $due_date, $max_score]);
        
        $_SESSION['success_message'] = "Assignment created successfully!";
        header("Location: manage_assignments.php?id=" . $course_id);
        exit();
    } catch (PDOException $e) {
        $error = "Error creating assignment: " . $e->getMessage();
    }
}

// Get user data for sidebar
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Assignment - Instructor Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/instructor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="instructor-container">
        <?php include '../../includes/instructor_sidebar.php'; ?>

        <main class="instructor-content">
            <header class="instructor-header">
                <h1>Create New Assignment</h1>
            </header>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" class="assignment-form">
                    <div class="form-group">
                        <label for="course_id">Select Course:</label>
                        <select name="course_id" id="course_id" required>
                            <option value="">Choose a course...</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="title">Assignment Title:</label>
                        <input type="text" name="title" id="title" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Assignment Description:</label>
                        <textarea name="description" id="description" rows="5" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="due_date">Due Date:</label>
                        <input type="datetime-local" name="due_date" id="due_date" required>
                    </div>

                    <div class="form-group">
                        <label for="max_score">Maximum Score:</label>
                        <input type="number" name="max_score" id="max_score" min="0" max="100" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn primary-btn">
                            <i class="fas fa-plus"></i> Create Assignment
                        </button>
                        <a href="dashboard.php" class="btn secondary-btn">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Set minimum date to today
        const dueDateInput = document.getElementById('due_date');
        const today = new Date();
        const minDate = today.toISOString().slice(0, 16);
        dueDateInput.min = minDate;
    </script>
</body>
</html>