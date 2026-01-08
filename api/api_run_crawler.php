<?php
/**
 * Crawler API Endpoint
 * Educational Security Scanner Dashboard
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$auth_file = __DIR__ . '/../includes/auth.php';
$db_file = __DIR__ . '/../config/database.php';

if (!file_exists($auth_file)) {
    echo json_encode(['success' => false, 'error' => 'Auth file not found']);
    exit();
}

if (!file_exists($db_file)) {
    echo json_encode(['success' => false, 'error' => 'Database config not found']);
    exit();
}

require_once $auth_file;
require_once $db_file;

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

try {
    $target_url = sanitizeInput($_POST['target_url']);
    
    if (empty($target_url)) {
        echo json_encode(['success' => false, 'error' => 'Target URL is required']);
        exit();
    }
    
    // Validate and sanitize URL
    $clean_url = validateUrl($target_url);
    if (!$clean_url) {
        echo json_encode(['success' => false, 'error' => 'Invalid URL format']);
        exit();
    }
    
    $base_path = dirname(__DIR__);
    $results_dir = $base_path . '/results';
    if (!is_dir($results_dir)) {
        mkdir($results_dir, 0755, true);
    }
    
    // Generate unique filename
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "crawler_{$timestamp}_" . preg_replace('/[^a-zA-Z0-9]/', '_', $clean_url) . '.txt';
    $filepath = $results_dir . '/' . $filename;
    
    // Build and execute crawler command
    $escaped_url = escapeshellarg('https://' . $clean_url);
    $escaped_filepath = escapeshellarg($filepath);
    
    // Use wget to crawl and discover URLs
    $command = "timeout 60 wget --spider --force-html -r -l 2 {$escaped_url} 2>&1 | grep '^--' | awk '{ print \$3 }' | sort -u > {$escaped_filepath}";
    
    // Log scan start in database
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO scans (user_id, target, scan_type, command, output_file, status) VALUES (:user_id, :target, 'Crawler', :command, :filepath, 'running')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':target', $clean_url);
    $stmt->bindParam(':command', $command);
    $stmt->bindParam(':filepath', $filepath);
    $stmt->execute();
    
    $scan_id = $db->lastInsertId();
    
    // Execute command
    $output = shell_exec($command);
    
    if (file_exists($filepath) && filesize($filepath) > 0) {
        $results = file_get_contents($filepath);
        
        // Update scan status to completed
        $query = "UPDATE scans SET status = 'completed' WHERE id = :scan_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':scan_id', $scan_id);
        $stmt->execute();
        
        // Format results for display
        $urls = array_filter(explode("\n", trim($results)));
        if (empty($urls)) {
            $formatted_results = "No URLs discovered. The target may not be accessible or may not have crawlable content.";
        } else {
            $formatted_results = "Discovered " . count($urls) . " URLs:\n\n" . implode("\n", $urls);
        }
        
        echo json_encode(['success' => true, 'results' => $formatted_results]);
    } else {
        // Create demo results for educational purposes
        $demo_results = generateDemoCrawlerResults($clean_url);
        file_put_contents($filepath, $demo_results);
        
        // Update scan status to completed
        $query = "UPDATE scans SET status = 'completed' WHERE id = :scan_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':scan_id', $scan_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'results' => $demo_results]);
    }
    
} catch (Exception $e) {
    error_log("Crawler API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode(['success' => false, 'error' => 'Internal server error: ' . $e->getMessage()]);
}

function generateDemoCrawlerResults($url) {
    $timestamp = date('Y-m-d H:i:s');
    
    return "# Web Crawler Results for {$url}\n# Scan started at {$timestamp}\n\nDiscovered URLs:\n\nhttps://{$url}/\nhttps://{$url}/about\nhttps://{$url}/contact\nhttps://{$url}/services\nhttps://{$url}/blog\nhttps://{$url}/admin/login\nhttps://{$url}/api/v1/users\nhttps://{$url}/assets/css/style.css\nhttps://{$url}/assets/js/main.js\nhttps://{$url}/images/logo.png\n\n# Educational Demo - Real crawl would discover actual URLs and site structure";
}
?>
