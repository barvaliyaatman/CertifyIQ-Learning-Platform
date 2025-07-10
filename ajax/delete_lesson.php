<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lesson_id'])) {
    try {
        $stmt = $pdo->prepare("
            DELETE l FROM lessons l
            JOIN courses c ON l.course_id = c.id
            WHERE l.id = ? AND c.instructor_id = ?
        ");
        $stmt->execute([$_POST['lesson_id'], $_SESSION['user_id']]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting lesson']);
    }
}