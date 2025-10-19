@php
    $updateAvailable = false;
    $currentVersion = config('app.version', '1.0.0');
    $latestVersion = null;
    $updateStatus = 'idle';
    
    // Check for updates (only for authenticated users)
    if (auth()->check()) {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get(url('/api/update/check'));
            if ($response->successful()) {
                $data = $response->json();
                $updateAvailable = $data['update_available'] ?? false;
                $latestVersion = $data['latest_version'] ?? null;
            }
        } catch (\Exception $e) {
            // Silently fail
        }
    }
@endphp

@if(auth()->check())
<div id="update-notification" class="relative">
    <!-- Update Available Badge -->
    @if($updateAvailable)
    <div class="fixed top-4 right-4 z-50 max-w-sm">
        <div class="bg-blue-600 text-white rounded-lg shadow-lg p-4 border-l-4 border-blue-400">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-200" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium">
                        Update Available!
                    </h3>
                    <div class="mt-1 text-sm">
                        <p>Version {{ $latestVersion }} is available (current: {{ $currentVersion }})</p>
                    </div>
                    <div class="mt-3">
                        <button onclick="showUpdateModal()" class="bg-blue-500 hover:bg-blue-400 text-white text-xs font-medium py-2 px-3 rounded">
                            Update Now
                        </button>
                        <button onclick="dismissUpdate()" class="ml-2 text-blue-200 hover:text-white text-xs">
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Update Button in Header -->
    <div class="relative">
        <button onclick="checkForUpdates()" class="relative p-2 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 rounded-md">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            @if($updateAvailable)
            <span class="absolute -top-1 -right-1 h-3 w-3 bg-red-500 rounded-full"></span>
            @endif
        </button>
    </div>

    <!-- Update Modal -->
    <div id="updateModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="hideUpdateModal()"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Update Available
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    A new version of Internet Management is available.
                                </p>
                                <div class="mt-2 text-sm">
                                    <p><strong>Current Version:</strong> <span id="current-version">{{ $currentVersion }}</span></p>
                                    <p><strong>Latest Version:</strong> <span id="latest-version">{{ $latestVersion }}</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="performUpdate()" id="update-button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Update Now
                    </button>
                    <button type="button" onclick="hideUpdateModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Progress Modal -->
    <div id="updateProgressModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Updating Application
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500" id="update-status-message">
                                    Please wait while we update your application...
                                </p>
                                <div class="mt-4">
                                    <div class="bg-gray-200 rounded-full h-2">
                                        <div id="update-progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2" id="update-progress-text">0%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let updateCheckInterval;

function checkForUpdates() {
    fetch('/api/update/check')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.update_available) {
                document.getElementById('current-version').textContent = data.current_version;
                document.getElementById('latest-version').textContent = data.latest_version;
                showUpdateModal();
            } else {
                alert('You are already up to date!');
            }
        })
        .catch(error => {
            console.error('Error checking for updates:', error);
            alert('Failed to check for updates');
        });
}

function showUpdateModal() {
    document.getElementById('updateModal').classList.remove('hidden');
}

function hideUpdateModal() {
    document.getElementById('updateModal').classList.add('hidden');
}

function dismissUpdate() {
    document.getElementById('update-notification').querySelector('.fixed').style.display = 'none';
}

function performUpdate() {
    hideUpdateModal();
    showUpdateProgress();
    
    fetch('/api/update/perform', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            startUpdateStatusCheck();
        } else {
            hideUpdateProgress();
            alert('Update failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error starting update:', error);
        hideUpdateProgress();
        alert('Failed to start update');
    });
}

function showUpdateProgress() {
    document.getElementById('updateProgressModal').classList.remove('hidden');
}

function hideUpdateProgress() {
    document.getElementById('updateProgressModal').classList.add('hidden');
    if (updateCheckInterval) {
        clearInterval(updateCheckInterval);
    }
}

function startUpdateStatusCheck() {
    updateCheckInterval = setInterval(() => {
        fetch('/api/update/status')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const status = data.status;
                    const message = data.message;
                    const progress = data.progress || 0;
                    
                    document.getElementById('update-status-message').textContent = message;
                    document.getElementById('update-progress-bar').style.width = progress + '%';
                    document.getElementById('update-progress-text').textContent = progress + '%';
                    
                    if (status === 'completed') {
                        hideUpdateProgress();
                        alert('Update completed successfully! The page will reload in 5 seconds.');
                        setTimeout(() => {
                            window.location.reload();
                        }, 5000);
                    } else if (status === 'failed') {
                        hideUpdateProgress();
                        alert('Update failed: ' + message);
                    }
                }
            })
            .catch(error => {
                console.error('Error checking update status:', error);
            });
    }, 2000);
}

// Auto-check for updates every 30 minutes
setInterval(checkForUpdates, 30 * 60 * 1000);
</script>
@endif
