<?php
/**
 * Admin Get Users API
 * Educational Security Scanner Dashboard
 */

header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    requireAdmin();
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $users = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users[] = [
            'id' => $row['id'],
            'username' => $row['username'],
            'email' => $row['email'],
            'role' => $row['role'],
            'created_at' => date('M j, Y g:i A', strtotime($row['created_at']))
        ];
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
    
} catch (Exception $e) {
    error_log("Admin Get Users Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error: ' . $e->getMessage()]);
}
?>
