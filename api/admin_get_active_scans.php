<?php
/**
 * Active Scans API Endpoint
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
    $database = new Database();
    $db = $database->getConnection();
    
    // Get running scans for current user
    $query = "SELECT id, target, scan_type, created_at, output_file FROM scans WHERE user_id = :user_id AND status = 'running' ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    $scans = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $scan = [
            'id' => $row['id'],
            'target_url' => $row['target'], // Map target to target_url for frontend compatibility
            'tool' => $row['scan_type'], // Map scan_type to tool for frontend compatibility
            'date' => date('M j, Y g:i A', strtotime($row['created_at'])), // Updated to use created_at
            'output' => 'Scan in progress...\nInitializing ' . $row['scan_type'] . ' for ' . $row['target'] . '\nPlease wait...'
        ];
        
        // Try to read partial output if file exists
        if ($row['output_file'] && file_exists($row['output_file'])) { // Updated to use output_file
            $partial_output = file_get_contents($row['output_file']);
            if (!empty($partial_output)) {
                $scan['output'] = $partial_output;
            }
        }
        
        $scans[] = $scan;
    }
    
    echo json_encode(['success' => true, 'scans' => $scans]);
    
} catch (Exception $e) {
    error_log("Active Scans API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
