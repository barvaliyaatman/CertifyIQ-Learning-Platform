<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get assignment ID
$assignment_id = $_GET['id'] ?? null;

if (!$assignment_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Assignment ID not provided']);
    exit();
}

try {
    // Verify the assignment belongs to this instructor
    $stmt = $pdo->prepare("
        SELECT a.* FROM assignments a 
        JOIN courses c ON a.course_id = c.id 
        WHERE a.id = ? AND c.instructor_id = ?
    ");
    $stmt->execute([$assignment_id, $_SESSION['user_id']]);
    $assignment = $stmt->fetch();

    if (!$assignment) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        exit();
    }

    // Delete the assignment
    $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
    $stmt->execute([$assignment_id]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>