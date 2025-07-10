<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $level = $_POST['level'] ?? '';
    $price = $_POST['price'] ?? 0;
    $category = $_POST['category'] ?? '';
    
    // Handle file upload
    $image_url = '';
    if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] == 0) {
        $target_dir = "../../uploads/courses/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES['course_image']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
            header("Location: add_course.php");
            exit();
        }
        
        $file_name = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES['course_image']['tmp_name'], $target_file)) {
            $image_url = 'uploads/courses/' . $file_name;
        } else {
            $_SESSION['error'] = "Failed to upload image.";
            header("Location: add_course.php");
            exit();
        }
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO courses (
                title, description, duration, level, 
                price, category, image_url, instructor_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([
            $title, $description, $duration, $level,
            $price, $category, $image_url, $_SESSION['user_id']
        ])) {
            $_SESSION['success'] = "Course added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add course.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    
    header("Location: manage_courses.php");
    exit();
} else {
    header("Location: add_course.php");
    exit();
}
?>