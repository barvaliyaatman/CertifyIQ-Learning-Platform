<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Please log in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $assignment_id = $_POST['assignment_id'] ?? null;
        $submission_text = $_POST['submission_text'] ?? '';

        if (!$assignment_id) {
            throw new Exception('Assignment ID is required');
        }

        // Check if assignment exists and is still open
        $stmt = $pdo->prepare("
            SELECT a.*, c.id as course_id 
            FROM assignments a
            JOIN courses c ON a.course_id = c.id
            WHERE a.id = ?
        ");
        $stmt->execute([$assignment_id]);
        $assignment = $stmt->fetch();

        if (!$assignment) {
            throw new Exception('Assignment not found');
        }

        // Check if student is enrolled in the course
        $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$_SESSION['user_id'], $assignment['course_id']]);
        if (!$stmt->fetch()) {
            throw new Exception('You are not enrolled in this course');
        }

        // Handle file upload
        $file_path = null;
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === 0) {
            $allowed_types = ['pdf', 'doc', 'docx', 'zip'];
            $file_ext = strtolower(pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, $allowed_types)) {
                throw new Exception('Invalid file type');
            }

            if ($_FILES['submission_file']['size'] > 10 * 1024 * 1024) { // 10MB limit
                throw new Exception('File size too large');
            }

            $upload_dir = '../uploads/assignments/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = uniqid() . '.' . $file_ext;
            $file_path = 'uploads/assignments/' . $file_name;

            if (!move_uploaded_file($_FILES['submission_file']['tmp_name'], '../' . $file_path)) {
                throw new Exception('Failed to upload file');
            }
        }

        // Check for existing submission
        $stmt = $pdo->prepare("SELECT id FROM assignment_submissions WHERE user_id = ? AND assignment_id = ?");
        $stmt->execute([$_SESSION['user_id'], $assignment_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update existing submission
            $stmt = $pdo->prepare("
                UPDATE assignment_submissions 
                SET submission_text = ?, file_path = COALESCE(?, file_path), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$submission_text, $file_path, $existing['id']]);
        } else {
            // Create new submission
            $stmt = $pdo->prepare("
                INSERT INTO assignment_submissions (user_id, assignment_id, submission_text, file_path) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $assignment_id, $submission_text, $file_path]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Assignment submitted successfully'
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