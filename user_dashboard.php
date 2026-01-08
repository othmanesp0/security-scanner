<?php
/**
 * User Dashboard
 * Educational Security Scanner Dashboard
 */

require_once 'includes/auth.php';
requireLogin();

// Handle logout
if (isset($_GET['logout'])) {
    logout();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Security Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-gray-900">Security Scanner Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="?logout=1" class="text-sm text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ activeTab: 'crawler' }">
        <!-- Navigation Tabs -->
        <div class="border-b border-gray-200 mb-8">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'crawler'" 
                        :class="activeTab === 'crawler' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Crawler
                </button>
                <button @click="activeTab = 'scanner'" 
                        :class="activeTab === 'scanner' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Scanner
                </button>
                <button @click="activeTab = 'active'" 
                        :class="activeTab === 'active' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Active Scans
                </button>
                <button @click="activeTab = 'history'" 
                        :class="activeTab === 'history' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    History
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="space-y-6">
            <!-- Crawler Tab -->
            <div x-show="activeTab === 'crawler'" x-transition>
                <?php include 'components/crawler.php'; ?>
            </div>

            <!-- Scanner Tab -->
            <div x-show="activeTab === 'scanner'" x-transition>
                <?php include 'components/scanner.php'; ?>
            </div>

            <!-- Active Scans Tab -->
            <div x-show="activeTab === 'active'" x-transition>
                <?php include 'components/active_scans.php'; ?>
            </div>

            <!-- History Tab -->
            <div x-show="activeTab === 'history'" x-transition>
                <?php include 'components/history.php'; ?>
            </div>

            <!-- Results Tab -->
            <div x-show="activeTab === 'results'" x-transition>
                <?php include 'components/results.php'; ?>
            </div>
        </div>
    </div>

    <!-- Warning Modal -->

</body>
</html>
