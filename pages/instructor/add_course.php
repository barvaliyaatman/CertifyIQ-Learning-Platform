<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $category = $_POST['category'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $level = $_POST['level'] ?? '';
    
    // Handle file upload
    $image_url = '';
    if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] == 0) {
        $target_dir = "../../uploads/courses/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['course_image']['name'], PATHINFO_EXTENSION));
        $file_name = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES['course_image']['tmp_name'], $target_file)) {
            $image_url = 'uploads/courses/' . $file_name;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO courses (title, description, price, category, duration, level, image_url, instructor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $description, $price, $category, $duration, $level, $image_url, $_SESSION['user_id']])) {
            $success = "Course added successfully!";
        } else {
            $error = "Failed to add course";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Course - Instructor Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/instructor.css">
</head>
<body>
    <div class="instructor-container">
        <?php include '../../includes/instructor_sidebar.php'; ?>

        <main class="instructor-content">
            <header class="instructor-header">
                <h1>Add New Course</h1>
            </header>

            <?php if ($success): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" class="course-form">
                    <div class="form-group">
                        <label>Course Title</label>
                        <input type="text" name="title" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="6" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Price ($)</label>
                        <input type="number" name="price" min="0" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <option value="Programming">Programming</option>
                            <option value="Design">Design</option>
                            <option value="Business">Business</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Music">Music</option>
                            <option value="Photography">Photography</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Duration</label>
                            <input type="text" name="duration" placeholder="e.g., 8 weeks, 24 hours" required>
                        </div>

                        <div class="form-group">
                            <label>Level</label>
                            <select name="level" required>
                                <option value="">Select Level</option>
                                <option value="Beginner">Beginner</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Advanced">Advanced</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Course Image</label>
                        <input type="file" name="course_image" accept="image/*" required>
                    </div>

                    <button type="submit" class="submit-btn">Create Course</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>