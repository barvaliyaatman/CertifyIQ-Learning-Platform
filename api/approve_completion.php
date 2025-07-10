<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Set header to return JSON
header('Content-Type: application/json');

// Response structure
$response = ['success' => false, 'message' => 'An error occurred.'];

// Ensure user is logged in and is an instructor
try {
    require_login();
    if ($_SESSION['user_role'] !== 'instructor') {
        throw new Exception('You are not authorized to perform this action.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['completion_id'])) {
        throw new Exception('Invalid request.');
    }

    $completion_id = (int)$_POST['completion_id'];
    $instructor_id = $_SESSION['user_id'];

    //
    // WARNING: This is a critical section.
    // We must verify that the instructor approving this completion
    // is actually the instructor of the course.
    //
    $stmt = $pdo->prepare("
        SELECT c.instructor_id 
        FROM course_completions cc
        JOIN courses c ON cc.course_id = c.id
        WHERE cc.id = ?
    ");
    $stmt->execute([$completion_id]);
    $completion_instructor = $stmt->fetchColumn();

    if (!$completion_instructor || $completion_instructor != $instructor_id) {
        throw new Exception('You do not have permission to approve this completion.');
    }

    // Update the completion status
    $stmt = $pdo->prepare("
        UPDATE course_completions
        SET status = 'approved', processed_by = ?, processed_at = NOW()
        WHERE id = ? AND status = 'pending'
    ");
    
    if ($stmt->execute([$instructor_id, $completion_id])) {
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Course completion approved successfully.';
        } else {
            // This could happen if the completion was already approved
            $response['message'] = 'Could not approve the completion. It might have been approved already.';
        }
    } else {
        $response['message'] = 'Database error during approval.';
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response); 