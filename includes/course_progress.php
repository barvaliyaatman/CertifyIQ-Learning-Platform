<?php
function getUserEnrolledCourses($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            COUNT(DISTINCT l.id) as total_lessons,
            COUNT(DISTINCT cp.lesson_id) as completed_lessons
        FROM courses c
        JOIN enrollments e ON c.id = e.course_id
        LEFT JOIN lessons l ON c.id = l.course_id
        LEFT JOIN course_progress cp ON (l.id = cp.lesson_id AND cp.user_id = ?)
        WHERE e.user_id = ?
        GROUP BY c.id
    ");
    
    $stmt->execute([$user_id, $user_id]);
    $courses = $stmt->fetchAll();
    
    // Calculate progress percentage for each course
    foreach ($courses as &$course) {
        $course['progress'] = $course['total_lessons'] > 0 
            ? round(($course['completed_lessons'] / $course['total_lessons']) * 100) 
            : 0;
    }
    
    return $courses;
}

function getCourseProgress($pdo, $user_id, $course_id) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT l.id) as total_lessons,
            COUNT(DISTINCT cp.lesson_id) as completed_lessons
        FROM lessons l
        LEFT JOIN course_progress cp ON cp.lesson_id = l.id 
            AND cp.user_id = ?
        WHERE l.course_id = ?
    ");
    
    $stmt->execute([$user_id, $course_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $percentage = $result['total_lessons'] > 0 
        ? round(($result['completed_lessons'] / $result['total_lessons']) * 100) 
        : 0;
    
    return [
        'total' => $result['total_lessons'],
        'completed' => $result['completed_lessons'],
        'percentage' => $percentage
    ];
}

function isLessonLocked($progress, $lesson_id) {
    // Check if previous lessons are completed
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as incomplete
        FROM lessons
        WHERE course_id = ? 
        AND order_number < (SELECT order_number FROM lessons WHERE id = ?)
        AND id NOT IN (
            SELECT lesson_id FROM course_progress WHERE user_id = ?
        )
    ");
    
    $stmt->execute([$course_id, $lesson_id, $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['incomplete'] > 0;
}

function markLessonComplete($pdo, $user_id, $lesson_id) {
    $stmt = $pdo->prepare("
        INSERT INTO course_progress (user_id, lesson_id, completed_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE completed_at = NOW()
    ");
    
    return $stmt->execute([$user_id, $lesson_id]);
}