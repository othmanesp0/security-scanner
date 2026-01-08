<?php
// security-scanner/debug_system.php
header('Content-Type: text/plain');

echo "=== SYSTEM DIAGNOSTIC TOOL ===\n\n";

// 1. Check User and Path
echo "Current User: " . exec('whoami') . "\n";
echo "Current Script Path: " . __DIR__ . "\n";
echo "Results Directory Target: " . __DIR__ . "/results\n\n";

// 2. Check Permissions
$results_dir = __DIR__ . '/results';
if (!is_dir($results_dir)) {
    echo "[ERROR] Results directory does not exist!\n";
    if (mkdir($results_dir, 0775, true)) {
        echo "[INFO] Created results directory.\n";
    } else {
        echo "[FATAL] Failed to create results directory.\n";
    }
}

if (is_writable($results_dir)) {
    echo "[PASS] Results directory is writable.\n";
    
    // Test Write
    $test_file = $results_dir . '/test_write.txt';
    if (file_put_contents($test_file, 'Write Test Successful')) {
        echo "[PASS] Successfully wrote test file.\n";
        unlink($test_file);
    } else {
        echo "[FAIL] PHP reported writable, but failed to write file.\n";
    }
} else {
    echo "[FAIL] Results directory is NOT writable by this user.\n";
    echo "Permissions: " . substr(sprintf('%o', fileperms($results_dir)), -4) . "\n";
    echo "Owner: " . posix_getpwuid(fileowner($results_dir))['name'] . "\n";
}

echo "\n=== TOOL CHECK ===\n";

// 3. Check Tools
$tools = ['ping', 'nmap', 'whois', 'traceroute', 'curl', 'php'];
foreach ($tools as $tool) {
    $path = exec("which $tool");
    if ($path) {
        echo "[PASS] $tool found at: $path\n";
    } else {
        echo "[FAIL] $tool NOT found in PATH.\n";
    }
}

echo "\n=== EXECUTION TEST ===\n";

// 4. Test Background Execution (simulated)
$output_file = $results_dir . '/debug_exec_test.txt';
$cmd = "ping -c 1 8.8.8.8"; // Try to ping Google DNS once
$full_cmd = "($cmd) > " . escapeshellarg($output_file) . " 2>&1";

echo "Running: $full_cmd\n";
exec($full_cmd, $output, $return_var);

echo "Return Code: $return_var\n";
if (file_exists($output_file)) {
    $content = file_get_contents($output_file);
    echo "Output File Created. Size: " . filesize($output_file) . " bytes\n";
    echo "Content Preview:\n---\n" . substr($content, 0, 200) . "\n---\n";
} else {
    echo "[FAIL] Output file was NOT created.\n";
}

?>
