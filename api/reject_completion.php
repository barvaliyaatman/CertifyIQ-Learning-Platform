<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An error occurred.'];

try {
    require_login();
    if ($_SESSION['user_role'] !== 'instructor') {
        throw new Exception('You are not authorized to perform this action.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['completion_id']) || !isset($_POST['rejection_reason'])) {
        throw new Exception('Invalid request.');
    }

    $completion_id = (int)$_POST['completion_id'];
    $rejection_reason = trim($_POST['rejection_reason']);
    $instructor_id = $_SESSION['user_id'];

    if (empty($rejection_reason)) {
        throw new Exception('A reason for rejection is required.');
    }

    // Verify that the instructor owns the course for this completion
    $stmt = $pdo->prepare("
        SELECT c.instructor_id 
        FROM course_completions cc
        JOIN courses c ON cc.course_id = c.id
        WHERE cc.id = ?
    ");
    $stmt->execute([$completion_id]);
    $completion_instructor = $stmt->fetchColumn();

    if (!$completion_instructor || $completion_instructor != $instructor_id) {
        throw new Exception('You do not have permission to reject this completion.');
    }

    // Update the completion status to 'rejected'
    $stmt = $pdo->prepare("
        UPDATE course_completions
        SET status = 'rejected', rejection_reason = ?, processed_by = ?, processed_at = NOW()
        WHERE id = ? AND status = 'pending'
    ");
    
    if ($stmt->execute([$rejection_reason, $instructor_id, $completion_id])) {
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Course completion rejected successfully.';
        } else {
            $response['message'] = 'Could not reject the completion. It might have been processed already.';
        }
    } else {
        $response['message'] = 'Database error during rejection.';
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response); 