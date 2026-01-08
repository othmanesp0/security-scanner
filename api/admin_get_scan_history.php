<?php
/**
 * Admin Get Scan History API
 * Educational Security Scanner Dashboard
 */

header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

requireAdmin();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT s.id, s.target_url, s.tool, s.date, s.status, u.username 
              FROM scans s 
              JOIN users u ON s.user_id = u.id 
              ORDER BY s.date DESC 
              LIMIT 100";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $history = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $history[] = [
            'id' => $row['id'],
            'target_url' => $row['target_url'],
            'tool' => $row['tool'],
            'username' => $row['username'],
            'date' => date('M j, Y g:i A', strtotime($row['date'])),
            'status' => $row['status']
        ];
    }
    
    echo json_encode(['success' => true, 'history' => $history]);
    
} catch (Exception $e) {
    error_log("Admin Get Scan History Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
