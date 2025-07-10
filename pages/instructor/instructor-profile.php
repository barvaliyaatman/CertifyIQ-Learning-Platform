<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is logged in and is an instructor
require_login();
if ($_SESSION['user_role'] !== 'instructor') {
    header('Location: ../../index.php');
    exit();
}

// Handle profile update
$update_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $expertise = trim($_POST['expertise']);
    
    // Handle profile image upload
    $profile_image = '';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../../uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $filepath)) {
                $profile_image = 'uploads/profiles/' . $filename;
            }
        }
    }
    
    // Update user profile
    $sql = "UPDATE users SET name = ?, expertise = ?";
    $params = [$name, $expertise];
    
    if (!empty($profile_image)) {
        $sql .= ", profile_image = ?";
        $params[] = $profile_image;
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        $update_message = 'Profile updated successfully!';
        $_SESSION['user_name'] = $name;
    } else {
        $update_message = 'Error updating profile.';
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get instructor statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_courses FROM courses WHERE instructor_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_courses = $stmt->fetch()['total_courses'];

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT e.user_id) as total_students 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.id 
    WHERE c.instructor_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$total_students = $stmt->fetch()['total_students'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_assignments 
    FROM assignments a 
    JOIN courses c ON a.course_id = c.id 
    WHERE c.instructor_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$total_assignments = $stmt->fetch()['total_assignments'];

$stmt = $pdo->prepare("
    SELECT AVG(s.score) as avg_score 
    FROM assignment_submissions s 
    JOIN assignments a ON s.assignment_id = a.id 
    JOIN courses c ON a.course_id = c.id 
    WHERE c.instructor_id = ? AND s.score IS NOT NULL
");
$stmt->execute([$_SESSION['user_id']]);
$avg_score = $stmt->fetch()['avg_score'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Profile - Learning Management System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/instructor.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/components/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .profile-info {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .profile-avatar {
            position: relative;
            width: 150px;
            height: 150px;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .profile-avatar .edit-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #fff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .profile-avatar .edit-overlay:hover {
            transform: scale(1.1);
        }
        
        .profile-details h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .profile-details .role {
            font-size: 1.2em;
            opacity: 0.9;
            margin-bottom: 15px;
        }
        
        .profile-details .expertise {
            font-size: 1.1em;
            opacity: 0.8;
            margin-bottom: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: #667eea;
        }
        
        .stat-card .number {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 0.9em;
        }
        
        .profile-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .profile-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .profile-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.5em;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .recent-activity {
            list-style: none;
            padding: 0;
        }
        
        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #e1e8ed;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
        }
        
        .activity-content h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        
        .activity-content p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .profile-info {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-sections {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <nav class="instructor-sidebar">
            <div class="instructor-brand">
                <h2>Instructor Panel</h2>
                <div class="user-info">
                    <img src="<?php 
                        if (!empty($user['profile_image'])) {
                            echo '../../' . $user['profile_image'];
                        } else {
                            echo '../../assets/images/default-avatar.jpg';
                        }
                    ?>" alt="User Avatar">
                    <h3><?php echo $_SESSION['user_name']; ?></h3>
                </div>
            </div>
            <ul>
                <li><a href="../../index.php">Back to Site</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li class="active"><a href="instructor-profile.php">My Profile</a></li>
                <li><a href="manage_courses.php">My Courses</a></li>
                <li><a href="students.php">My Students</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>

        <main class="content">
            <div class="profile-container">
                <?php if (!empty($update_message)): ?>
                    <div class="alert <?php echo strpos($update_message, 'successfully') !== false ? 'alert-success' : 'alert-error'; ?>">
                        <?php echo htmlspecialchars($update_message); ?>
                    </div>
                <?php endif; ?>

                <div class="profile-header">
                    <div class="profile-info">
                        <div class="profile-avatar">
                            <img src="<?php echo !empty($user['profile_image']) 
                                ? BASE_URL . '/' . $user['profile_image'] 
                                : BASE_URL . '/assets/images/default-avatar.jpg'; ?>" 
                                alt="Profile Photo">
                            <label for="profileImageInput" class="edit-overlay" title="Change Photo">
                                <i class="fas fa-camera"></i>
                            </label>
                        </div>
                        <div class="profile-details">
                            <h1><?php echo htmlspecialchars($user['name']); ?></h1>
                            <p class="role"><?php echo ucfirst($user['role']); ?></p>
                            <?php if (!empty($user['expertise'])): ?>
                                <p class="expertise"><i class="fas fa-star"></i> <?php echo htmlspecialchars($user['expertise']); ?></p>
                            <?php endif; ?>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p><i class="fas fa-calendar"></i> Member since <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="number"><?php echo $total_courses; ?></div>
                        <div class="label">Total Courses</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="number"><?php echo $total_students; ?></div>
                        <div class="label">Total Students</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="number"><?php echo $total_assignments; ?></div>
                        <div class="label">Assignments</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="number"><?php echo number_format($avg_score, 1); ?>%</div>
                        <div class="label">Avg Score</div>
                    </div>
                </div>

                <div class="profile-sections">
                    <div class="profile-section">
                        <h2><i class="fas fa-edit"></i> Edit Profile</h2>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="file" id="profileImageInput" name="profile_image" accept="image/*" style="display: none;">
                            
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small style="color: #666;">Email cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="expertise">Expertise/Subject Area</label>
                                <input type="text" id="expertise" name="expertise" value="<?php echo htmlspecialchars($user['expertise'] ?? ''); ?>" placeholder="e.g., Web Development, Data Science, Mathematics">
                            </div>
                            
                            <div class="form-group">
                                <label for="bio">Bio</label>
                                <textarea id="bio" name="bio" placeholder="Tell us about yourself, your teaching experience, and what makes you passionate about education..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>

                    <div class="profile-section">
                        <h2><i class="fas fa-chart-bar"></i> Recent Activity</h2>
                        <ul class="recent-activity">
                            <li class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="activity-content">
                                    <h4>Course Created</h4>
                                    <p>You created a new course "Advanced Web Development"</p>
                                </div>
                            </li>
                            <li class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="activity-content">
                                    <h4>New Student</h4>
                                    <p>John Doe enrolled in your "JavaScript Fundamentals" course</p>
                                </div>
                            </li>
                            <li class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="activity-content">
                                    <h4>Assignment Graded</h4>
                                    <p>You graded 5 submissions for "Final Project"</p>
                                </div>
                            </li>
                            <li class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="activity-content">
                                    <h4>Course Completed</h4>
                                    <p>Sarah completed your "Python Basics" course</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Handle profile image preview
        document.getElementById('profileImageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImage').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats on scroll
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
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html> 