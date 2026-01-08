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
    
    // SCHEMA FIX: Aliasing columns to match frontend expectations
    // target -> target_url
    // scan_type -> tool
    // started_at -> date
    // output_file -> result_file_path
    $query = "SELECT id, target as target_url, scan_type as tool, started_at as date, output_file as result_file_path 
              FROM scans 
              WHERE user_id = :user_id AND status = 'running' 
              ORDER BY started_at DESC";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    $scans = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $scan = [
            'id' => $row['id'],
            'target_url' => $row['target_url'],
            'tool' => $row['tool'],
            'date' => date('M j, Y g:i A', strtotime($row['date'])),
            'output' => "Scan in progress...\nRunning: " . $row['tool']
        ];
        
        if ($row['result_file_path'] && file_exists($row['result_file_path'])) {
            $partial_output = file_get_contents($row['result_file_path']);
            if (!empty($partial_output)) {
                // Limit output size to prevent UI freeze
                $scan['output'] = substr($partial_output, -2000); 
            }
        }
        $scans[] = $scan;
    }
    
    echo json_encode(['success' => true, 'scans' => $scans]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
