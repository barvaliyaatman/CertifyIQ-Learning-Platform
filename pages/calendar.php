<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../classes/CalendarManager.php';

// Ensure user is logged in
require_login();

// Initialize calendar manager
$calendarManager = new CalendarManager($pdo, $_SESSION['user_id']);

// Get current month and year
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle all AJAX requests first
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'get_event_details':
                $eventDetails = $calendarManager->getEventDetails(
                    $_POST['event_id'],
                    $_POST['event_type']
                );
                echo json_encode([
                    'success' => true,
                    'data' => $eventDetails ? [
                        'id' => $eventDetails['id'],
                        'title' => $eventDetails['title'],
                        'description' => $eventDetails['description'],
                        'course' => $eventDetails['course_title'],
                        'time' => date('H:i', strtotime($eventDetails['date'])),
                        'date' => date('Y-m-d', strtotime($eventDetails['date']))
                    ] : null
                ]);
                break;

            case 'add_event':
                if ($user['role'] !== 'instructor') {
                    throw new Exception('Unauthorized access');
                }
                $result = $calendarManager->addEvent(
                    $_POST['title'],
                    $_POST['description'],
                    $_POST['date'],
                    $_POST['course_id'],
                    $_POST['event_type']
                );
                echo json_encode(['success' => true, 'data' => $result]);
                break;

            case 'update_event':
                if ($user['role'] !== 'instructor') {
                    throw new Exception('Unauthorized access');
                }
                $result = $calendarManager->updateEvent(
                    $_POST['event_id'],
                    $_POST['title'],
                    $_POST['description'],
                    $_POST['date'],
                    $_POST['event_type']
                );
                echo json_encode(['success' => true, 'data' => $result]);
                break;

            case 'delete_event':
                if ($user['role'] !== 'instructor') {
                    throw new Exception('Unauthorized access');
                }
                $result = $calendarManager->deleteEvent(
                    $_POST['event_id'],
                    $_POST['event_type']
                );
                echo json_encode(['success' => true]);
                break;

            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Get events for the month
try {
    $events = $calendarManager->getEvents($month, $year);
    $formattedEvents = $calendarManager->formatEventsForCalendar($events);
} catch (Exception $e) {
    $error_message = "Error loading calendar events: " . $e->getMessage();
    $events = [];
    $formattedEvents = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Calendar - Learning Management System</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/calendar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="../assets/js/calendar.js" defer></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="content">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="page-header">
                <h1>Academic Calendar</h1>
                <div class="calendar-nav">
                    <?php
                    $prev_month = $month - 1;
                    $prev_year = $year;
                    if ($prev_month < 1) {
                        $prev_month = 12;
                        $prev_year--;
                    }

                    $next_month = $month + 1;
                    $next_year = $year;
                    if ($next_month > 12) {
                        $next_month = 1;
                        $next_year++;
                    }
                    ?>
                    <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="nav-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <h2><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h2>
                    <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="nav-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>

            <div class="calendar-container">
                <div class="calendar-header">
                    <div>Sunday</div>
                    <div>Monday</div>
                    <div>Tuesday</div>
                    <div>Wednesday</div>
                    <div>Thursday</div>
                    <div>Friday</div>
                    <div>Saturday</div>
                </div>

                <div class="calendar-grid">
                    <?php
                    $first_day = mktime(0, 0, 0, $month, 1, $year);
                    $days_in_month = date('t', $first_day);
                    $start_day = date('w', $first_day);
                    $current_date = date('Y-m-d');

                    // Fill in blank days at start of month
                    for ($i = 0; $i < $start_day; $i++) {
                        echo '<div class="calendar-day empty"></div>';
                    }

                    // Fill in days of month
                    for ($day = 1; $day <= $days_in_month; $day++) {
                        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $is_today = ($date === $current_date);
                        $has_events = isset($events[$date]);

                        echo '<div class="calendar-day' . ($is_today ? ' today' : '') . '">';
                        echo '<span class="day-number">' . $day . '</span>';

                        if ($has_events) {
                            echo '<div class="events-list">';
                            foreach ($events[$date] as $event) {
                                echo '<div class="event">';
                                echo '<span class="event-time">' . $event['time'] . '</span>';
                                echo '<span class="event-title">' . htmlspecialchars($event['title']) . '</span>';
                                echo '<span class="event-course">' . htmlspecialchars($event['course']) . '</span>';
                                echo '</div>';
                            }
                            echo '</div>';
                        }
                        echo '</div>';
                    }

                    // Fill in blank days at end of month
                    $end_blanks = 42 - ($start_day + $days_in_month); // 42 = 6 rows × 7 days
                    for ($i = 0; $i < $end_blanks; $i++) {
                        echo '<div class="calendar-day empty"></div>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>