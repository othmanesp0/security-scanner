<?php
/**
 * Admin Dashboard - Fixed Stats, History & User Creation
 */
session_start();
require_once 'config/database.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$message = "";
$error = "";

// 2. Handle "Add User" Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($username) || empty($password) || empty($email)) {
        $error = "All fields are required.";
    } else {
        try {
            // Check if username exists
            $check = $db->prepare("SELECT id FROM users WHERE username = :u OR email = :e");
            $check->execute([':u' => $username, ':e' => $email]);
            
            if ($check->rowCount() > 0) {
                $error = "Username or Email already exists.";
            } else {
                // Insert User
                $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (:u, :e, :p, :r)");
                $stmt->execute([
                    ':u' => $username, 
                    ':e' => $email, 
                    ':p' => $hashed_pass, 
                    ':r' => $role
                ]);
                $message = "User '$username' created successfully!";
            }
        } catch (Exception $e) {
            $error = "Error creating user: " . $e->getMessage();
        }
    }
}

// 3. Fetch System Statistics
$stats = [];
try {
    $stats['users'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['scans'] = $db->query("SELECT COUNT(*) FROM scans")->fetchColumn();
    $stats['running'] = $db->query("SELECT COUNT(*) FROM scans WHERE status = 'running'")->fetchColumn();
} catch (Exception $e) {
    $stats = ['users' => 0, 'scans' => 0, 'running' => 0];
}

// 4. Fetch Global Scan History
$scans = [];
try {
    // Note: We join with users table to show WHO ran the scan
    $query = "SELECT s.*, u.username 
              FROM scans s 
              LEFT JOIN users u ON s.user_id = u.id 
              ORDER BY s.started_at DESC LIMIT 20";
    $stmt = $db->query($query);
    $scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silent fail or log
}

// 5. Fetch Users List
$users = [];
try {
    $users = $db->query("SELECT * FROM users ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    
    <nav class="bg-gray-900 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <span class="text-xl font-bold text-red-500">🛡️ AdminPanel</span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-300">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="../logout.php" class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-sm transition">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <?php if($message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-blue-500">
                <h3 class="text-gray-500 text-sm font-medium uppercase">Total Users</h3>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['users']; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-purple-500">
                <h3 class="text-gray-500 text-sm font-medium uppercase">Total Scans</h3>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['scans']; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-green-500">
                <h3 class="text-gray-500 text-sm font-medium uppercase">Active Scans</h3>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['running']; ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-8">
                
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <h3 class="font-bold text-gray-700">System Scan History</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-gray-50 font-medium text-gray-800">
                                <tr>
                                    <th class="px-6 py-3">ID</th>
                                    <th class="px-6 py-3">User</th>
                                    <th class="px-6 py-3">Target</th>
                                    <th class="px-6 py-3">Tool</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if(empty($scans)): ?>
                                    <tr><td colspan="6" class="px-6 py-4 text-center">No scans found in database.</td></tr>
                                <?php else: ?>
                                    <?php foreach($scans as $scan): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3">#<?php echo $scan['id']; ?></td>
                                        <td class="px-6 py-3 font-bold text-blue-600"><?php echo htmlspecialchars($scan['username'] ?? 'Unknown'); ?></td>
                                        <td class="px-6 py-3 font-mono"><?php echo htmlspecialchars($scan['target']); ?></td>
                                        <td class="px-6 py-3"><?php echo htmlspecialchars($scan['scan_type']); ?></td>
                                        <td class="px-6 py-3">
                                            <span class="px-2 py-1 rounded text-xs font-bold 
                                                <?php echo $scan['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                <?php echo $scan['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <a href="../view_scan_results.php?id=<?php echo $scan['id']; ?>" target="_blank" class="text-blue-600 hover:underline">View</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="font-bold text-gray-700">Recent Users</h3>
                    </div>
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50 font-medium text-gray-800">
                            <tr>
                                <th class="px-6 py-3">ID</th>
                                <th class="px-6 py-3">Username</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3">Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td class="px-6 py-3">#<?php echo $u['id']; ?></td>
                                <td class="px-6 py-3 font-medium text-gray-900"><?php echo htmlspecialchars($u['username']); ?></td>
                                <td class="px-6 py-3"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td class="px-6 py-3">
                                    <span class="px-2 py-1 rounded text-xs font-bold <?php echo $u['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $u['role']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="bg-white p-6 rounded-lg shadow h-fit">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Create New User</h3>
                <form method="POST">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="username" required class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="role" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" name="create_user" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 font-medium transition">
                        Create Account
                    </button>
                </form>
            </div>

        </div>
    </div>
</body>
</html>
