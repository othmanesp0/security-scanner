<?php
/**
 * Crawler Component
 * Educational Security Scanner Dashboard
 */
?>

<div class="bg-white rounded-lg shadow p-6" x-data="{
    isRunning: false, 
    results: '', 
    targetUrl: '',
    runCrawler() {
        console.log('[v0] Starting crawler with URL:', this.targetUrl);
        this.isRunning = true;
        this.results = '';
        
        fetch('api/api_run_crawler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'target_url=' + encodeURIComponent(this.targetUrl)
        })
        .then(response => {
            console.log('[v0] Crawler response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('[v0] Crawler response data:', data);
            this.isRunning = false;
            if (data.success) {
                this.results = data.results;
            } else {
                this.results = 'Error: ' + data.error;
            }
        })
        .catch(error => {
            console.log('[v0] Crawler error:', error);
            this.isRunning = false;
            this.results = 'Error: ' + error.message;
        });
    }
}">
    <div class="mb-6">
        <h2 class="text-lg font-medium text-gray-900 mb-2">Web Crawler</h2>
        <p class="text-sm text-gray-600">Discover URLs and links on a target website. Use only on sites you own or have permission to test.</p>
    </div>

    <form @submit.prevent="runCrawler()" class="space-y-4">
        <div>
            <label for="crawler_url" class="block text-sm font-medium text-gray-700 mb-2">Target URL</label>
            <input type="text" 
                   id="crawler_url" 
                   x-model="targetUrl"
                   placeholder="example.com or 192.168.1.1"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                   required>
            <p class="text-xs text-gray-500 mt-1">Enter domain name or IP address (without http://)</p>
        </div>

        <button type="submit" 
                :disabled="isRunning"
                :class="isRunning ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                class="px-4 py-2 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <span x-show="!isRunning">Run Crawler</span>
            <span x-show="isRunning" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Crawling...
            </span>
        </button>
    </form>

    <!-- Results Section -->
    <div x-show="results" class="mt-6 border-t pt-6">
        <h3 class="text-md font-medium text-gray-900 mb-3">Discovered URLs</h3>
        <div class="bg-gray-50 rounded-md p-4 max-h-96 overflow-y-auto">
            <pre x-text="results" class="text-sm text-gray-800 whitespace-pre-wrap"></pre>
        </div>
    </div>
</div>
