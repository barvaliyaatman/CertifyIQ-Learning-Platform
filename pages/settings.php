<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Ensure user is logged in
require_login();

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $expertise = $_POST['expertise'] ?? '';

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Update basic info
        $updateFields = ["name = ?", "email = ?"];
        $params = [$name, $email];

        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $target_dir = "../uploads/profiles/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $file_name = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $file_name;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                $updateFields[] = "profile_image = ?";
                $params[] = 'uploads/profiles/' . $file_name;
            }
        }

        // Update expertise if user is instructor
        if ($user['role'] === 'instructor') {
            $updateFields[] = "expertise = ?";
            $params[] = $expertise;
        }

        // Handle password change
        if (!empty($current_password) && !empty($new_password)) {
            if (password_verify($current_password, $user['password'])) {
                $updateFields[] = "password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            } else {
                throw new Exception("Current password is incorrect");
            }
        }

        $params[] = $_SESSION['user_id'];
        $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $pdo->commit();
        $success = "Settings updated successfully!";
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Settings - Learning Management System</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/components/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>

        <main class="content">
            <div class="page-header">
                <h1>Settings</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="settings-container">
                <form method="POST" enctype="multipart/form-data" class="settings-form">
                    <div class="form-section">
                        <h2>Profile Picture</h2>
                        <div class="profile-upload">
                            <div class="current-photo">
                                <img src="<?php 
                                    echo !empty($user['profile_image']) 
                                        ? '../' . $user['profile_image'] 
                                        : '../assets/images/default-avatar.jpg'; 
                                ?>" alt="Profile Picture" id="profilePreview">
                            </div>
                            <div class="upload-controls">
                                <label for="profile_image" class="upload-btn">
                                    <i class="fas fa-camera"></i> Change Photo
                                </label>
                                <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display: none;">
                                <p class="upload-info">Maximum file size: 2MB (JPG, PNG)</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2>Personal Information</h2>
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <?php if ($user['role'] === 'instructor'): ?>
                            <div class="form-group">
                                <label for="expertise">Area of Expertise</label>
                                <input type="text" id="expertise" name="expertise" value="<?php echo htmlspecialchars($user['expertise'] ?? ''); ?>">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-section">
                        <h2>Change Password</h2>
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn save-btn">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Profile image preview
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>