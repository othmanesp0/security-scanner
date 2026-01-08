<?php
/**
 * Active Scans Component
 * Educational Security Scanner Dashboard
 */
?>

<div class="bg-white rounded-lg shadow p-6" x-data="{
    scans: [], 
    autoRefresh: true,
    loadActiveScans() {
        fetch('api/api_get_active_scans.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.scans = data.scans;
                }
            })
            .catch(error => console.error('Error loading active scans:', error));
    },
    init() {
        this.loadActiveScans();
        // Auto-refresh every 3 seconds if enabled
        setInterval(() => {
            if (this.autoRefresh) {
                this.loadActiveScans();
            }
        }, 3000);
    }
}" x-init="init()">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900 mb-2">Active Scans</h2>
            <p class="text-sm text-gray-600">Real-time monitoring of running scans</p>
        </div>
        <div class="flex items-center space-x-2">
            <label class="flex items-center">
                <input type="checkbox" x-model="autoRefresh" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-600">Auto-refresh</span>
            </label>
            <button @click="loadActiveScans()" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                Refresh
            </button>
        </div>
    </div>

    <!-- Active Scans List -->
    <div x-show="scans.length > 0" class="space-y-4">
        <template x-for="scan in scans" :key="scan.id">
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="font-medium text-gray-900" x-text="scan.scan_type + ' - ' + scan.target"></h3>
                        <p class="text-sm text-gray-500" x-text="'Started: ' + scan.date"></p>
                    </div>
                    <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Running</span>
                </div>
                
                <!-- Live Output -->
                <div class="bg-black text-green-400 p-3 rounded font-mono text-sm max-h-48 overflow-y-auto">
                    <div x-text="scan.output || 'Initializing scan...'"></div>
                </div>
            </div>
        </template>
    </div>

    <!-- No Active Scans -->
    <div x-show="scans.length === 0" class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No active scans</h3>
        <p class="mt-1 text-sm text-gray-500">Start a scan from the Scanner tab to see real-time output here.</p>
    </div>
</div>
