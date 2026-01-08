<?php
/**
 * Admin Terminate Scan API
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
    $scan_id = sanitizeInput($_POST['scan_id']);
    
    if (empty($scan_id)) {
        echo json_encode(['success' => false, 'error' => 'Scan ID is required']);
        exit();
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Update scan status to failed (terminated)
    $query = "UPDATE scans SET status = 'failed' WHERE id = :scan_id AND status = 'running'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':scan_id', $scan_id);
    
    if ($stmt->execute() && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Scan terminated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Scan not found or not running']);
    }
    
} catch (Exception $e) {
    error_log("Admin Terminate Scan Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
