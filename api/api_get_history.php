<?php
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
    
    // Selecting columns aliased to match what the frontend expects
    $query = "SELECT id, target as target_url, scan_type as tool, started_at as date, status 
              FROM scans 
              WHERE user_id = :user_id 
              ORDER BY started_at DESC LIMIT 50";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    $history = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $history[] = [
            'id' => $row['id'],
            'target_url' => $row['target_url'],
            'tool' => $row['tool'],
            'date' => date('M j, Y g:i A', strtotime($row['date'])),
            'status' => $row['status']
        ];
    }
    
    echo json_encode(['success' => true, 'history' => $history]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
