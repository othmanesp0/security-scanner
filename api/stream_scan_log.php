<?php
/**
 * Stream Scan Log API (Server-Sent Events)
 * Educational Security Scanner Dashboard
 */

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once '../includes/auth.php';
require_once '../config/database.php';

if (!isLoggedIn()) {
    echo "event: error\n";
    echo "data: Unauthorized\n\n";
    exit();
}

$scan_id = isset($_GET['scan_id']) ? sanitizeInput($_GET['scan_id']) : null;

if (!$scan_id) {
    echo "event: error\n";
    echo "data: Scan ID required\n\n";
    exit();
}

// Verify user has access to this scan
$database = new Database();
$db = $database->getConnection();

$query = "SELECT result_file_path, status FROM scans WHERE id = :scan_id";
if ($_SESSION['role'] !== 'admin') {
    $query .= " AND user_id = :user_id";
}

$stmt = $db->prepare($query);
$stmt->bindParam(':scan_id', $scan_id);
if ($_SESSION['role'] !== 'admin') {
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
}
$stmt->execute();

if ($stmt->rowCount() === 0) {
    echo "event: error\n";
    echo "data: Scan not found\n\n";
    exit();
}

$scan = $stmt->fetch(PDO::FETCH_ASSOC);
$log_file = "../logs/scan_{$scan_id}.log";

$last_size = 0;
$max_iterations = 300; // 5 minutes max (300 * 1 second)
$iterations = 0;

while ($iterations < $max_iterations) {
    // Check if scan is still running
    $stmt->execute();
    $current_scan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($current_scan['status'] !== 'running') {
        echo "event: status\n";
        echo "data: " . json_encode(['status' => $current_scan['status']]) . "\n\n";
        
        if ($current_scan['status'] === 'completed') {
            // Send final log content
            if (file_exists($log_file)) {
                $content = file_get_contents($log_file);
                echo "event: log\n";
                echo "data: " . json_encode(['content' => $content, 'final' => true]) . "\n\n";
            }
        }
        break;
    }
    
    // Check for new log content
    if (file_exists($log_file)) {
        $current_size = filesize($log_file);
        
        if ($current_size > $last_size) {
            $handle = fopen($log_file, 'r');
            fseek($handle, $last_size);
            $new_content = fread($handle, $current_size - $last_size);
            fclose($handle);
            
            if (!empty($new_content)) {
                echo "event: log\n";
                echo "data: " . json_encode(['content' => $new_content, 'final' => false]) . "\n\n";
            }
            
            $last_size = $current_size;
        }
    }
    
    // Send heartbeat
    echo "event: heartbeat\n";
    echo "data: " . time() . "\n\n";
    
    ob_flush();
    flush();
    
    sleep(1);
    $iterations++;
}

echo "event: close\n";
echo "data: Stream ended\n\n";
?>
