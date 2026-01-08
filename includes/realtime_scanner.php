<?php
/**
 * Real-time Scanner Class
 * Educational Security Scanner Dashboard
 */

class RealtimeScanner {
    private $db;
    private $scan_id;
    private $log_file;
    
    public function __construct($database, $scan_id) {
        $this->db = $database;
        $this->scan_id = $scan_id;
        $this->log_file = "../logs/scan_{$scan_id}.log";
        
        // Create logs directory if it doesn't exist
        $logs_dir = dirname($this->log_file);
        if (!is_dir($logs_dir)) {
            mkdir($logs_dir, 0755, true);
        }
    }
    
    public function executeCommand($command, $timeout = 300) {
        $this->logMessage("Starting scan with command: " . $command);
        
        // Set up process
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];
        
        $process = proc_open($command, $descriptorspec, $pipes);
        
        if (!is_resource($process)) {
            $this->logMessage("ERROR: Failed to start process");
            $this->updateScanStatus('failed');
            return false;
        }
        
        // Close stdin
        fclose($pipes[0]);
        
        // Set streams to non-blocking
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        
        $start_time = time();
        $output = '';
        
        while (true) {
            // Check timeout
            if (time() - $start_time > $timeout) {
                $this->logMessage("TIMEOUT: Scan exceeded {$timeout} seconds");
                proc_terminate($process);
                $this->updateScanStatus('failed');
                break;
            }
            
            // Read from stdout
            $stdout_data = fread($pipes[1], 8192);
            if ($stdout_data !== false && $stdout_data !== '') {
                $output .= $stdout_data;
                $this->logMessage($stdout_data);
            }
            
            // Read from stderr
            $stderr_data = fread($pipes[2], 8192);
            if ($stderr_data !== false && $stderr_data !== '') {
                $output .= $stderr_data;
                $this->logMessage("STDERR: " . $stderr_data);
            }
            
            // Check if process is still running
            $status = proc_get_status($process);
            if (!$status['running']) {
                // Process finished, read any remaining output
                $remaining_stdout = stream_get_contents($pipes[1]);
                $remaining_stderr = stream_get_contents($pipes[2]);
                
                if ($remaining_stdout) {
                    $output .= $remaining_stdout;
                    $this->logMessage($remaining_stdout);
                }
                
                if ($remaining_stderr) {
                    $output .= $remaining_stderr;
                    $this->logMessage("STDERR: " . $remaining_stderr);
                }
                
                $exit_code = $status['exitcode'];
                $this->logMessage("Process finished with exit code: " . $exit_code);
                
                if ($exit_code === 0) {
                    $this->updateScanStatus('completed');
                } else {
                    $this->updateScanStatus('failed');
                }
                break;
            }
            
            // Small delay to prevent excessive CPU usage
            usleep(100000); // 0.1 seconds
        }
        
        // Close pipes and process
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        
        return $output;
    }
    
    private function logMessage($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] {$message}\n";
        
        // Write to log file
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Also update the result file path in database if not set
        $this->updateResultFilePath();
    }
    
    private function updateScanStatus($status) {
        try {
            $query = "UPDATE scans SET status = :status WHERE id = :scan_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':scan_id', $this->scan_id);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to update scan status: " . $e->getMessage());
        }
    }
    
    private function updateResultFilePath() {
        try {
            $query = "UPDATE scans SET result_file_path = :log_file WHERE id = :scan_id AND result_file_path IS NULL";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':log_file', $this->log_file);
            $stmt->bindParam(':scan_id', $this->scan_id);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to update result file path: " . $e->getMessage());
        }
    }
    
    public function getLogContent() {
        if (file_exists($this->log_file)) {
            return file_get_contents($this->log_file);
        }
        return '';
    }
}
?>
