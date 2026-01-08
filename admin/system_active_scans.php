<?php
/**
 * System Active Scans Component
 * Educational Security Scanner Dashboard
 */
?>

<div class="bg-white rounded-lg shadow p-6" x-data="{
    scans: [], 
    loading: true, 
    autoRefresh: true,
    loadActiveScans() {
        this.loading = true;
        fetch('api/admin_get_active_scans.php')
            .then(response => response.json())
            .then(data => {
                this.loading = false;
                if (data.success) {
                    this.scans = data.scans;
                }
            })
            .catch(error => {
                this.loading = false;
                console.error('Error loading active scans:', error);
            });
    },
    terminateScan(scanId) {
        if (confirm('Are you sure you want to terminate this scan?')) {
            fetch('api/admin_terminate_scan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'scan_id=' + scanId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.loadActiveScans();
                    alert('Scan terminated successfully');
                } else {
                    alert('Error terminating scan: ' + data.error);
                }
            });
        }
    },
    init() {
        this.loadActiveScans();
        // Auto-refresh every 3 seconds if enabled
        setInterval(() => {
            if (this.autoRefresh && !this.loading) {
                this.loadActiveScans();
            }
        }, 3000);
    }
}" x-init="init()">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900 mb-2">System Active Scans</h2>
            <p class="text-sm text-gray-600">Monitor all running scans across the system</p>
        </div>
        <div class="flex items-center space-x-2">
            <label class="flex items-center">
                <input type="checkbox" x-model="autoRefresh" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                <span class="ml-2 text-sm text-gray-600">Auto-refresh</span>
            </label>
            <button @click="loadActiveScans()" class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                Refresh
            </button>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-8">
        <svg class="animate-spin mx-auto h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-sm text-gray-500">Loading active scans...</p>
    </div>

    <!-- Active Scans -->
    <div x-show="!loading && scans.length > 0" class="space-y-4">
        <template x-for="scan in scans" :key="scan.id">
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="font-medium text-gray-900" x-text="scan.scan_type + ' - ' + scan.target"></h3>
                        <p class="text-sm text-gray-500" x-text="'User: ' + scan.username + ' | Started: ' + scan.date"></p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Running</span>
                        <button @click="terminateScan(scan.id)" class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded hover:bg-red-200">
                            Terminate
                        </button>
                    </div>
                </div>
                
                <!-- Live Output -->
                <div class="bg-black text-green-400 p-3 rounded font-mono text-sm max-h-48 overflow-y-auto">
                    <div x-text="scan.output || 'Initializing scan...'"></div>
                </div>
            </div>
        </template>
    </div>

    <!-- No Active Scans -->
    <div x-show="!loading && scans.length === 0" class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No active scans</h3>
        <p class="mt-1 text-sm text-gray-500">No scans are currently running in the system.</p>
    </div>
</div>
