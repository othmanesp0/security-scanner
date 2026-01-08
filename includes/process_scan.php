<?php
// security-scanner/includes/process_scan.php
if (php_sapi_name() !== 'cli') exit;

// 1. Setup Environment
$root_dir = dirname(__DIR__); 
require_once $root_dir . '/config/database.php';
require_once $root_dir . '/includes/ai_helper.php';

// Logging
$log_dir = $root_dir . '/logs';
if (!is_dir($log_dir)) mkdir($log_dir, 0775, true);
$log_file = $log_dir . '/process.log';

function log_msg($msg) {
    global $log_file;
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $msg\n", FILE_APPEND);
}

$scan_id = isset($argv[1]) ? intval($argv[1]) : 0;
if ($scan_id <= 0) exit;

try {
    $database = new Database();
    $db = $database->getConnection();

    // Fetch Scan Details
    $stmt = $db->prepare("SELECT command, output_file, scan_type FROM scans WHERE id = :id");
    $stmt->execute([':id' => $scan_id]);
    $scan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$scan) exit;

    $command = $scan['command'];
    $output_file = $scan['output_file'];
    $tool_name = $scan['scan_type'];

    log_msg("Starting Scan #$scan_id ($tool_name)");

    // Execute Command
    $final_cmd = "$command > " . escapeshellarg($output_file) . " 2>&1";
    exec($final_cmd, $output, $return_var);
    
    // === AI ANALYSIS ===
    log_msg("Starting AI Analysis for #$scan_id ($tool_name)");
    
    if (file_exists($output_file)) {
        $scan_content = file_get_contents($output_file);
        
        // Analyze if we have ANY meaningful output (more than 10 chars)
        if (strlen(trim($scan_content)) > 10) {
            $ai_json = analyzeWithGemini($scan_content, $tool_name);
            
            $ai_file = str_replace('.txt', '_ai.json', $output_file);
            file_put_contents($ai_file, $ai_json);
            log_msg("AI Report saved to $ai_file");
        } else {
            log_msg("Output too short for AI analysis.");
        }
    }
    // ===================

    // Update Status
    $status = 'completed';
    $update = $db->prepare("UPDATE scans SET status = :status, completed_at = NOW() WHERE id = :id");
    $update->execute([':status' => $status, ':id' => $scan_id]);

} catch (Exception $e) {
    log_msg("CRITICAL EXCEPTION: " . $e->getMessage());
}
?>
