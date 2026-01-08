<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Signup Diagnostic Test</h2>";

// Test 1: Check if files exist
echo "<h3>1. File Existence Check:</h3>";
$files = [
    'config/database.php',
    'includes/auth.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Test 2: Try to include files
echo "<h3>2. Include Test:</h3>";
try {
    require_once 'config/database.php';
    echo "✅ database.php included successfully<br>";
} catch (Exception $e) {
    echo "❌ database.php error: " . $e->getMessage() . "<br>";
}

try {
    require_once 'includes/auth.php';
    echo "✅ auth.php included successfully<br>";
} catch (Exception $e) {
    echo "❌ auth.php error: " . $e->getMessage() . "<br>";
}

// Test 3: Database connection
echo "<h3>3. Database Connection Test:</h3>";
try {
    $database = new Database();
    $db = $database->getConnection();
    if ($db) {
        echo "✅ Database connection successful<br>";
        
        // Test users table
        $query = "SELECT COUNT(*) as count FROM users";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Users table accessible (count: " . $result['count'] . ")<br>";
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 4: Session functionality
echo "<h3>4. Session Test:</h3>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session is active<br>";
} else {
    echo "❌ Session not active<br>";
}

echo "<h3>5. PHP Info:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Error Reporting: " . error_reporting() . "<br>";
echo "Display Errors: " . ini_get('display_errors') . "<br>";

echo "<p><a href='signup.php'>Test Signup Page</a> | <a href='login.php'>Go to Login</a></p>";
echo "<p style='color: red;'><strong>Delete this file after testing!</strong></p>";
?>
