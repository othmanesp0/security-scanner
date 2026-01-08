<?php
/**
 * Admin Get Statistics API
 * Educational Security Scanner Dashboard
 */

header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

requireAdmin();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stats = [];
    
    // Total users
    $query = "SELECT COUNT(*) as count FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total scans
    $query = "SELECT COUNT(*) as count FROM scans";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_scans'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Active scans
    $query = "SELECT COUNT(*) as count FROM scans WHERE status = 'running'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['active_scans'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Success rate
    $query = "SELECT 
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(*) as total
              FROM scans 
              WHERE status IN ('completed', 'failed')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['success_rate'] = $result['total'] > 0 ? round(($result['completed'] / $result['total']) * 100, 1) : 0;
    
    // Tool usage
    $query = "SELECT tool, COUNT(*) as count FROM scans GROUP BY tool ORDER BY count DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tool_usage = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tool_usage[$row['tool']] = (int)$row['count'];
    }
    $stats['tool_usage'] = $tool_usage;
    
    // Recent activity
    $query = "SELECT s.target_url, s.tool, s.date, u.username 
              FROM scans s 
              JOIN users u ON s.user_id = u.id 
              ORDER BY s.date DESC 
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_activity = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $recent_activity[] = [
            'username' => $row['username'],
            'tool' => $row['tool'],
            'target_url' => $row['target_url'],
            'date' => date('M j, g:i A', strtotime($row['date']))
        ];
    }
    $stats['recent_activity'] = $recent_activity;
    
    echo json_encode(['success' => true, 'stats' => $stats]);
    
} catch (Exception $e) {
    error_log("Admin Get Statistics Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
