<?php
/**
 * Admin Delete User API
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
    $user_id = sanitizeInput($_POST['user_id']);
    
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'error' => 'User ID is required']);
        exit();
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user exists and is not admin
    $query = "SELECT username FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user['username'] === 'admin') {
        echo json_encode(['success' => false, 'error' => 'Cannot delete admin user']);
        exit();
    }
    
    // Delete user (scans will be deleted due to foreign key constraint)
    $query = "DELETE FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete user']);
    }
    
} catch (Exception $e) {
    error_log("Admin Delete User Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
