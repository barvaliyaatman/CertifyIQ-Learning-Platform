<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is instructor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $expertise = trim($_POST['expertise'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $linkedin = trim($_POST['linkedin'] ?? '');
        $twitter = trim($_POST['twitter'] ?? '');

        // Validate required fields
        if (empty($name) || empty($email)) {
            throw new Exception('Name and email are required');
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            throw new Exception('Email is already taken by another user');
        }

        // Handle profile image upload
        $profile_image = $user['profile_image']; // Keep existing image by default
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                throw new Exception('Invalid image format. Please use JPG, PNG, or GIF');
            }

            if ($_FILES['profile_image']['size'] > $max_size) {
                throw new Exception('Image size too large. Maximum size is 5MB');
            }

            $upload_dir = '../../uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $file_name = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
            $file_path = 'uploads/profiles/' . $file_name;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $file_name)) {
                // Delete old profile image if it exists
                if (!empty($user['profile_image']) && file_exists('../../' . $user['profile_image'])) {
                    unlink('../../' . $user['profile_image']);
                }
                $profile_image = $file_path;
            } else {
                throw new Exception('Failed to upload image');
            }
        }

        // Update user profile
        $stmt = $pdo->prepare("
            UPDATE users 
            SET name = ?, email = ?, expertise = ?, bio = ?, phone = ?, 
                website = ?, linkedin = ?, twitter = ?, profile_image = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $name, $email, $expertise, $bio, $phone,
            $website, $linkedin, $twitter, $profile_image, $_SESSION['user_id']
        ]);

        $success_message = 'Profile updated successfully!';
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile - Learning Management System</title>
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/instructor.css">
    <link rel="stylesheet" href="../../assets/css/components/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .edit-profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }
        
        .page-header h1 {
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .profile-image-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .current-profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid #667eea;
            margin-bottom: 20px;
            object-fit: cover;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .file-input-label:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
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
        
        .social-links {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .social-links {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../includes/instructor_sidebar.php'; ?>
        
        <main class="content">
            <div class="edit-profile-container">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-user-edit"></i>
                        Edit Profile
                    </h1>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Profile Image Section -->
                        <div class="form-section">
                            <h3><i class="fas fa-camera"></i> Profile Image</h3>
                            <div class="profile-image-section">
                                <img src="<?php 
                                    echo !empty($user['profile_image']) 
                                        ? '../../' . $user['profile_image'] 
                                        : '../../assets/images/default-avatar.jpg'; 
                                ?>" alt="Profile Image" class="current-profile-image" id="profilePreview">
                                
                                <div class="file-input-wrapper">
                                    <input type="file" name="profile_image" id="profileImage" accept="image/*">
                                    <label for="profileImage" class="file-input-label">
                                        <i class="fas fa-upload"></i>
                                        Change Profile Image
                                    </label>
                                </div>
                                <p style="color: #666; margin-top: 10px;">
                                    <small>Maximum file size: 5MB. Supported formats: JPG, PNG, GIF</small>
                                </p>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="form-section">
                            <h3><i class="fas fa-user"></i> Basic Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Full Name *</label>
                                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="expertise">Area of Expertise</label>
                                    <input type="text" id="expertise" name="expertise" value="<?php echo htmlspecialchars($user['expertise'] ?? ''); ?>" placeholder="e.g., Web Development, Data Science">
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+1 (555) 123-4567">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="bio">Bio</label>
                                <textarea id="bio" name="bio" placeholder="Tell students about your background, experience, and teaching philosophy..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- Social Links -->
                        <div class="form-section">
                            <h3><i class="fas fa-share-alt"></i> Social Links</h3>
                            <div class="social-links">
                                <div class="form-group">
                                    <label for="website">Website</label>
                                    <input type="url" id="website" name="website" value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" placeholder="https://yourwebsite.com">
                                </div>
                                <div class="form-group">
                                    <label for="linkedin">LinkedIn</label>
                                    <input type="url" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($user['linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/in/yourprofile">
                                </div>
                                <div class="form-group">
                                    <label for="twitter">Twitter</label>
                                    <input type="url" id="twitter" name="twitter" value="<?php echo htmlspecialchars($user['twitter'] ?? ''); ?>" placeholder="https://twitter.com/yourhandle">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="profile.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Preview profile image before upload
        document.getElementById('profileImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 