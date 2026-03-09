<?php
/**
 * Authentication & RBAC Functions
 */

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user's role
function getUserRole() {
    return $_SESSION['user_role'] ?? 'clerk';
}

// Check if current user is an admin
function isAdmin() {
    return isLoggedIn() && getUserRole() === 'admin';
}

// Check if current user is a sales clerk
function isClerk() {
    return isLoggedIn() && getUserRole() === 'clerk';
}

// Redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: index.php?error=access_denied");
        exit();
    }
}

// Login user
function loginUser($email, $password, $conn) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $email = $conn->real_escape_string($email);
    $result = $conn->query("SELECT users_id, name, email, password, role FROM Users WHERE email = '$email'");
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['users_id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role']  = $user['role'] ?? 'clerk';
            return true;
        }
    }
    return false;
}

// Register user
function registerUser($name, $email, $password, $confirmPassword, $conn, $role = 'clerk') {
    $errors = [];
    
    if (empty($name))  $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($password)) $errors[] = "Password is required";
    elseif (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if ($password !== $confirmPassword) $errors[] = "Passwords do not match";
    if (!in_array($role, ['admin','clerk'])) $role = 'clerk';
    
    if (!empty($errors)) return ['success' => false, 'errors' => $errors];
    
    $email_safe = $conn->real_escape_string($email);
    $result = $conn->query("SELECT users_id FROM Users WHERE LOWER(email) = LOWER('$email_safe')");
    if ($result && $result->num_rows > 0) {
        return ['success' => false, 'errors' => ['Email already registered']];
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $name_safe = $conn->real_escape_string($name);
    $role_safe = $conn->real_escape_string($role);
    
    $sql = "INSERT INTO Users (name, email, password, role) VALUES ('$name_safe', '$email_safe', '$hashedPassword', '$role_safe')";
    if ($conn->query($sql)) {
        return ['success' => true, 'message' => 'Account created! Please log in.'];
    } else {
        return ['success' => false, 'errors' => ['Database error: ' . $conn->error]];
    }
}

// Logout user
function logoutUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]);
    }
    
    session_destroy();
    header("Location: login.php", true, 302);
    exit();
}

// Get current user info
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id'    => $_SESSION['user_id'],
            'name'  => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role'  => $_SESSION['user_role'] ?? 'clerk',
        ];
    }
    return null;
}
?>
