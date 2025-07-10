<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $lesson_id = isset($_POST['lesson_id']) ? $_POST['lesson_id'] : null;
        
        if ($lesson_id) {
            // Update existing lesson
            $stmt = $pdo->prepare("
                UPDATE lessons 
                SET title = ?, description = ?, content = ?
                WHERE id = ? AND course_id IN (
                    SELECT id FROM courses WHERE instructor_id = ?
                )
            ");
            $stmt->execute([
                $_POST['title'],
                $_POST['description'],
                $_POST['content'],
                $lesson_id,
                $_SESSION['user_id']
            ]);
        } else {
            // Add new lesson
            $stmt = $pdo->prepare("
                INSERT INTO lessons (course_id, title, description, content, lesson_order)
                SELECT ?, ?, ?, ?, COALESCE(MAX(lesson_order), 0) + 1
                FROM lessons WHERE course_id = ?
            ");
            $stmt->execute([
                $_POST['course_id'],
                $_POST['title'],
                $_POST['description'],
                $_POST['content'],
                $_POST['course_id']
            ]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error saving lesson']);
    }
}