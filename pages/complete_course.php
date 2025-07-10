<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/course_completion.php';

// Ensure user is logged in
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    header('Content-Type: application/json');
    
    $result = markCourseAsCompleted($pdo, $_SESSION['user_id'], $_POST['course_id']);
    echo json_encode($result);
    exit;
}