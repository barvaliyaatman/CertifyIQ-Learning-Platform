<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Get instructor ID from URL parameter
$instructor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$instructor_id) {
    header('Location: index.php');
    exit();
}

// Get instructor data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'instructor'");
$stmt->execute([$instructor_id]);
$instructor = $stmt->fetch();

if (!$instructor) {
    header('Location: index.php');
    exit();
}

// Get instructor statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_courses FROM courses WHERE instructor_id = ?");
$stmt->execute([$instructor_id]);
$total_courses = $stmt->fetch()['total_courses'];

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT e.user_id) as total_students 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.id 
    WHERE c.instructor_id = ?
");
$stmt->execute([$instructor_id]);
$total_students = $stmt->fetch()['total_students'];

$stmt = $pdo->prepare("
    SELECT AVG(s.score) as avg_score 
    FROM assignment_submissions s 
    JOIN assignments a ON s.assignment_id = a.id 
    JOIN courses c ON a.course_id = c.id 
    WHERE c.instructor_id = ? AND s.score IS NOT NULL
");
$stmt->execute([$instructor_id]);
$avg_score = $stmt->fetch()['avg_score'] ?? 0;

// Get instructor's courses
$stmt = $pdo->prepare("
    SELECT c.*, COUNT(DISTINCT e.user_id) as enrolled_students
    FROM courses c 
    LEFT JOIN enrollments e ON c.id = e.course_id
    WHERE c.instructor_id = ?
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->execute([$instructor_id]);
$courses = $stmt->fetchAll();

// Get recent testimonials
$stmt = $pdo->prepare("
    SELECT t.*, u.name as student_name 
    FROM testimonials t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.course_name IN (
        SELECT title FROM courses WHERE instructor_id = ?
    )
    ORDER BY t.created_at DESC 
    LIMIT 5
");
$stmt->execute([$instructor_id]);
$testimonials = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($instructor['name']); ?> - Instructor Profile</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/components/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/course.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background: #f8f9fb;
        }
        .sidebar, .instructor-sidebar {
            width: 260px;
            flex-shrink: 0;
            background: #223046;
            min-height: 100vh;
        }
        .content, .main-content {
            flex: 1;
            padding: 40px 30px;
            background: #f8f9fb;
            min-height: 100vh;
        }
        .instructor-profile-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 0 20px 0;
        }
        
        .instructor-hero {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 48px 40px 48px 40px;
            border-radius: 20px 20px 0 0;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
        }
        
        .instructor-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .instructor-info {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 40px;
        }
        
        .instructor-avatar {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            border: 6px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }
        
        .instructor-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .instructor-details h1 {
            font-size: 3em;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .instructor-details .expertise {
            font-size: 1.18em;
            color: #333;
            font-weight: 500;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .instructor-details .teaching-since {
            font-size: 1.08em;
            color: #444;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .instructor-details .member-since {
            font-size: 1.1em;
            opacity: 0.8;
        }
        
        .profile-actions {
            margin-top: 20px;
        }
        
        .profile-actions .btn {
            padding: 12px 24px;
            font-size: 1.1em;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .profile-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
        }
        
        .stat-card .icon {
            font-size: 3em;
            margin-bottom: 20px;
            color: #667eea;
        }
        
        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 1.1em;
            font-weight: 500;
        }
        
        .content-sections {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
        }
        
        .courses-section, .testimonials-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .section-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }
        
        .section-header h2 {
            color: #2c3e50;
            font-size: 1.8em;
            margin: 0;
        }
        
        .course-card {
            display: flex;
            gap: 20px;
            padding: 20px;
            border: 2px solid #f8f9fa;
            border-radius: 12px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .course-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
        }
        
        .course-image {
            width: 100%;
            aspect-ratio: 16/9;
            border-radius: 8px;
            overflow: hidden;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-bottom: 10px;
        }
        
        .course-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .course-info {
            flex: 1;
        }
        
        .course-info h3 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 1.3em;
        }
        
        .course-info .course-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 10px;
            font-size: 0.9em;
            color: #666;
        }
        
        .course-info .course-description {
            color: #555;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .course-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-outline {
            border: 2px solid #667eea;
            color: #667eea;
            background: transparent;
        }
        
        .btn-outline:hover {
            background: #667eea;
            color: white;
        }
        
        .testimonial-item {
            padding: 20px 0;
            border-bottom: 1px solid #e1e8ed;
        }
        
        .testimonial-item:last-child {
            border-bottom: none;
        }
        
        .testimonial-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .testimonial-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .testimonial-author {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .testimonial-course {
            font-size: 0.9em;
            color: #667eea;
        }
        
        .testimonial-content {
            color: #555;
            line-height: 1.6;
            font-style: italic;
        }
        
        .testimonial-date {
            font-size: 0.8em;
            color: #999;
            margin-top: 8px;
        }
        
        .no-courses, .no-testimonials {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-courses i, .no-testimonials i {
            font-size: 3em;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        @media (max-width: 768px) {
            .instructor-info {
                flex-direction: column;
                text-align: center;
            }
            
            .content-sections {
                grid-template-columns: 1fr;
            }
            
            .course-card {
                flex-direction: column;
            }
            
            .course-image {
                width: 100%;
                height: 150px;
            }
            
            .stats-section {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 900px) {
            .instructor-hero {
                padding: 32px 10px;
                border-radius: 0;
            }
            .instructor-profile-container {
                padding: 0 0 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>
        <main class="content">
            <div class="instructor-profile-container">
                <div class="instructor-hero">
                    <div class="instructor-info">
                        <div class="instructor-avatar">
                            <img src="<?php 
                                echo !empty($instructor['profile_image']) 
                                    ? BASE_URL . '/' . $instructor['profile_image'] 
                                    : BASE_URL . '/assets/images/default-avatar.jpg'; 
                            ?>" alt="<?php echo htmlspecialchars($instructor['name']); ?>">
                        </div>
                        <div class="instructor-details">
                            <h1><?php echo htmlspecialchars($instructor['name']); ?></h1>
                            <?php if (!empty($instructor['expertise'])): ?>
                                <p class="expertise"><i class="fas fa-star"></i> <?php echo htmlspecialchars($instructor['expertise']); ?></p>
                            <?php endif; ?>
                            <p class="teaching-since"><i class="fas fa-calendar"></i> Teaching since <?php echo date('F d, Y', strtotime($instructor['created_at'])); ?></p>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $instructor_id && $_SESSION['user_role'] === 'instructor'): ?>
                                <div class="profile-actions">
                                    <a href="instructor/edit_profile.php" class="btn btn-primary">
                                        <i class="fas fa-edit"></i> Edit Profile
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="stats-section">
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="number"><?php echo $total_courses; ?></div>
                        <div class="label">Courses Created</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="number"><?php echo $total_students; ?></div>
                        <div class="label">Students Taught</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="number"><?php echo date('Y') - date('Y', strtotime($instructor['created_at'])); ?>+</div>
                        <div class="label">Years Experience</div>
                    </div>
                </div>

                <div class="courses-section">
                    <div class="section-header">
                        <h2><i class="fas fa-graduation-cap"></i> Courses by <?php echo htmlspecialchars($instructor['name']); ?></h2>
                    </div>
                    <?php if (!empty($courses)): ?>
                    <div class="courses-grid">
                        <?php foreach ($courses as $course): ?>
                            <div class="course-card">
                                <div class="course-image">
                                    <img src="<?php 
                                        echo !empty($course['image_url']) 
                                            ? BASE_URL . '/' . $course['image_url'] 
                                            : BASE_URL . '/assets/images/default-course.jpg'; 
                                    ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                </div>
                                <div class="course-info">
                                    <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                    <div class="course-meta">
                                        <span><i class="fas fa-users"></i> <?php echo $course['enrolled_students']; ?> students</span>
                                        <span><i class="fas fa-clock"></i> <?php echo $course['duration'] ?? 'Self-paced'; ?></span>
                                        <span><i class="fas fa-signal"></i> <?php echo ucfirst($course['level'] ?? 'All levels'); ?></span>
                                    </div>
                                    <p class="course-description">
                                        <?php echo htmlspecialchars(substr($course['description'], 0, 150)) . (strlen($course['description']) > 150 ? '...' : ''); ?>
                                    </p>
                                    <div class="course-actions">
                                        <a href="pages/course_view.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-eye"></i> View Course
                                        </a>
                                        <a href="pages/enroll.php?course_id=<?php echo $course['id']; ?>" class="btn btn-outline">
                                            <i class="fas fa-graduation-cap"></i> Enroll Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <div class="no-courses">
                            <i class="fas fa-book-open"></i>
                            <h3>No courses available yet</h3>
                            <p>This instructor hasn't published any courses yet. Check back later!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <!-- Profile Photo Modal (optional, if you want to allow modal viewing) -->
    <div id="profileModal" onclick="hideProfileModal()" style="display:none;">
        <img src="" alt="Profile Picture" id="modalImage">
    </div>
    <script>
        // Optional: If you want to allow modal viewing of instructor photo
        function showProfilePhoto() {
            const modal = document.getElementById('profileModal');
            const modalImg = document.getElementById('modalImage');
            const profileImg = document.querySelector('.instructor-avatar img');
            modalImg.src = profileImg.src;
            modal.style.display = 'block';
        }
        function hideProfileModal() {
            document.getElementById('profileModal').style.display = 'none';
        }
        // Animate stats on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {
                threshold: 0.5,
                rootMargin: '0px 0px -50px 0px'
            };
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            document.querySelectorAll('.stat-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html> 