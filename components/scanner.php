<?php
// security-scanner/components/scanner.php
$csrf_token = generateCsrfToken();
?>

<div class="bg-white rounded-lg shadow p-6" 
     x-data="{ 
        isRunning: false, 
        results: '', 
        selectedTool: 'Port Scan', 
        targetUrl: '',
        runScan() {
            this.isRunning = true;
            this.results = '';
            
            // Get token from hidden input or PHP variable
            const csrfToken = '<?php echo $csrf_token; ?>';
            
            fetch('api/api_run_scan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: 'target_url=' + encodeURIComponent(this.targetUrl) + 
                      '&tool_name=' + encodeURIComponent(this.selectedTool) +
                      '&csrf_token=' + encodeURIComponent(csrfToken)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success state briefly
                    setTimeout(() => { 
                        this.isRunning = false;
                        this.targetUrl = ''; // Optional: clear input after success
                    }, 1500);
                    
                    // Refresh active scans if the function exists
                    if(window.loadActiveScans) window.loadActiveScans();
                } else {
                    alert('Error: ' + data.error);
                    this.isRunning = false;
                }
            })
            .catch(error => {
                this.isRunning = false;
                alert('Connection Error: ' + error.message);
            });
        }
     }">

    <div class="mb-6 border-b pb-4">
        <h2 class="text-xl font-bold text-blue-800 mb-2">Security Scanner (PRO VERSION 2.1)</h2>
        <p class="text-sm text-gray-600">
            Current Mode: <span class="font-bold text-green-600">Live System Access</span>
        </p>
    </div>

    <form @submit.prevent="runScan" class="space-y-4">
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Target URL / IP</label>
            <input type="text" 
                   x-model="targetUrl"
                   placeholder="e.g., 8.8.8.8 or example.com"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                   required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Tool</label>
            <select x-model="selectedTool" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                <optgroup label="Connectivity & Network">
                    <option value="Ping">Ping (Connectivity Check)</option>
                    <option value="Traceroute">Traceroute (Path Analysis)</option>
                    <option value="Port Scan">Nmap (Fast Port Scan)</option>
                    <option value="DNS">DNS Lookup (Dig)</option>
                    <option value="Whois">Whois (Domain Info)</option>
                </optgroup>
                <optgroup label="Vulnerability & Web">
                    <option value="Headers">HTTP Headers</option>
                    <option value="SSL">SSL/TLS Security (SSLyze)</option>
                    <option value="Subdirectories">Subdomain Finder (Ffuf)</option>
                    <option value="Directory">Directory Buster (Dirsearch)</option>
                    <option value="Nikto">Web Vuln Scanner (Nikto)</option>
                </optgroup>
            </select>
        </div>

        <button type="submit" 
                :disabled="isRunning"
                :class="isRunning ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                class="w-full px-4 py-3 text-white font-medium rounded-md shadow-sm transition-colors">
            <span x-show="!isRunning">🚀 Launch Scan</span>
            <span x-show="isRunning" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing in Background...
            </span>
        </button>
    </form>
    
    <div x-show="isRunning" x-transition class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800">Scan Queued Successfully</p>
                <p class="text-sm text-green-700 mt-1">
                    The scan is running in the background. Please check the 
                    <a href="#" @click="$dispatch('switch-tab', 'active')" class="font-bold underline">Active Scans</a> 
                    tab to see the live output.
                </p>
            </div>
        </div>
    </div>
</div>
