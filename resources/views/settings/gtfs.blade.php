<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">GTFS Configuration</h1>
                        <p class="mt-2 text-sm text-gray-600">Manage your transit data feeds and realtime updates</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center text-sm text-gray-500">
                            <div class="w-2 h-2 bg-green-400 rounded-full mr-2"></div>
                            System Active
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-8">

                    @if (session('success'))
                        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg relative">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                {{ session('success') }}
                            </div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg relative">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                {{ session('error') }}
                            </div>
                        </div>
                    @endif

                    <!-- Configuration Form -->
                    <div class="bg-gray-50 rounded-lg p-6 mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Data Feed Configuration
                        </h3>
                        
                        <form action="{{ route('settings.gtfs') }}" method="POST" class="space-y-6">
                            @csrf
                            
                            <!-- Schedule Data -->
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <div class="flex items-center mb-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Schedule Data</h4>
                                        <p class="text-xs text-gray-500">Static transit information</p>
                                    </div>
                                </div>
                                <div>
                                    <label for="url" class="block text-sm font-medium text-gray-700 mb-1">GTFS Schedule URL</label>
                                    <input type="url" name="url" id="url" 
                                        value="{{ old('url', $gtfsSettings?->url) }}"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                        placeholder="https://example.com/gtfs.zip">
                                    <p class="mt-1 text-xs text-gray-500">URL to the GTFS schedule ZIP file containing static transit data.</p>
                                    @error('url')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Realtime Data -->
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <div class="flex items-center mb-3">
                                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Realtime Data</h4>
                                        <p class="text-xs text-gray-500">Live updates and status</p>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <label for="realtime_url" class="block text-sm font-medium text-gray-700 mb-1">GTFS Realtime URL</label>
                                        <input type="url" name="realtime_url" id="realtime_url" 
                                            value="{{ old('realtime_url', $gtfsSettings?->realtime_url) }}"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                                            placeholder="https://example.com/gtfs-realtime.json">
                                        <p class="mt-1 text-xs text-gray-500">URL to the GTFS realtime JSON feed for live updates.</p>
                                        @error('realtime_url')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="realtime_update_interval" class="block text-sm font-medium text-gray-700 mb-1">Update Interval</label>
                                        <div class="flex items-center space-x-2">
                                            <input type="number" name="realtime_update_interval" id="realtime_update_interval" 
                                                value="{{ old('realtime_update_interval', $gtfsSettings?->realtime_update_interval ?? 30) }}"
                                                min="10" max="300"
                                                class="block w-24 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                                                placeholder="30">
                                            <span class="text-sm text-gray-500">seconds (10-300)</span>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">How often to fetch realtime updates.</p>
                                        @error('realtime_update_interval')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                <div class="flex space-x-3">
                                    <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Save Configuration
                                    </button>
                                    
                                    @if($gtfsSettings && $gtfsSettings->url)
                                        <a href="{{ route('settings.gtfs.download') }}" 
                                           class="inline-flex items-center px-4 py-2 border border-green-300 rounded-md shadow-sm text-sm font-medium text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                            Download GTFS Data
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Status Dashboard -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Schedule Status -->
                        <div class="bg-white rounded-lg border border-gray-200 p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    Schedule Data Status
                                </h3>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-green-400 rounded-full mr-2"></div>
                                    <span class="text-sm text-gray-500">Active</span>
                                </div>
                            </div>
                            
                            @if($gtfsSettings)
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Last Download:</span>
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $gtfsSettings->last_download ? $gtfsSettings->last_download->format('M j, Y g:i A') : 'Never' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Next Download:</span>
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $gtfsSettings->next_download ? $gtfsSettings->next_download->format('M j, Y g:i A') : 'Not Scheduled' }}
                                        </span>
                                    </div>
                                    
                                    <div class="pt-2">
                                        <a href="{{ route('settings.gtfs.download') }}" 
                                           class="inline-flex items-center px-3 py-1.5 border border-blue-300 rounded-md shadow-sm text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                            Download Now
                                        </a>
                                    </div>
                                    
                                    @if($gtfsSettings->is_downloading)
                                        <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                            <div class="flex items-center mb-2">
                                                <svg class="animate-spin w-4 h-4 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span class="text-sm font-medium text-blue-900">Download in Progress</span>
                                            </div>
                                            @if($gtfsSettings->download_status)
                                                <p class="text-xs text-blue-700 mb-2" id="download-status">
                                                    {{ $gtfsSettings->download_status }}
                                                </p>
                                            @endif
                                            <div class="mb-2">
                                                <div class="flex justify-between text-xs text-blue-700 mb-1">
                                                    <span>Progress</span>
                                                    <span id="progress-percentage">{{ $gtfsSettings->download_progress ?? 0 }}%</span>
                                                </div>
                                                <div class="w-full bg-blue-200 rounded-full h-1.5">
                                                    <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300" 
                                                         id="progress-bar"
                                                         style="width: {{ $gtfsSettings->download_progress ?? 0 }}%"></div>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-5 gap-1 text-xs">
                                                <div class="text-center {{ $gtfsSettings->download_progress >= 5 ? 'text-blue-600 font-medium' : 'text-blue-400' }}" id="stage-download">
                                                    Download
                                                </div>
                                                <div class="text-center {{ $gtfsSettings->download_progress >= 20 ? 'text-blue-600 font-medium' : 'text-blue-400' }}" id="stage-trips">
                                                    Trips
                                                </div>
                                                <div class="text-center {{ $gtfsSettings->download_progress >= 40 ? 'text-blue-600 font-medium' : 'text-blue-400' }}" id="stage-calendar">
                                                    Calendar
                                                </div>
                                                <div class="text-center {{ $gtfsSettings->download_progress >= 60 ? 'text-blue-600 font-medium' : 'text-blue-400' }}" id="stage-routes">
                                                    Routes
                                                </div>
                                                <div class="text-center {{ $gtfsSettings->download_progress >= 80 ? 'text-blue-600 font-medium' : 'text-blue-400' }}" id="stage-stops">
                                                    Stops
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <p class="text-sm text-gray-500">No configuration found</p>
                            @endif
                        </div>

                        <!-- Realtime Status -->
                        <div class="bg-white rounded-lg border border-gray-200 p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    Realtime Data Status
                                </h3>
                                <div class="flex items-center">
                                    @if($gtfsSettings && $gtfsSettings->realtime_url)
                                        <div class="w-2 h-2 bg-green-400 rounded-full mr-2"></div>
                                        <span class="text-sm text-gray-500">Configured</span>
                                    @else
                                        <div class="w-2 h-2 bg-gray-400 rounded-full mr-2"></div>
                                        <span class="text-sm text-gray-500">Not Configured</span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($gtfsSettings && $gtfsSettings->realtime_url)
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">URL:</span>
                                        <span class="text-xs font-mono text-gray-900 truncate max-w-32">
                                            {{ $gtfsSettings->realtime_url }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Update Interval:</span>
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $gtfsSettings->realtime_update_interval ?? 30 }} seconds
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Last Update:</span>
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $gtfsSettings->last_realtime_update ? $gtfsSettings->last_realtime_update->format('M j, Y g:i A') : 'Never' }}
                                        </span>
                                    </div>
                                    @if($gtfsSettings->realtime_status)
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Status:</span>
                                            <span class="text-sm font-medium text-gray-900">
                                                {{ $gtfsSettings->realtime_status }}
                                            </span>
                                        </div>
                                    @endif
                                    
                                    <div class="pt-2">
                                        <button type="button"
                                            class="inline-flex items-center px-3 py-1.5 border border-green-300 rounded-md shadow-sm text-sm font-medium text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
                                            x-data=""
                                            x-on:click="
                                                $el.classList.add('opacity-50', 'cursor-not-allowed');
                                                $el.innerHTML = '<svg class=\'animate-spin w-4 h-4 mr-2\' fill=\'none\' viewBox=\'0 0 24 24\'><circle class=\'opacity-25\' cx=\'12\' cy=\'12\' r=\'10\' stroke=\'currentColor\' stroke-width=\'4\'></circle><path class=\'opacity-75\' fill=\'currentColor\' d=\'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\'></path></svg>Testing...';
                                                
                                                fetch('{{ route('settings.gtfs.test-realtime') }}', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Accept': 'application/json',
                                                        'X-Requested-With': 'XMLHttpRequest',
                                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                                                    },
                                                    credentials: 'same-origin'
                                                })
                                                .then(response => response.json().then(data => {
                                                    if (response.ok) {
                                                        alert('Realtime feed test successful! Found ' + data.entities_count + ' entities.');
                                                    } else {
                                                        throw new Error(data.message || 'Test failed');
                                                    }
                                                }))
                                                .catch(error => {
                                                    console.error('Error:', error);
                                                    alert('Test failed: ' + error.message);
                                                })
                                                .finally(() => {
                                                    $el.classList.remove('opacity-50', 'cursor-not-allowed');
                                                    $el.innerHTML = 'Test Connection';
                                                });
                                            ">
                                            Test Connection
                                        </button>
                                    </div>
                                </div>
                            @else
                                <p class="text-sm text-gray-500">No realtime feed configured</p>
                            @endif
                        </div>
                    </div>

                    <!-- Route Configuration -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-8">
                        <div x-data="{ open: false }" class="space-y-4">
                            <button @click="open = !open" 
                                class="flex justify-between items-center w-full px-4 py-3 text-left text-sm font-medium text-gray-900 bg-gray-50 hover:bg-gray-100 rounded-lg focus:outline-none focus-visible:ring focus-visible:ring-blue-500 focus-visible:ring-opacity-75 transition-colors duration-200">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 4m0 13V4m-6 3l6-3"></path>
                                    </svg>
                                    <span>Route Selection</span>
                                </div>
                                <svg class="w-5 h-5 transform transition-transform duration-200" 
                                     :class="{ 'rotate-180': open }" 
                                     fill="none" 
                                     stroke="currentColor" 
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" 
                                          stroke-linejoin="round" 
                                          stroke-width="2" 
                                          d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                                 x-transition:enter-end="opacity-100 transform translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 transform translate-y-0"
                                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                                 class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-sm text-gray-600 mb-4">Select which routes should appear in the dashboard.</p>
                                <livewire:route-selector />
                            </div>
                        </div>
                    </div>

                    <!-- Train Table Configuration -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-8">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    Train Table Configuration
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">Select which train data should appear in the dashboard table.</p>
                            </div>
                            <button 
                                type="button"
                                x-data=""
                                x-on:click="$dispatch('open-modal', 'train-table-selector')"
                                class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-md shadow-sm text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Configure Table Data
                            </button>
                        </div>
                        
                        <livewire:train-table-selector />
                    </div>

                    <!-- Group Configuration -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Group Configuration
                        </h3>
                        <p class="text-sm text-gray-600 mb-6">Configure train grid and table data for each group.</p>

                        <div class="space-y-4">
                            @foreach($groups as $group)
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center">
                                            @if($group->image)
                                                <img src="{{ $group->image }}" alt="{{ $group->name }}" class="w-8 h-8 rounded-full mr-3">
                                            @else
                                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                            <h4 class="text-md font-medium text-gray-900">{{ $group->name }}</h4>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button 
                                                type="button"
                                                x-data=""
                                                x-on:click="$dispatch('open-modal', 'group-train-grid-selector-{{ $group->id }}')"
                                                class="inline-flex items-center px-3 py-1.5 border border-blue-300 rounded-md shadow-sm text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                                            >
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                                </svg>
                                                Train Grid
                                            </button>
                                            <button 
                                                type="button"
                                                x-data=""
                                                x-on:click="$dispatch('open-modal', 'group-train-table-selector-{{ $group->id }}')"
                                                class="inline-flex items-center px-3 py-1.5 border border-green-300 rounded-md shadow-sm text-sm font-medium text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
                                            >
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                </svg>
                                                Train Table
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Group Train Grid Selector Modal -->
    @foreach($groups as $group)
        <x-modal name="group-train-grid-selector-{{ $group->id }}" :show="false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Configure Train Grid for {{ $group->name }}
                </h2>
                <livewire:group-train-grid-selector :group="$group" />
            </div>
        </x-modal>

        <x-modal name="group-train-table-selector-{{ $group->id }}" :show="false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Configure Train Table for {{ $group->name }}
                </h2>
                <livewire:group-train-table-selector :group="$group" />
            </div>
        </x-modal>
    @endforeach

    @if($gtfsSettings && $gtfsSettings->is_downloading)
        <script>
            function updateProgress() {
                fetch('{{ route('settings.gtfs.progress') }}', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.is_downloading) {
                        // Update progress bar
                        const progressBar = document.getElementById('progress-bar');
                        const progressPercentage = document.getElementById('progress-percentage');
                        if (progressBar && progressPercentage) {
                            progressBar.style.width = data.progress + '%';
                            progressPercentage.textContent = data.progress + '%';
                        }

                        // Update status
                        const statusElement = document.getElementById('download-status');
                        if (statusElement && data.status) {
                            statusElement.textContent = 'Status: ' + data.status;
                        }

                        // Update stage indicators
                        const stages = ['download', 'trips', 'calendar', 'routes', 'stops'];
                        const thresholds = [5, 20, 40, 60, 80];
                        
                        stages.forEach((stage, index) => {
                            const element = document.getElementById('stage-' + stage);
                            if (element) {
                                if (data.progress >= thresholds[index]) {
                                    element.classList.add('text-blue-600', 'font-medium');
                                    element.classList.remove('text-gray-500');
                                } else {
                                    element.classList.remove('text-blue-600', 'font-medium');
                                    element.classList.add('text-gray-500');
                                }
                            }
                        });

                        // Continue polling if still downloading
                        if (data.progress < 100) {
                            setTimeout(updateProgress, 2000); // Poll every 2 seconds
                        } else {
                            // Download completed, reload page after a short delay
                            setTimeout(() => {
                                window.location.reload();
                            }, 3000);
                        }
                    } else {
                        // Download finished, reload page
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error fetching progress:', error);
                    // Try again in 5 seconds on error
                    setTimeout(updateProgress, 5000);
                });
            }

            // Start polling when page loads
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(updateProgress, 1000); // Start after 1 second
            });
        </script>
    @endif
</x-admin-layout>
