<?php
/**
 * Scanner API - Worker Launcher
 */

header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

if (!isLoggedIn()) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

// CSRF Check
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
if (!verifyCsrfToken($csrf_token)) {
    exit(json_encode(['success' => false, 'error' => 'Invalid Security Token']));
}

try {
    $target_url = sanitizeInput($_POST['target_url']);
    $tool_name = sanitizeInput($_POST['tool_name']);
    
    if (empty($target_url) || empty($tool_name)) throw new Exception('Target URL and tool are required');
    
    $clean_url = validateUrl($target_url);
    if (!$clean_url) throw new Exception('Invalid URL or IP format');
    
    $allowed_tools = ['Port Scan', 'Subdirectories', 'Directory', 'SSL', 'Nikto', 'DNS', 'Webanalyze', 'Ping', 'Traceroute', 'Whois', 'Headers'];
    if (!in_array($tool_name, $allowed_tools)) throw new Exception('Invalid tool selected');
    
    // Directories
    $root_dir = dirname(__DIR__); // /var/www/html
    $results_dir = $root_dir . '/results';
    
    if (!is_dir($results_dir)) { mkdir($results_dir, 0775, true); chmod($results_dir, 0775); }
    
    $timestamp = date('Y-m-d_H-i-s');
    $safe_tool = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($tool_name));
    $safe_url = preg_replace('/[^a-zA-Z0-9]/', '_', $clean_url);
    $filename = "{$safe_tool}_{$timestamp}_{$safe_url}.txt";
    $filepath = $results_dir . '/' . $filename;
    
    // --- ABSOLUTE PATHS (For /var/www/html environment) ---
    $bin_ping = '/usr/bin/ping';
    $bin_nmap = '/usr/bin/nmap';
    $bin_whois = '/usr/bin/whois';
    $bin_traceroute = '/usr/sbin/traceroute';
    $bin_curl = '/usr/bin/curl';
    $bin_dig = '/usr/bin/dig';
    $bin_php = '/usr/bin/php';

    // Construct Command
    $escaped_url = escapeshellarg($clean_url);
    $cmd = "";
    
    switch ($tool_name) {
        case 'Ping': $cmd = "{$bin_ping} -c 4 {$escaped_url}"; break;
        case 'Traceroute': $cmd = "{$bin_traceroute} -m 15 -w 2 {$escaped_url}"; break;
        case 'Whois': $cmd = "{$bin_whois} {$escaped_url}"; break;
        case 'Headers': $cmd = "{$bin_curl} -I -L -k --max-time 10 http://{$escaped_url}"; break;
        case 'Port Scan': $cmd = "{$bin_nmap} -F -T4 -Pn {$escaped_url}"; break;
        case 'DNS': $cmd = "{$bin_dig} ANY {$escaped_url} +noall +answer +stats"; break;
        case 'Directory': $cmd = "dirsearch -u http://{$escaped_url} -e php,html,txt -x 400,404 --format=plain"; break;
        case 'SSL': $cmd = "sslyze {$escaped_url}"; break;
        case 'Nikto': $cmd = "nikto -h {$escaped_url} -Tuning x 123b -maxtime 5m"; break;
        case 'Webanalyze': $cmd = "{$bin_curl} -s -I http://{$escaped_url} | grep -i 'Server\|X-Powered-By'"; break;
        case 'Subdirectories': 
            $wordlist = $root_dir . '/wordlists/subdomains.txt';
            if (!file_exists($wordlist)) {
                if (!is_dir(dirname($wordlist))) mkdir(dirname($wordlist), 0775, true);
                file_put_contents($wordlist, "www\nmail\nftp\nadmin\n");
            }
            $bin_ffuf = file_exists('/usr/local/bin/ffuf') ? '/usr/local/bin/ffuf' : 'ffuf';
            $escaped_path = escapeshellarg($filepath); 
            // Note: ffuf writes to file directly, others use > redirect in process script
            $cmd = "{$bin_ffuf} -w " . escapeshellarg($wordlist) . " -u http://FUZZ.{$escaped_url} -t 50 -mc 200,301,403";
            break;
    }

    // Insert into DB
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO scans (user_id, target, scan_type, command, output_file, status, started_at) 
              VALUES (:user_id, :target, :scan_type, :command, :output_file, 'running', NOW())";
              
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':target' => $clean_url,
        ':scan_type' => $tool_name,
        ':command' => $cmd,
        ':output_file' => $filepath
    ]);
    
    $scan_id = $db->lastInsertId();
    
    // --- LAUNCH BACKGROUND WORKER ---
    $worker_script = $root_dir . '/includes/process_scan.php';
    
    // Command: nohup php /var/www/html/includes/process_scan.php 123 > /dev/null &
    $launch_cmd = "nohup {$bin_php} " . escapeshellarg($worker_script) . " {$scan_id} > /dev/null 2>&1 &";
    exec($launch_cmd);
    
    echo json_encode(['success' => true, 'message' => 'Scan started', 'scan_id' => $scan_id]);

} catch (Exception $e) {
    error_log("Scanner API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
