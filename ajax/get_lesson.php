<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT l.* 
        FROM lessons l
        JOIN courses c ON l.course_id = c.id
        WHERE l.id = ? AND c.instructor_id = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $lesson = $stmt->fetch();
    
    if ($lesson) {
        echo json_encode(['success' => true, 'lesson' => $lesson]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lesson not found']);
    }
}