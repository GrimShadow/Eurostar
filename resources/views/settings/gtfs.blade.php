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
                                @endif
                            </div>
                            <div class="flex space-x-2">
                                <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-neutral-600 hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                                    Save URL
                                </button>
                                
                                @if($gtfsSettings)
                                    <a href="{{ route('settings.gtfs.download') }}" 
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
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
