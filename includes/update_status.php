<?php
// includes/update_status.php
if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

require_once __DIR__ . '/../config/database.php';

if ($argc < 3) {
    die("Usage: php update_status.php <scan_id> <status>\n");
}

$scan_id = intval($argv[1]);
$status = $argv[2];

// Validate status against Enum in DB schema
$allowed_status = ['running', 'completed', 'failed'];
if (!in_array($status, $allowed_status)) {
    die("Invalid status\n");
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE scans SET status = :status WHERE id = :scan_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':scan_id', $scan_id);
    $stmt->execute();
    echo "Scan $scan_id updated to $status\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
