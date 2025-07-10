<?php
session_start();
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/auth.php';

// Get featured courses
$stmt = $pdo->prepare("SELECT c.*, u.name as instructor_name, u.profile_image as instructor_profile_image,
                       COUNT(DISTINCT e.user_id) as student_count,
                       c.image_url, c.title, c.description, c.level, c.category, c.duration 
                       FROM courses c 
                       LEFT JOIN users u ON c.instructor_id = u.id 
                       LEFT JOIN enrollments e ON c.id = e.course_id 
                       GROUP BY c.id 
                       ORDER BY c.created_at DESC
                       LIMIT 3");
$stmt->execute();
$featured_courses = $stmt->fetchAll();

// Add this for debugging
if (empty($featured_courses)) {
    error_log('No featured courses found');
}
// Get testimonials
$stmt = $pdo->prepare("SELECT t.*, u.name, u.profile_image 
                       FROM testimonials t 
                       JOIN users u ON t.user_id = u.id 
                       ORDER BY t.created_at DESC LIMIT 3");
$stmt->execute();
$testimonials = $stmt->fetchAll();

// Get instructors
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'instructor' LIMIT 3");
$instructors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Online Learning Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <header class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Unlock Your Potential</h1>
                <h2>Learn Without Limits</h2>
                <p>Join our vibrant learning community and master the skills that shape tomorrow.</p>
                <div class="hero-buttons">
                    <a href="pages/courses.php" class="cta-button primary">Explore Courses <i class="fas fa-compass"></i></a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="pages/register.php" class="cta-button secondary">Join Now <i class="fas fa-user-plus"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-image">
                <img src="assets/images/hero-illustration.svg" alt="Learning Illustration">
            </div>
        </div>
       
    </header>

    <!-- Featured Courses -->
    <?php $delay = 0; // Initialize delay variable ?>
    <section class="featured-courses" data-aos="fade-up">
        <div class="section-header">
            <h2>Featured Courses</h2>
            <p>Discover our most popular learning paths</p>
        </div>
        <div class="course-grid">
            <?php if (empty($featured_courses)): ?>
                <div class="no-courses">
                    <i class="fas fa-book-open"></i>
                    <p>No featured courses available at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($featured_courses as $course): ?>
                    <div class="course-card" data-aos="fade-up" data-aos-delay="<?php echo $delay += 100; ?>">
                        <div class="course-image">
                            <img src="<?php 
                                echo !empty($course['image_url']) 
                                    ? htmlspecialchars($course['image_url'])
                                    : 'assets/images/courses/default-course.jpg';
                            ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                            <div class="course-overlay">
                                <div class="course-preview">
                                    <span class="course-stats">
                                        <i class="fas fa-star"></i> 4.8 (<?php echo rand(50, 200); ?> reviews)
                                    </span>
                                    <span class="course-level">
                                        <i class="fas fa-signal"></i> <?php echo htmlspecialchars($course['level'] ?? 'Beginner'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="course-content">
                            <div class="course-tags">
                                <span class="course-category">
                                    <i class="fas fa-bookmark"></i> 
                                    <?php echo htmlspecialchars($course['category'] ?? 'General'); ?>
                                </span>
                                <span class="course-status">Featured</span>
                            </div>
                            <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                            <div class="course-meta">
                                <div class="instructor-info">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                    <span><?php echo htmlspecialchars($course['instructor_name']); ?></span>
                                </div>
                                <div class="course-stats">
                                    <span><i class="fas fa-users"></i> <?php echo $course['student_count'] ?? 0; ?> enrolled</span>
                                    <span><i class="far fa-clock"></i> <?php echo htmlspecialchars($course['duration'] ?? ''); ?></span>
                                </div>
                            </div>
                            <div class="course-footer">
                                <div class="course-progress">
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo rand(70, 95); ?>%"></div>
                                    </div>
                                    <span class="seats-left">Seats filling fast</span>
                                </div>
                                <a href="pages/course.php?id=<?php echo $course['id']; ?>" class="enroll-btn">
                                    Learn More <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits">
        <div class="section-header">
            <h2>Why Choose Us?</h2>
            <p>Experience the advantages of learning with us</p>
        </div>
        <div class="benefits-grid">
            <div class="benefit-card" data-aos="fade-up">
                <div class="benefit-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3>Expert-Led Learning</h3>
                <p>Learn from industry professionals with real-world experience</p>
                <div class="benefit-hover">
                    <span class="stat">500+</span>
                    <span class="stat-label">Expert Instructors</span>
                </div>
            </div>

            <div class="benefit-card" data-aos="fade-up" data-aos-delay="100">
                <div class="benefit-icon">
                    <i class="fas fa-laptop-code"></i>
                </div>
                <h3>Hands-on Projects</h3>
                <p>Build real-world projects and enhance your portfolio</p>
                <div class="benefit-hover">
                    <span class="stat">1000+</span>
                    <span class="stat-label">Practice Projects</span>
                </div>
            </div>

            <div class="benefit-card" data-aos="fade-up" data-aos-delay="200">
                <div class="benefit-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Flexible Learning</h3>
                <p>Learn at your own pace with lifetime access to courses</p>
                <div class="benefit-hover">
                    <span class="stat">24/7</span>
                    <span class="stat-label">Access</span>
                </div>
            </div>

            <div class="benefit-card" data-aos="fade-up" data-aos-delay="300">
                <div class="benefit-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Community Support</h3>
                <p>Join a vibrant community of learners and mentors</p>
                <div class="benefit-hover">
                    <span class="stat">50K+</span>
                    <span class="stat-label">Active Learners</span>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });
    </script>
</body>
</html>