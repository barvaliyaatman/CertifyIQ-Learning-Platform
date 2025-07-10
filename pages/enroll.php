<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Ensure user is logged in
require_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['course_id'])) {
    $course_id = (int)$_POST['course_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$user_id, $course_id]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "You are already enrolled in this course.";
            header("Location: course.php?id=" . $course_id);
            exit();
        }

        // Get course details
        $stmt = $pdo->prepare("
            SELECT c.*, u.name as instructor_name 
            FROM courses c 
            JOIN users u ON c.instructor_id = u.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();

        if (!$course) {
            $_SESSION['error'] = "Course not found.";
            header("Location: courses.php");
            exit();
        }

        // If course is free, enroll directly
        if ($course['price'] <= 0) {
            $stmt = $pdo->prepare("
                INSERT INTO enrollments (user_id, course_id, enrollment_date, status) 
                VALUES (?, ?, NOW(), 'active')
            ");
            
            if ($stmt->execute([$user_id, $course_id])) {
                $pdo->commit();
                $_SESSION['success'] = "Successfully enrolled in " . htmlspecialchars($course['title']);
                header("Location: course.php?id=" . $course_id);
                exit();
            }
        } else {
            // For paid courses
            $_SESSION['pending_enrollment'] = [
                'course_id' => $course_id,
                'course_title' => $course['title'],
                'price' => $course['price'],
                'instructor_name' => $course['instructor_name']
            ];
            
            $pdo->commit();
            header("Location: payment.php?course_id=" . $course_id);
            exit();
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Enrollment Error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred during enrollment. Please try again.";
        header("Location: courses.php");
        exit();
    }
} else {
    header("Location: courses.php");
    exit();
}
?>