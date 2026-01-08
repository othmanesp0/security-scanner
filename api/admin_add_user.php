<?php
/**
 * Admin Add User API
 * Educational Security Scanner Dashboard
 */

header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

try {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $role = sanitizeInput($_POST['role']);
    
    if (empty($username) || empty($password) || empty($role)) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit();
    }
    
    if (!in_array($role, ['user', 'admin'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid role']);
        exit();
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
        exit();
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if username exists
    $query = "SELECT id FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'error' => 'Username already exists']);
        exit();
    }
    
    // Create user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $query = "INSERT INTO users (username, password, role) VALUES (:username, :password, :role)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':role', $role);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User created successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create user']);
    }
    
} catch (Exception $e) {
    error_log("Admin Add User Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
