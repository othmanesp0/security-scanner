<?php
/**
 * Admin Delete Scan API
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
    
    // Get scan details to delete result file
    $query = "SELECT result_file_path FROM scans WHERE id = :scan_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':scan_id', $scan_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $scan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete result file if it exists
        if ($scan['result_file_path'] && file_exists($scan['result_file_path'])) {
            unlink($scan['result_file_path']);
        }
        
        // Delete scan record
        $query = "DELETE FROM scans WHERE id = :scan_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':scan_id', $scan_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Scan deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete scan']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Scan not found']);
    }
    
} catch (Exception $e) {
    error_log("Admin Delete Scan Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
