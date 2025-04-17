<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        GTFS Settings
                    </h2>

                    @if (session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('settings.gtfs.update') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="url" class="block text-sm font-medium text-gray-700">GTFS URL</label>
                            <input type="url" name="url" id="url" 
                                value="{{ old('url', $gtfsSettings?->url) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500"
                                placeholder="https://example.com/gtfs.zip">
                            @error('url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                @if($gtfsSettings)
                                    <p class="text-sm text-gray-600">Last Download: {{ $gtfsSettings->last_download ? $gtfsSettings->last_download->format('Y-m-d H:i:s') : 'Never' }}</p>
                                    <p class="text-sm text-gray-600">Next Download: {{ $gtfsSettings->next_download ? $gtfsSettings->next_download->format('Y-m-d H:i:s') : 'Not Scheduled' }}</p>
                                    @if($gtfsSettings->is_downloading)
                                        <div class="mt-2">
                                            <p class="text-sm text-blue-600">
                                                <span class="inline-block animate-spin mr-2">⟳</span>
                                                Download in progress since {{ $gtfsSettings->download_started_at->format('Y-m-d H:i:s') }}
                                            </p>
                                            @if($gtfsSettings->download_status)
                                                <p class="text-sm text-gray-600 mt-1">
                                                    Status: {{ $gtfsSettings->download_status }}
                                                </p>
                                            @endif
                                            <div class="mt-2">
                                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                                    <span>Progress</span>
                                                    <span>{{ $gtfsSettings->download_progress ?? 0 }}%</span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                    <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" 
                                                         style="width: {{ $gtfsSettings->download_progress ?? 0 }}%"></div>
                                                </div>
                                                <div class="mt-2 grid grid-cols-5 gap-2 text-xs text-gray-500">
                                                    <div class="text-center {{ $gtfsSettings->download_progress >= 5 ? 'text-blue-600 font-medium' : '' }}">
                                                        Download
                                                    </div>
                                                    <div class="text-center {{ $gtfsSettings->download_progress >= 20 ? 'text-blue-600 font-medium' : '' }}">
                                                        Trips
                                                    </div>
                                                    <div class="text-center {{ $gtfsSettings->download_progress >= 40 ? 'text-blue-600 font-medium' : '' }}">
                                                        Calendar
                                                    </div>
                                                    <div class="text-center {{ $gtfsSettings->download_progress >= 60 ? 'text-blue-600 font-medium' : '' }}">
                                                        Routes
                                                    </div>
                                                    <div class="text-center {{ $gtfsSettings->download_progress >= 80 ? 'text-blue-600 font-medium' : '' }}">
                                                        Stops
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                            <div class="flex space-x-2">
                                <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-neutral-600 hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                                    Save URL
                                </button>
                                
                                @if($gtfsSettings)
                                    <a href="{{ route('settings.gtfs.download') }}" 
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500"
                                        x-data=""
                                        x-on:click="
                                            $el.classList.add('opacity-50', 'cursor-not-allowed');
                                            $el.innerHTML = '<span class=\'flex items-center\'>' +
                                                '<svg class=\'animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700\' xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\'>' +
                                                '<circle class=\'opacity-25\' cx=\'12\' cy=\'12\' r=\'10\' stroke=\'currentColor\' stroke-width=\'4\'></circle>' +
                                                '<path class=\'opacity-75\' fill=\'currentColor\' d=\'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\'></path>' +
                                                '</svg>' +
                                                'Downloading...' +
                                            '</span>';
                                            
                                            fetch($el.href, {
                                                method: 'GET',
                                                headers: {
                                                    'Accept': 'application/json',
                                                    'X-Requested-With': 'XMLHttpRequest'
                                                },
                                                credentials: 'same-origin'
                                            })
                                            .then(response => response.json().then(data => {
                                                if (response.ok) {
                                                    if (data.status === 'in_progress') {
                                                        alert(`Download already in progress for ${data.elapsed_time} seconds. Progress: ${data.progress}%. Please wait.`);
                                                        $el.classList.remove('opacity-50', 'cursor-not-allowed');
                                                        $el.innerHTML = 'Download Now';
                                                    } else {
                                                        window.location.reload();
                                                    }
                                                } else {
                                                    throw new Error(data.message || 'Download failed');
                                                }
                                            }))
                                            .catch(error => {
                                                console.error('Error:', error);
                                                alert(error.message);
                                                $el.classList.remove('opacity-50', 'cursor-not-allowed');
                                                $el.innerHTML = 'Download Now';
                                            });
                                            return false;
                                        ">
                                        Download Now
                                    </a>

                                    <form action="{{ route('settings.gtfs.clear') }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                            onclick="return confirm('Are you sure you want to clear all GTFS data? This action cannot be undone.')"
                                            class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            Clear GTFS Data
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </form>

                    <div class="mt-8 border-t border-gray-200 pt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Route Selection</h3>
                        <p class="text-sm text-gray-600 mb-4">Select which routes should appear in the dashboard.</p>

                        <livewire:route-selector />
                    </div>

                    <div class="mt-8 border-t border-gray-200 pt-8">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Train Table Data</h3>
                                <p class="text-sm text-gray-600">Select which train data should appear in the dashboard table.</p>
                            </div>
                            <button 
                                type="button"
                                x-data=""
                                x-on:click="$dispatch('open-modal', 'train-table-selector')"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-neutral-600 hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500"
                            >
                                Configure Table Data
                            </button>
                        </div>
                        
                        <livewire:train-table-selector />
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
