<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

if (!isLoggedIn()) exit(json_encode(['success' => false, 'error' => 'Unauthorized']));

try {
    $scan_id = sanitizeInput($_POST['scan_id']);
    $database = new Database();
    $db = $database->getConnection();
    
    // SCHEMA FIX: output_file instead of result_file_path
    $query = "SELECT output_file, status FROM scans WHERE id = :scan_id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':scan_id' => $scan_id, ':user_id' => $_SESSION['user_id']]);
    
    if ($stmt->rowCount() === 0) exit(json_encode(['success' => false, 'error' => 'Scan not found']));
    
    $scan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if file exists
    if ($scan['output_file'] && file_exists($scan['output_file'])) {
        $results = file_get_contents($scan['output_file']);
        echo json_encode(['success' => true, 'results' => $results]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Results file not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
