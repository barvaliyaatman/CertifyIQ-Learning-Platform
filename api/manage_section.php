<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $course_id = $_POST['course_id'];
        $title = $_POST['title'];
        $order = $_POST['order'] ?? null;

        // Verify instructor owns the course
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND instructor_id = ?");
        $stmt->execute([$course_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            throw new Exception('Unauthorized access to this course');
        }

        // If no order specified, put at the end
        if (!$order) {
            $stmt = $pdo->prepare("SELECT MAX(order_number) as max_order FROM sections WHERE course_id = ?");
            $stmt->execute([$course_id]);
            $result = $stmt->fetch();
            $order = ($result['max_order'] ?? 0) + 1;
        }

        $stmt = $pdo->prepare("INSERT INTO sections (course_id, title, order_number) VALUES (?, ?, ?)");
        $stmt->execute([$course_id, $title, $order]);

        echo json_encode([
            'success' => true,
            'message' => 'Section added successfully',
            'section_id' => $pdo->lastInsertId()
        ]);
    } catch (Exception $e) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        // Read the JSON input from the request body
        $data = json_decode(file_get_contents('php://input'), true);
        
        $section_id = $data['section_id'] ?? null;
        $title = $data['title'] ?? null;
        $order = $data['order'] ?? null;

        if (!$section_id || !$title) {
            throw new Exception('Section ID and title are required.');
        }

        // Verify instructor owns the course
        $stmt = $pdo->prepare("
            SELECT c.instructor_id 
            FROM sections s
            JOIN courses c ON s.course_id = c.id
            WHERE s.id = ?
        ");
        $stmt->execute([$section_id]);
        $owner = $stmt->fetch();

        if (!$owner || $owner['instructor_id'] != $_SESSION['user_id']) {
            throw new Exception('Unauthorized to edit this section');
        }

        // Update the section
        $stmt = $pdo->prepare("UPDATE sections SET title = ?, order_number = ? WHERE id = ?");
        $stmt->execute([$title, $order, $section_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Section updated successfully'
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        // Read the JSON input from the request body
        $data = json_decode(file_get_contents('php://input'), true);
        $section_id = $data['section_id'] ?? null;

        if (!$section_id) {
            throw new Exception('Section ID is required.');
        }

        // Verify instructor owns the course
        $stmt = $pdo->prepare("
            SELECT c.instructor_id 
            FROM sections s
            JOIN courses c ON s.course_id = c.id
            WHERE s.id = ?
        ");
        $stmt->execute([$section_id]);
        $owner = $stmt->fetch();

        if (!$owner || $owner['instructor_id'] != $_SESSION['user_id']) {
            throw new Exception('Unauthorized to delete this section');
        }

        // Proceed with deletion
        $stmt = $pdo->prepare("DELETE FROM sections WHERE id = ?");
        $stmt->execute([$section_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Section deleted successfully'
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>