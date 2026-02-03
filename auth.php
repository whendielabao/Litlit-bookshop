<?php
/**
 * Authentication Functions
 */

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Login user
function loginUser($email, $password, $conn) {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $email = $conn->real_escape_string($email);
    $result = $conn->query("SELECT users_id, name, email, password FROM Users WHERE email = '$email'");
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['users_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            return true;
        }
    }
    return false;
}

// Register user
function registerUser($name, $email, $password, $confirmPassword, $conn) {
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Check if email already exists (case-insensitive)
    $email_safe = $conn->real_escape_string($email);
    $result = $conn->query("SELECT users_id FROM Users WHERE LOWER(email) = LOWER('$email_safe')");
    
    if ($result && $result->num_rows > 0) {
        return ['success' => false, 'errors' => ['Email already registered']];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $name = $conn->real_escape_string($name);
    
    // Insert user
    $sql = "INSERT INTO Users (name, email, password) VALUES ('$name', '$email', '$hashedPassword')";
    
    if ($conn->query($sql)) {
        return ['success' => true, 'message' => 'Registration successful! Please log in.'];
    } else {
        return ['success' => false, 'errors' => ['Database error: ' . $conn->error]];
    }
}

// Logout user
function logoutUser() {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear all session variables
    $_SESSION = [];
    
    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]);
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login
    header("Location: login.php", true, 302);
    exit();
}

// Get current user info
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email']
        ];
    }
    return null;
}
?>
