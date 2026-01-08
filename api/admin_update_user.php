<?php
/**
 * Admin Update User API
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
    $user_id = sanitizeInput($_POST['id']);
    $username = sanitizeInput($_POST['username']);
    $new_password = $_POST['new_password'];
    $role = sanitizeInput($_POST['role']);
    
    if (empty($user_id) || empty($username) || empty($role)) {
        echo json_encode(['success' => false, 'error' => 'User ID, username, and role are required']);
        exit();
    }
    
    if (!in_array($role, ['user', 'admin'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid role']);
        exit();
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if username exists for other users
    $query = "SELECT id FROM users WHERE username = :username AND id != :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'error' => 'Username already exists']);
        exit();
    }
    
    // Update user
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
            exit();
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $query = "UPDATE users SET username = :username, password = :password, role = :role WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':user_id', $user_id);
    } else {
        $query = "UPDATE users SET username = :username, role = :role WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':user_id', $user_id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update user']);
    }
    
} catch (Exception $e) {
    error_log("Admin Update User Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
