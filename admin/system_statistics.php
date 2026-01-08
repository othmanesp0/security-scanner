<?php
/**
 * System Statistics Component
 * Educational Security Scanner Dashboard
 */
?>

<div class="bg-white rounded-lg shadow p-6" x-data="{
    stats: {}, 
    loading: true,
    loadStats() {
        this.loading = true;
        fetch('api/admin_get_statistics.php')
            .then(response => response.json())
            .then(data => {
                this.loading = false;
                if (data.success) {
                    this.stats = data.stats;
                }
            })
            .catch(error => {
                this.loading = false;
                console.error('Error loading statistics:', error);
            });
    }
}" x-init="loadStats()">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900 mb-2">System Statistics</h2>
            <p class="text-sm text-gray-600">Overview of system usage and activity</p>
        </div>
        <button @click="loadStats()" class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700">
            Refresh
        </button>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-8">
        <svg class="animate-spin mx-auto h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-sm text-gray-500">Loading statistics...</p>
    </div>

    <!-- Statistics Grid -->
    <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Users -->
        <div class="bg-blue-50 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-blue-600">Total Users</p>
                    <p class="text-2xl font-semibold text-blue-900" x-text="stats.total_users || 0"></p>
                </div>
            </div>
        </div>

        <!-- Total Scans -->
        <div class="bg-green-50 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-green-600">Total Scans</p>
                    <p class="text-2xl font-semibold text-green-900" x-text="stats.total_scans || 0"></p>
                </div>
            </div>
        </div>

        <!-- Active Scans -->
        <div class="bg-yellow-50 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-yellow-600">Active Scans</p>
                    <p class="text-2xl font-semibold text-yellow-900" x-text="stats.active_scans || 0"></p>
                </div>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="bg-purple-50 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-purple-600">Success Rate</p>
                    <p class="text-2xl font-semibold text-purple-900" x-text="(stats.success_rate || 0) + '%'"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tool Usage Chart -->
    <div x-show="!loading && stats.tool_usage" class="mt-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Tool Usage Distribution</h3>
        <div class="bg-gray-50 rounded-lg p-4">
            <template x-for="(count, tool) in stats.tool_usage" :key="tool">
                <div class="flex justify-between items-center py-2 border-b border-gray-200 last:border-b-0">
                    <span class="text-sm font-medium text-gray-700" x-text="tool"></span>
                    <div class="flex items-center space-x-2">
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-red-600 h-2 rounded-full" :style="'width: ' + (count / Math.max(...Object.values(stats.tool_usage)) * 100) + '%'"></div>
                        </div>
                        <span class="text-sm text-gray-600" x-text="count"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Recent Activity -->
    <div x-show="!loading && stats.recent_activity" class="mt-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
        <div class="bg-gray-50 rounded-lg p-4 max-h-64 overflow-y-auto">
            <template x-for="activity in stats.recent_activity" :key="activity.id">
                <div class="flex justify-between items-center py-2 text-sm">
                    <span x-text="activity.username + ' ran ' + activity.tool + ' on ' + activity.target_url"></span>
                    <span class="text-gray-500" x-text="activity.date"></span>
                </div>
            </template>
        </div>
    </div>
</div>
