<?php
class CalendarManager {
    private $pdo;
    private $user_id;

    public function __construct($pdo, $user_id) {
        $this->pdo = $pdo;
        $this->user_id = $user_id;
    }

    public function getEvents($month, $year) {
        // Get assignments
        $assignments = $this->getAssignments($month, $year);
        // Get lessons
        $lessons = $this->getLessons($month, $year);
        // Get course schedules (skip if table doesn't exist)
        try {
            $schedules = $this->getCourseSchedules($month, $year);
        } catch (PDOException $e) {
            $schedules = [];
        }

        return array_merge($assignments, $lessons, $schedules);
    }

    private function getAssignments($month, $year) {
        $stmt = $this->pdo->prepare("
            SELECT 
                a.id,
                a.title,
                a.description,
                a.due_date as date,
                c.title as course_title,
                'assignment' as event_type
            FROM assignments a
            JOIN courses c ON a.course_id = c.id
            JOIN enrollments e ON c.id = e.course_id
            WHERE e.user_id = ? 
            AND MONTH(a.due_date) = ?
            AND YEAR(a.due_date) = ?
        ");
        $stmt->execute([$this->user_id, $month, $year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getLessons($month, $year) {
        $stmt = $this->pdo->prepare("
            SELECT 
                l.id,
                l.title,
                l.description,
                l.date,
                c.title as course_title,
                'lesson' as event_type
            FROM lessons l
            JOIN courses c ON l.course_id = c.id
            JOIN enrollments e ON c.id = e.course_id
            WHERE e.user_id = ?
            AND MONTH(l.date) = ?
            AND YEAR(l.date) = ?
        ");
        $stmt->execute([$this->user_id, $month, $year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getCourseSchedules($month, $year) {
        $stmt = $this->pdo->prepare("
            SELECT 
                cs.id,
                c.title,
                cs.description,
                cs.schedule_date as date,
                c.title as course_title,
                'schedule' as event_type
            FROM course_schedules cs
            JOIN courses c ON cs.course_id = c.id
            JOIN enrollments e ON c.id = e.course_id
            WHERE e.user_id = ?
            AND MONTH(cs.schedule_date) = ?
            AND YEAR(cs.schedule_date) = ?
        ");
        $stmt->execute([$this->user_id, $month, $year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEventDetails($event_id, $event_type) {
        switch ($event_type) {
            case 'assignment':
                return $this->getAssignmentDetails($event_id);
            case 'lesson':
                return $this->getLessonDetails($event_id);
            case 'schedule':
                return $this->getScheduleDetails($event_id);
            default:
                return null;
        }
    }

    public function formatEventsForCalendar($events) {
        $formatted = [];
        foreach ($events as $event) {
            $date = date('Y-m-d', strtotime($event['date']));
            if (!isset($formatted[$date])) {
                $formatted[$date] = [];
            }
            
            $formatted[$date][] = [
                'id' => $event['id'],
                'title' => $event['title'],
                'course' => $event['course_title'],
                'type' => $event['event_type'],
                'time' => date('H:i', strtotime($event['date'])),
                'description' => $event['description']
            ];
        }
        return $formatted;
    }

    private function getAssignmentDetails($event_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                a.id,
                a.title,
                a.description,
                a.due_date as date,
                a.max_score,
                c.title as course_title
            FROM assignments a
            JOIN courses c ON a.course_id = c.id
            WHERE a.id = ?
        ");
        $stmt->execute([$event_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getLessonDetails($event_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                l.id,
                l.title,
                l.description,
                l.date,
                l.content,
                c.title as course_title
            FROM lessons l
            JOIN courses c ON l.course_id = c.id
            WHERE l.id = ?
        ");
        $stmt->execute([$event_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getScheduleDetails($event_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                cs.id,
                cs.title,
                cs.description,
                cs.schedule_date as date,
                c.title as course_title
            FROM course_schedules cs
            JOIN courses c ON cs.course_id = c.id
            WHERE cs.id = ?
        ");
        $stmt->execute([$event_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}