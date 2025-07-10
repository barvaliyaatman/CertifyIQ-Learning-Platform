<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

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
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/instructor.css">

    <link rel="stylesheet" href="../../assets/css/components/dashboard.css">
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
        .content {
    flex: 1;
    margin-left: 0px;
    padding: 26px;
    background: var(--light-color);
}
    </style>
</head>
<body>
    <div class="dashboard-container">
    <?php include '../../includes/instructor_sidebar.php'; ?>

        <main class="content">
            <div class="page-header">
                <h1>My Profile</h1>
            </div>

            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-photo" onclick="showProfilePhoto()">
                        <img src="<?php 
                            echo !empty($user['profile_image']) 
                                ? '../../' . $user['profile_image'] 
                                : '../../assets/images/default-avatar.jpg'; 
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
                    <?php if (!empty($user['expertise'])): ?>
                    <div class="detail-group">
                        <label>Expertise</label>
                        <p><?php echo htmlspecialchars($user['expertise']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($user['bio'])): ?>
                    <div class="detail-group">
                        <label>Bio</label>
                        <p><?php echo htmlspecialchars($user['bio']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-actions" style="margin-top: 30px; text-align: center;">
                    <a href="edit_profile.php" class="btn btn-primary" style="padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: 600;">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                </div>
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