<?php
/**
 * Real-time Scan Log API Endpoint
 * Educational Security Scanner Dashboard
 */

header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $scan_id = isset($_GET['scan_id']) ? sanitizeInput($_GET['scan_id']) : null;
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($scan_id) {
        // Get specific scan log
        $query = "SELECT result_file_path, status FROM scans WHERE id = :scan_id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':scan_id', $scan_id);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $scan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $log_content = '';
            if ($scan['result_file_path'] && file_exists($scan['result_file_path'])) {
                $log_content = file_get_contents($scan['result_file_path']);
            }
            
            echo json_encode([
                'success' => true,
                'log' => $log_content,
                'status' => $scan['status']
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Scan not found']);
        }
    } else {
        // Get all running scans for user
        $query = "SELECT id, result_file_path, status FROM scans WHERE user_id = :user_id AND status = 'running'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        $logs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $log_content = '';
            if ($row['result_file_path'] && file_exists($row['result_file_path'])) {
                $log_content = file_get_contents($row['result_file_path']);
            }
            
            $logs[] = [
                'scan_id' => $row['id'],
                'log' => $log_content,
                'status' => $row['status']
            ];
        }
        
        echo json_encode(['success' => true, 'logs' => $logs]);
    }
    
} catch (Exception $e) {
    error_log("Scan Log API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
