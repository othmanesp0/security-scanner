<?php
/**
 * Debug Setup Script
 * Run this to diagnose and fix login/signup issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Security Scanner Debug Setup</h2>";

// Test database connection
echo "<h3>1. Testing Database Connection...</h3>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit();
}

// Check if users table exists
echo "<h3>2. Checking Users Table...</h3>";
try {
    $query = "DESCRIBE users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    echo "✅ Users table exists<br>";
} catch (Exception $e) {
    echo "❌ Users table missing. Creating table...<br>";
    
    $create_table = "
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100),
        password VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    try {
        $db->exec($create_table);
        echo "✅ Users table created<br>";
    } catch (Exception $e) {
        echo "❌ Failed to create users table: " . $e->getMessage() . "<br>";
        exit();
    }
}

// Check if admin user exists
echo "<h3>3. Checking Admin User...</h3>";
$query = "SELECT * FROM users WHERE username = 'admin'";
$stmt = $db->prepare($query);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo "✅ Admin user exists<br>";
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Admin details: ID=" . $admin['id'] . ", Role=" . $admin['role'] . "<br>";
} else {
    echo "❌ Admin user missing. Creating admin user...<br>";
    
    $admin_password = password_hash('admin', PASSWORD_DEFAULT);
    $query = "INSERT INTO users (username, email, password, role) VALUES ('admin', 'admin@localhost', ?, 'admin')";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$admin_password])) {
        echo "✅ Admin user created successfully<br>";
        echo "Username: admin<br>";
        echo "Password: admin<br>";
    } else {
        echo "❌ Failed to create admin user<br>";
    }
}

// Test password verification
echo "<h3>4. Testing Password Verification...</h3>";
$query = "SELECT password FROM users WHERE username = 'admin'";
$stmt = $db->prepare($query);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (password_verify('admin', $admin['password'])) {
    echo "✅ Password verification working<br>";
} else {
    echo "❌ Password verification failed<br>";
}

// Check required directories
echo "<h3>5. Checking Required Directories...</h3>";
$dirs = ['logs', 'results', 'wordlists'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "✅ Created directory: $dir<br>";
    } else {
        echo "✅ Directory exists: $dir<br>";
    }
}

echo "<h3>Setup Complete!</h3>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";
echo "<p><strong>Note:</strong> Delete this file after setup for security.</p>";
?>
