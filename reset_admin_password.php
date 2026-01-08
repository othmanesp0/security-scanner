<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Generate new password hash for "admin"
    $new_password = password_hash('admin', PASSWORD_DEFAULT);
    
    // Update admin user password
    $query = "UPDATE users SET password = ? WHERE username = 'admin'";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$new_password])) {
        echo "<h2>✅ Admin Password Reset Successful!</h2>";
        echo "<p><strong>Username:</strong> admin</p>";
        echo "<p><strong>Password:</strong> admin</p>";
        echo "<p><a href='login.php'>Go to Login Page</a></p>";
        echo "<p style='color: red;'><strong>Important:</strong> Delete this file after use for security!</p>";
    } else {
        echo "<h2>❌ Failed to reset password</h2>";
        print_r($stmt->errorInfo());
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>";
}
?>
