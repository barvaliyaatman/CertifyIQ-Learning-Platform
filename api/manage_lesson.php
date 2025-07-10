<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug incoming data
        error_log('POST Data: ' . print_r($_POST, true));
        error_log('FILES Data: ' . print_r($_FILES, true));

        // Validate required fields
        $required_fields = ['course_id', 'section_id', 'title', 'type'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $course_id = $_POST['course_id'];
        $section_id = $_POST['section_id'];
        $title = $_POST['title'];
        $type = $_POST['type'];
        $description = $_POST['description'] ?? null;
        $content = $_POST['content'] ?? null;
        $order_number = $_POST['order'] ?? 1;
        $video_url = $_POST['video_url'] ?? null;
        $duration = $_POST['duration'] ?? null;

        // Verify instructor owns the course
        $stmt = $pdo->prepare("SELECT instructor_id FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();

        if (!$course || $course['instructor_id'] != $_SESSION['user_id']) {
            throw new Exception('Unauthorized access');
        }

        // Handle file upload if present
        $file_path = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $allowed_types = [
                'video/mp4',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            
            if (!in_array($_FILES['file']['type'], $allowed_types)) {
                throw new Exception('Invalid file type');
            }

            $upload_dir = '../uploads/lessons/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = uniqid() . '_' . $_FILES['file']['name'];
            $file_path = 'uploads/lessons/' . $file_name;

            if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $file_name)) {
                throw new Exception('Failed to upload file');
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO lessons (
                course_id, section_id, title, type, description, 
                content, file_path, video_url, duration, order_number
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $course_id, $section_id, $title, $type, $description,
            $content, $file_path, $video_url, $duration, $order_number
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Lesson added successfully',
            'lesson_id' => $pdo->lastInsertId()
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        // Read the JSON input from the request body
        $data = json_decode(file_get_contents('php://input'), true);
        
        $lesson_id = $data['lesson_id'] ?? null;
        $title = $data['title'] ?? null;
        $description = $data['description'] ?? null;
        $content = $data['content'] ?? null;
        $type = $data['type'] ?? null;
        $order_number = $data['order'] ?? null;

        if (!$lesson_id || !$title) {
            throw new Exception('Lesson ID and title are required.');
        }

        // Verify instructor owns the course
        $stmt = $pdo->prepare("
            SELECT c.instructor_id 
            FROM lessons l
            JOIN courses c ON l.course_id = c.id
            WHERE l.id = ?
        ");
        $stmt->execute([$lesson_id]);
        $owner = $stmt->fetch();

        if (!$owner || $owner['instructor_id'] != $_SESSION['user_id']) {
            throw new Exception('Unauthorized to edit this lesson');
        }

        // Update the lesson
        $stmt = $pdo->prepare("
            UPDATE lessons 
            SET title = ?, description = ?, content = ?, type = ?, order_number = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $content, $type, $order_number, $lesson_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Lesson updated successfully'
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