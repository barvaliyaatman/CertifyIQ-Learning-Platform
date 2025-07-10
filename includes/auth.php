<?php
require_once __DIR__ . '/../config/config.php';
// Only start session if one hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function register_user($name, $email, $password, $role, $expertise = '', $profile_image = '') {
    global $pdo;
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, expertise, profile_image) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$name, $email, $hashed_password, $role, $expertise, $profile_image]);
}

function login_user($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    return false;
}

// Remove session_start() since it's already called in index.php
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /lms0.1/pages/login.php");
        exit();
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function logout_user() {
    session_destroy();
    header("Location: " . ROOT_URL . "pages/login.php");
    exit();
}
?>