<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$course_id = $data['course_id'] ?? null;

if (!$course_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Course ID is required']);
    exit();
}

try {
    // Check if already completed
    $stmt = $pdo->prepare("SELECT * FROM course_completions WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Course already marked as completed']);
        exit();
    }

    // Insert completion record
    $stmt = $pdo->prepare("
        INSERT INTO course_completions (user_id, course_id, completion_date) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $course_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Course marked as completed'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to mark course as completed']);
}
?>