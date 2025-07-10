<?php
function markCourseAsCompleted($pdo, $user_id, $course_id) {
    try {
        // Check if all assignments are completed
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_assignments,
                   COUNT(s.id) as completed_assignments
            FROM assignments a
            LEFT JOIN assignment_submissions s 
                ON a.id = s.assignment_id 
                AND s.user_id = ?
            WHERE a.course_id = ?
        ");
        $stmt->execute([$user_id, $course_id]);
        $result = $stmt->fetch();

        if ($result['total_assignments'] > 0 && 
            $result['total_assignments'] != $result['completed_assignments']) {
            return [
                'success' => false,
                'message' => 'Complete all assignments before marking the course as completed.'
            ];
        }

        // Insert completion record
        $stmt = $pdo->prepare("
            INSERT INTO course_completions (user_id, course_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$user_id, $course_id]);

        return ['success' => true, 'message' => 'Course marked as completed!'];
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            return ['success' => false, 'message' => 'Course already marked as completed.'];
        }
        return ['success' => false, 'message' => 'Error marking course as completed.'];
    }
}