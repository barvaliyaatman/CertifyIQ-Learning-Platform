<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        
        $submission_id = $data['submission_id'] ?? null;
        $score = $data['score'] ?? null;
        $feedback = $data['feedback'] ?? '';

        if (!$submission_id || !isset($score)) {
            throw new Exception('Missing required fields');
        }

        // Verify instructor owns the assignment
        $stmt = $pdo->prepare("
            SELECT a.course_id 
            FROM assignment_submissions s
            JOIN assignments a ON s.assignment_id = a.id
            JOIN courses c ON a.course_id = c.id
            WHERE s.id = ? AND c.instructor_id = ?
        ");
        $stmt->execute([$submission_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            throw new Exception('Unauthorized access');
        }

        // Update submission with grade and feedback
        $stmt = $pdo->prepare("
            UPDATE assignment_submissions 
            SET score = ?, feedback = ?
            WHERE id = ?
        ");
        
        if (!$stmt->execute([$score, $feedback, $submission_id])) {
            throw new Exception('Failed to save grade');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Grade saved successfully'
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}