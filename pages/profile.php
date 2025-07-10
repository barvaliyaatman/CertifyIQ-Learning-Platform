<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Ensure user is logged in
require_login();

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile - Learning Management System</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/components/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .profile-photo {
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .profile-photo:hover {
            transform: scale(1.05);
        }
        #profileModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            cursor: pointer;
        }
        #profileModal img {
            max-width: 90%;
            max-height: 90vh;
            margin: auto;
            display: block;
            position: relative;
            top: 50%;
            transform: translateY(-50%);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="content">
            <div class="page-header">
                <h1>My Profile</h1>
            </div>

            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-photo" onclick="showProfilePhoto()">
                        <img src="<?php 
                            echo !empty($user['profile_image']) 
                                ? '../' . $user['profile_image'] 
                                : '../assets/images/default-avatar.jpg'; 
                        ?>" alt="Profile Picture">
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="role"><?php echo ucfirst($user['role']); ?></p>
                        <?php if ($user['role'] === 'instructor' && !empty($user['expertise'])): ?>
                            <p class="expertise"><?php echo htmlspecialchars($user['expertise']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-details">
                    <div class="detail-group">
                        <label>Email</label>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div class="detail-group">
                        <label>Member Since</label>
                        <p><?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                    
                    <?php if ($user['role'] === 'student'): ?>
                        <?php
                        // Get quiz score
                        $stmt = $pdo->prepare("SELECT score FROM placement_quiz_attempts WHERE user_id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $quizResult = $stmt->fetch();
                        ?>
                        <div class="detail-group">
                            <label>Placement Quiz Score & Course Discounts</label>
                            
                            <?php if ($quizResult): ?>
                                <?php 
                                $score = $quizResult['score'];
                                $discount = 0;
                                if ($score >= 80) {
                                    $discount = 30;
                                    $class = 'high-score';
                                } elseif ($score >= 65) {
                                    $discount = 20;
                                    $class = 'mid-score';
                                } elseif ($score >= 50) {
                                    $discount = 10;
                                    $class = 'low-score';
                                }
                                
                                // Update user's discount in database
                                $updateStmt = $pdo->prepare("UPDATE users SET discount_percentage = ? WHERE id = ?");
                                $updateStmt->execute([$discount, $_SESSION['user_id']]);
                                ?>
                                <p>Quiz Score: <?php echo $score; ?>%</p>
                                <?php if ($discount > 0): ?>
                                    <p class="<?php echo $class; ?>">
                                        <i class="fas fa-tag"></i> 
                                        You have unlocked <?php echo $discount; ?>% discount on all courses!
                                        <br>
                                        <small>This discount will be automatically applied at checkout.</small>
                                    </p>
                                <?php else: ?>
                                    <p class="no-score">
                                        <i class="fas fa-times-circle"></i> 
                                        Score too low for course discounts. Minimum required: 50%
                                    </p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p>
                                    <i class="fas fa-gift"></i>
                                    <a href="placement-quiz.php" class="quiz-link">Take placement quiz</a> 
                                    to unlock up to 30% discount on all courses!
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <style>
                            .high-score { color: #28a745; font-weight: bold; }
                            .mid-score { color: #17a2b8; font-weight: bold; }
                            .low-score { color: #ffc107; font-weight: bold; }
                            .no-score { color: #dc3545; }
                            .detail-group p { margin-bottom: 8px; }
                            .detail-group small { color: #666; font-style: italic; }
                            .fas { margin-right: 5px; }
                        </style>
                    <?php endif; ?>
                </div>

                <!-- Add this CSS in the existing style tag -->
                <style>
                    .quiz-link {
                        color: #007bff;
                        text-decoration: none;
                        font-weight: bold;
                    }
                    .quiz-link:hover {
                        text-decoration: underline;
                    }
                    .detail-group {
                        margin-bottom: 20px;
                        padding: 15px;
                        background: #f8f9fa;
                        border-radius: 8px;
                    }
                    .detail-group label {
                        font-weight: bold;
                        color: #333;
                        display: block;
                        margin-bottom: 5px;
                    }
                    .detail-group p {
                        margin: 0;
                        color: #666;
                    }
                </style>
            </div>
        </main>
    </div>

    <!-- Profile Photo Modal -->
    <div id="profileModal" onclick="hideProfileModal()">
        <img src="" alt="Profile Picture" id="modalImage">
    </div>

    <script>
        function showProfilePhoto() {
            const modal = document.getElementById('profileModal');
            const modalImg = document.getElementById('modalImage');
            const profileImg = document.querySelector('.profile-photo img');
            
            modalImg.src = profileImg.src;
            modal.style.display = 'block';
        }

        function hideProfileModal() {
            document.getElementById('profileModal').style.display = 'none';
        }
    </script>
</body>
</html>