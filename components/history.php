<?php
// security-scanner/components/history.php
?>
<div x-data="scanHistory()" x-init="loadHistory()" class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-800">Scan History</h3>
        <button @click="loadHistory()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            Refresh
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50 text-gray-800 font-medium border-b border-gray-100">
                <tr>
                    <th class="px-6 py-3">Target</th>
                    <th class="px-6 py-3">Tool</th>
                    <th class="px-6 py-3">Date</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="scan in history" :key="scan.id">
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 font-medium text-gray-900" x-text="scan.target_url"></td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800" x-text="scan.tool"></span>
                        </td>
                        <td class="px-6 py-3" x-text="scan.date"></td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="{
                                      'bg-green-100 text-green-800': scan.status === 'completed',
                                      'bg-yellow-100 text-yellow-800': scan.status === 'running',
                                      'bg-red-100 text-red-800': scan.status === 'failed'
                                  }"
                                  x-text="scan.status">
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <a :href="'view_scan_results.php?id=' + scan.id" 
                               class="text-blue-600 hover:text-blue-900 font-medium hover:underline">
                               View Results
                            </a>
                        </td>
                    </tr>
                </template>
                <tr x-show="history.length === 0">
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        No scan history found. Run a scan to see it here.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        function scanHistory() {
            return {
                history: [],
                loadHistory() {
                    fetch('api/api_get_history.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.history = data.history;
                            }
                        })
                        .catch(error => console.error('Error loading history:', error));
                }
            }
        }
    </script>
</div>
