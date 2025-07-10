<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT s.* 
        FROM sections s
        JOIN courses c ON s.course_id = c.id
        WHERE s.id = ? AND c.instructor_id = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $section = $stmt->fetch();
    
    if ($section) {
        echo json_encode(['success' => true, 'section' => $section]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Section not found']);
    }
}
?> 