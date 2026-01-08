<?php
/**
 * Results Component
 * Educational Security Scanner Dashboard
 */
?>

<div class="bg-white rounded-lg shadow p-6" x-data="{ results: '', selectedScan: null, loading: false }" x-init="setupResultsListener()">
    <div class="mb-6">
        <h2 class="text-lg font-medium text-gray-900 mb-2">Scan Results</h2>
        <p class="text-sm text-gray-600">Detailed results from completed scans</p>
    </div>

    <!-- Scan Selection -->
    <div class="mb-4">
        <label for="scan_select" class="block text-sm font-medium text-gray-700 mb-2">Select Scan</label>
        <select id="scan_select" 
                x-model="selectedScan"
                @change="loadResults()"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Choose a completed scan...</option>
        </select>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-8">
        <svg class="animate-spin mx-auto h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-sm text-gray-500">Loading results...</p>
    </div>

    <!-- Results Display -->
    <div x-show="results && !loading" class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-md font-medium text-gray-900">Scan Output</h3>
            <button @click="downloadResults()" class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                Download
            </button>
        </div>
        
        <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm max-h-96 overflow-y-auto">
            <pre x-text="results" class="whitespace-pre-wrap"></pre>
        </div>
    </div>

    <!-- No Results -->
    <div x-show="!results && !loading && selectedScan" class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No results available</h3>
        <p class="mt-1 text-sm text-gray-500">The selected scan may not have completed successfully.</p>
    </div>

    <!-- Default State -->
    <div x-show="!selectedScan && !loading" class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Select a scan to view results</h3>
        <p class="mt-1 text-sm text-gray-500">Choose a completed scan from the dropdown above.</p>
    </div>

    <script>
        function setupResultsListener() {
            // Listen for view-results events from history component
            window.addEventListener('view-results', (event) => {
                this.selectedScan = event.detail.scanId;
                this.loadResults();
                // Switch to results tab
                document.querySelector('[x-data]').__x.$data.activeTab = 'results';
            });
            
            // Load available scans for dropdown
            this.loadAvailableScans();
        }

        function loadAvailableScans() {
            fetch('api/api_get_completed_scans.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('scan_select');
                        // Clear existing options except first
                        select.innerHTML = '<option value="">Choose a completed scan...</option>';
                        
                        data.scans.forEach(scan => {
                            const option = document.createElement('option');
                            option.value = scan.id;
                            option.textContent = `${scan.date} - ${scan.tool} - ${scan.target_url}`;
                            select.appendChild(option);
                        });
                    }
                });
        }

        function loadResults() {
            if (!this.selectedScan) return;
            
            this.loading = true;
            this.results = '';
            
            fetch('api/api_get_results.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'scan_id=' + encodeURIComponent(this.selectedScan)
            })
            .then(response => response.json())
            .then(data => {
                this.loading = false;
                if (data.success) {
                    this.results = data.results;
                } else {
                    this.results = 'Error loading results: ' + data.error;
                }
            })
            .catch(error => {
                this.loading = false;
                this.results = 'Error: ' + error.message;
            });
        }

        function downloadResults() {
            if (!this.results) return;
            
            const blob = new Blob([this.results], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `scan_results_${this.selectedScan}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</div>
