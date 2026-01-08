<?php
/**
 * Completed Scans API Endpoint - Schema Fixed
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
    $database = new Database();
    $db = $database->getConnection();
    
    // SCHEMA FIX: Aliasing columns to match what the frontend expects
    // target -> target_url
    // scan_type -> tool
    // started_at -> date
    $query = "SELECT id, target as target_url, scan_type as tool, started_at as date 
              FROM scans 
              WHERE user_id = :user_id AND status = 'completed' 
              ORDER BY started_at DESC LIMIT 20";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    $scans = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $scans[] = [
            'id' => $row['id'],
            'target_url' => $row['target_url'],
            'tool' => $row['tool'],
            'date' => date('M j, Y g:i A', strtotime($row['date']))
        ];
    }
    
    echo json_encode(['success' => true, 'scans' => $scans]);
    
} catch (Exception $e) {
    error_log("Completed Scans API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
