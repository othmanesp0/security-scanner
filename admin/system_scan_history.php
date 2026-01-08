<?php
/**
 * System Scan History Component
 * Educational Security Scanner Dashboard
 */
?>

<div class="bg-white rounded-lg shadow p-6" x-data="{
    history: [], 
    loading: true, 
    filters: { user: '', tool: '', status: '' },
    loadHistory() {
        this.loading = true;
        fetch('api/admin_get_scan_history.php')
            .then(response => response.json())
            .then(data => {
                this.loading = false;
                if (data.success) {
                    this.history = data.history;
                }
            })
            .catch(error => {
                this.loading = false;
                console.error('Error loading history:', error);
            });
    },
    viewResults(scanId) {
        window.open('view_scan_results.php?id=' + scanId, '_blank');
    },
    deleteScan(scanId) {
        if (confirm('Are you sure you want to delete this scan record?')) {
            fetch('api/admin_delete_scan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'scan_id=' + scanId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.loadHistory();
                    alert('Scan deleted successfully');
                } else {
                    alert('Error deleting scan: ' + data.error);
                }
            });
        }
    },
    get filteredHistory() {
        return this.history.filter(scan => {
            return (!this.filters.user || scan.username.toLowerCase().includes(this.filters.user.toLowerCase())) &&
                   (!this.filters.tool || scan.scan_type === this.filters.tool) &&
                   (!this.filters.status || scan.status === this.filters.status);
        });
    }
}" x-init="loadHistory()">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900 mb-2">System Scan History</h2>
            <p class="text-sm text-gray-600">Complete history of all scans in the system</p>
        </div>
        <button @click="loadHistory()" class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700">
            Refresh
        </button>
    </div>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by User</label>
            <input type="text" x-model="filters.user" placeholder="Username..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Tool</label>
            <select x-model="filters.tool" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                <option value="">All Tools</option>
                <option value="Port Scan">Port Scan</option>
                <option value="Subdirectories">Subdirectories</option>
                <option value="Directory">Directory</option>
                <option value="SSL">SSL</option>
                <option value="Nikto">Nikto</option>
                <option value="DNS">DNS</option>
                <option value="Webanalyze">Webanalyze</option>
                <option value="Crawler">Crawler</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
            <select x-model="filters.status" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                <option value="">All Status</option>
                <option value="completed">Completed</option>
                <option value="failed">Failed</option>
                <option value="running">Running</option>
            </select>
        </div>
        <div class="flex items-end">
            <button @click="filters = { user: '', tool: '', status: '' }" 
                    class="w-full px-3 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 text-sm">
                Clear Filters
            </button>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-8">
        <svg class="animate-spin mx-auto h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-sm text-gray-500">Loading scan history...</p>
    </div>

    <!-- History Table -->
    <div x-show="!loading" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tool</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="scan in filteredHistory" :key="scan.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="scan.id"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="scan.username"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="scan.date"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="scan.target"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="scan.scan_type"></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="{
                                'bg-green-100 text-green-800': scan.status === 'completed',
                                'bg-red-100 text-red-800': scan.status === 'failed',
                                'bg-yellow-100 text-yellow-800': scan.status === 'running'
                            }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" x-text="scan.status"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <button x-show="scan.status === 'completed'" @click="viewResults(scan.id)"
                                    class="text-blue-600 hover:text-blue-900">View</button>
                            <button @click="deleteScan(scan.id)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
