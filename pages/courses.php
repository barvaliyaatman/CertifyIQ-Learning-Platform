<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Ensure user is logged in
require_login();

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get all available courses with instructor information
$stmt = $pdo->prepare("
    SELECT c.*, u.name as instructor_name, 
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count 
    FROM courses c 
    JOIN users u ON c.instructor_id = u.id
");
$stmt->execute();
$courses = $stmt->fetchAll();

// Check user's enrolled courses
$stmt = $pdo->prepare("SELECT course_id FROM enrollments WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$enrolled_courses = array_column($stmt->fetchAll(), 'course_id');

// Add this query near the top of your PHP code, after the other queries
$stmt = $pdo->query("SELECT DISTINCT category FROM courses WHERE category IS NOT NULL");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Courses - LMS</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/course.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php';?>

        <main class="content">
            <div class="courses-header">
                <h1>Available Courses</h1>
                <div class="search-box">
                    <input type="text" id="courseSearch" placeholder="Search courses...">
                    <i class="fas fa-search"></i>
                </div>
            </div>

            <div class="course-filters">
                <select id="categoryFilter">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo strtolower(htmlspecialchars($category)); ?>">
                            <?php echo htmlspecialchars($category); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="course-grid">
                <?php foreach ($courses as $course): ?>
                <div class="course-card" data-category="<?php echo strtolower(htmlspecialchars($course['category'])); ?>">
                    <div class="course-image">
                        <img src="<?php 
                            if (!empty($course['image_url'])) {
                                echo '../' . ltrim($course['image_url'], '/');
                            } else {
                                echo '../assets/images/default-course.jpg';
                            }
                        ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                        <div class="course-overlay">
                            <span class="student-count">
                                <i class="fas fa-users"></i> <?php echo $course['student_count']; ?> students
                            </span>
                        </div>
                    </div>
                    <div class="course-info">
                        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                        <p class="instructor">
                            <i class="fas fa-chalkboard-teacher"></i> 
                            <?php echo htmlspecialchars($course['instructor_name']); ?>
                        </p>
                        <p class="description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                        <div class="course-meta">
                            <?php if (!empty($course['duration'])): ?>
                            <span class="duration"><i class="far fa-clock"></i> <?php echo htmlspecialchars($course['duration']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($course['level'])): ?>
                            <span class="level"><i class="fas fa-signal"></i> <?php echo ucfirst(htmlspecialchars($course['level'])); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (in_array($course['id'], $enrolled_courses)): ?>
                            <a href="course.php?id=<?php echo $course['id']; ?>" class="btn enrolled">
                                <i class="fas fa-play-circle"></i> Continue Learning
                            </a>
                        <?php else: ?>
                            <form action="enroll.php" method="POST" class="enroll-form">
                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                <button type="submit" class="btn enroll-btn">
                                    <i class="fas fa-graduation-cap"></i> Enroll Now
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <script>
        // Simple search functionality
        document.getElementById('courseSearch').addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            document.querySelectorAll('.course-card').forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const instructor = card.querySelector('.instructor').textContent.toLowerCase();
                card.style.display = (title.includes(search) || instructor.includes(search)) ? '' : 'none';
            });
        });

        // Updated Category filter
        document.getElementById('categoryFilter').addEventListener('change', function(e) {
            const selectedCategory = e.target.value.toLowerCase();
            document.querySelectorAll('.course-card').forEach(card => {
                const cardCategory = card.getAttribute('data-category');
                if (!selectedCategory || cardCategory === selectedCategory) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>