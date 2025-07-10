<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../config/config.php'; // For BASE_URL

// Ensure user is an instructor
require_login();
if ($_SESSION['user_role'] !== 'instructor') {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit();
}

$course_id = $_GET['id'] ?? null;
if (!$course_id) {
    header("Location: manage_courses.php");
    exit();
}

// Get user data for the sidebar
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch course data and verify ownership
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    // If course not found or doesn't belong to the instructor
    $_SESSION['error_message'] = "Course not found or you don't have permission to edit it.";
    header("Location: manage_courses.php");
    exit();
}

$success = $_SESSION['success_message'] ?? null;
$error = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? $course['title'];
    $description = $_POST['description'] ?? $course['description'];
    $price = $_POST['price'] ?? $course['price'];
    $category = $_POST['category'] ?? $course['category'];
    $duration = $_POST['duration'] ?? $course['duration'];
    $level = $_POST['level'] ?? $course['level'];
    $image_url = $course['image_url'];

    // Handle new file upload
    if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] == 0) {
        $target_dir = "../../uploads/courses/";
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['course_image']['name'], PATHINFO_EXTENSION));
        $file_name = uniqid('course_') . '.' . $file_extension;
        $target_file = $target_dir . $file_name;
        
        // Basic validation for image type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_extension, $allowed_types)) {
            if (move_uploaded_file($_FILES['course_image']['tmp_name'], $target_file)) {
                // Delete old image if it exists
                if (!empty($image_url) && file_exists('../../' . $image_url)) {
                    unlink('../../' . $image_url);
                }
                $image_url = 'uploads/courses/' . $file_name;
            } else {
                $error = "Failed to upload new image.";
            }
        } else {
            $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
    }

    // Update database if no error
    if (!$error) {
        try {
            $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ?, price = ?, category = ?, duration = ?, level = ?, image_url = ? WHERE id = ? AND instructor_id = ?");
            if ($stmt->execute([$title, $description, $price, $category, $duration, $level, $image_url, $course_id, $_SESSION['user_id']])) {
                $_SESSION['success_message'] = "Course updated successfully!";
                header("Location: manage_courses.php");
                exit();
            } else {
                $error = "Failed to update course.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Course - Instructor Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/instructor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="instructor-container">
        <?php include '../../includes/instructor_sidebar.php'; ?>

        <main class="instructor-content">
            <header class="instructor-header">
                <h1><i class="fas fa-edit"></i> Edit Course</h1>
                <a href="manage_courses.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Courses</a>
            </header>

            <?php if ($success): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" class="course-form">
                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">

                    <div class="form-group">
                        <label>Course Title</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="6" required><?php echo htmlspecialchars($course['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Price ($)</label>
                        <input type="number" name="price" value="<?php echo htmlspecialchars($course['price']); ?>" min="0" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <?php $categories = ['Programming', 'Design', 'Business', 'Marketing', 'Music', 'Photography']; ?>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php if ($course['category'] == $cat) echo 'selected'; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Duration</label>
                            <input type="text" name="duration" value="<?php echo htmlspecialchars($course['duration']); ?>" placeholder="e.g., 8 weeks, 24 hours" required>
                        </div>

                        <div class="form-group">
                            <label>Level</label>
                            <select name="level" required>
                                <?php $levels = ['Beginner', 'Intermediate', 'Advanced']; ?>
                                <?php foreach ($levels as $lvl): ?>
                                    <option value="<?php echo $lvl; ?>" <?php if ($course['level'] == $lvl) echo 'selected'; ?>>
                                        <?php echo $lvl; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Course Image</label>
                        <div class="current-image">
                            <p>Current Image:</p>
                            <img src="<?php echo BASE_URL . '/' . $course['image_url']; ?>" alt="Current Course Image" style="max-width: 200px; border-radius: 5px; margin-bottom: 10px;">
                        </div>
                        <input type="file" name="course_image" accept="image/*">
                        <small>Upload a new image to replace the current one.</small>
                    </div>

                    <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Update Course</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html> 