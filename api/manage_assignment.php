<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is an instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $course_id = $_POST['course_id'] ?? null;
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $max_score = $_POST['max_score'] ?? 100;
        $due_date = $_POST['due_date'] ?? null;

        // Validate required fields
        if (!$course_id || !$title || !$due_date) {
            throw new Exception('Required fields are missing');
        }

        // Check if instructor owns the course
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND instructor_id = ?");
        $stmt->execute([$course_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            throw new Exception('Unauthorized access to course');
        }

        // Insert new assignment
        $stmt = $pdo->prepare("
            INSERT INTO assignments (course_id, title, description, max_score, due_date) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if (!$stmt->execute([$course_id, $title, $description, $max_score, $due_date])) {
            throw new Exception('Failed to create assignment');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Assignment created successfully',
            'assignment_id' => $pdo->lastInsertId()
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