<div wire:init="loadTrains">
    <div x-data="{ 
            modalOpen: false, 
            selectedTrain: null,
            status: 'on-time',
            newTime: null,
            platform: null,
            init() {
                this.$watch('selectedTrain', value => {
                    if (value) {
                        const train = JSON.parse(value);
                        this.newTime = train.new_departure_time || train.departure_time;
                        this.status = train.status;
                        this.platform = train.departure_platform !== 'TBD' ? train.departure_platform : null;
                    }
                })
            }
        }" x-on:livewire-upload-start="isUploading = true" x-on:livewire-upload-finish="isUploading = false"
        x-on:livewire-upload-error="isUploading = false">

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="mb-4 bg-yellow-50 border border-yellow-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">{{ session('message') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Date Selector -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Train Schedule</h2>
                <p class="text-sm text-gray-600">
                    @if($selectedDate === now()->format('Y-m-d'))
                        Showing {{ count($trains) }} {{ count($trains) === 1 ? 'train' : 'trains' }} for today
                    @else
                        Showing {{ count($trains) }} {{ count($trains) === 1 ? 'train' : 'trains' }} for {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}
                    @endif
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <label for="date-selector" class="text-sm font-medium text-gray-700">Select Date:</label>
                <input 
                    type="date" 
                    id="date-selector"
                    wire:model.live="selectedDate"
                    min="{{ now()->format('Y-m-d') }}"
                    max="{{ now()->addDays(30)->format('Y-m-d') }}"
                    class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                >
                <button 
                    wire:click="$set('selectedDate', '{{ now()->format('Y-m-d') }}')"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Today
                </button>
            </div>
        </div>


        <!-- Loading State -->
        <div wire:loading.delay wire:target="selectedDate" class="mb-4">
            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                <div class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-blue-700 font-medium">Loading trains for selected date...</span>
                </div>
            </div>
        </div>

        <!-- Train Cards -->
        <div class="grid grid-cols-4 gap-4" wire:loading.remove wire:target="selectedDate">
            @if(count($trains) === 0)
                <div class="col-span-4">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No trains found</h3>
                        <p class="mt-2 text-sm text-gray-500">
                            @if($selectedDate === now()->format('Y-m-d'))
                                No trains are currently scheduled for your selected routes today.
                            @else
                                No trains are scheduled for {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }} on your selected routes.
                            @endif
                        </p>
                        <div class="mt-6">
                            <button 
                                wire:click="$set('selectedDate', '{{ now()->format('Y-m-d') }}')"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                View Today's Trains
                            </button>
                        </div>
                    </div>
                </div>
            @else
            @foreach($trains as $train)
                <div class="bg-white rounded-lg shadow-sm p-6 mb-4 hover:shadow-md transition-shadow">
                    <div class="flex flex-col h-full">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="text-lg font-semibold">{{ $train['route_name'] }}</h3>
                                <p class="text-sm text-gray-500">{{ $train['stop_name'] }}</p>
                                <!-- View Route Button -->
                                <button wire:click="loadRouteStops('{{ $train['trip_id'] }}')" 
                                        class="ml-auto text-gray-500 hover:text-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M12 1.586l-4 4v12.828l4-4V1.586zM3.707 3.293A1 1 0 002 4v10a1 1 0 00.293.707L6 18.414V5.586L3.707 3.293zM17.707 5.293L14 1.586v12.828l2.293 2.293A1 1 0 0018 16V6a1 1 0 00-.293-.707z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>

                            <div class="text-sm text-gray-500">{{ $train['route_short_name'] }}</div>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm text-gray-500 mb-1">Arrival</div>
                                        <div class="text-2xl font-bold">{{ \Carbon\Carbon::parse($train['arrival_time'])->format('H:i') }}</div>
                                        <div class="text-sm text-gray-500">
                                            Platform 
                                            <span class="{{ $train['is_realtime_update'] ? 'text-orange-500 font-semibold' : '' }}">
                                                {{ $train['arrival_platform'] }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-500 mb-1">Departure</div>
                                        <div class="text-2xl font-bold">
                                            @if($train['new_departure_time'])
                                                <span class="line-through text-gray-400">{{ \Carbon\Carbon::parse($train['departure_time'])->format('H:i') }}</span>
                                                <span class="ml-2 {{ $train['is_realtime_update'] ? 'text-orange-500' : '' }}">{{ \Carbon\Carbon::parse($train['new_departure_time'])->format('H:i') }}</span>
                                            @else
                                                <span class="{{ $train['is_realtime_update'] ? 'text-orange-500' : '' }}">{{ \Carbon\Carbon::parse($train['departure_time'])->format('H:i') }}</span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Platform 
                                            <span class="{{ $train['is_realtime_update'] ? 'text-orange-500 font-semibold' : '' }}">
                                                {{ $train['departure_platform'] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="mt-4">
                            <div class="text-sm text-gray-500 mb-1">Status</div>
                            <div class="flex items-center">
                                <span class="text-lg font-semibold" style="color: rgb({{ $train['status_color'] ?? '156,163,175' }});">
                                    {{ isset($train['status']) && !empty($train['status']) ? ucfirst($train['status']) : 'On time' }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button 
                                type="button" 
                                @click="modalOpen = true; selectedTrain = $el.dataset.train"
                                data-train='@json($train)'
                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-700">
                                Select
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
            @endif
        </div>

        <!-- Status Update Modal -->
        <div x-show="modalOpen" class="fixed inset-0 z-50" x-cloak>
            <div class="fixed top-0 left-0 z-[99] flex items-center justify-center w-screen h-screen">
                <div class="absolute inset-0 w-full h-full bg-black/20 backdrop-blur-sm"></div>
                <div class="relative w-full max-w-lg mx-auto bg-white shadow-lg rounded-xl">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">
                            Update Train Status
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select x-model="status"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @foreach($statuses as $status)
                                    <option value="{{ $status->status }}">{{ ucfirst($status->status) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Departure Time</label>
                                <input type="time" x-model="newTime"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-sm text-gray-500">Update the departure time if needed</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Platform</label>
                                <input type="text" x-model="platform"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Enter platform number">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            <button @click="modalOpen = false" type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button 
                                wire:click="updateTrainStatus(
                                    JSON.parse(selectedTrain).trip_id,
                                    status,
                                    newTime,
                                    platform
                                )"
                                @click="modalOpen = false"
                                type="button" 
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700">
                                Update Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Route Modal -->
        @if(count($routeStops) > 0)
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">Train Journey</h3>
                        <button wire:click="$set('routeStops', [])" class="text-gray-500 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="relative">
                        <!-- Progress Bar -->
                        <div class="absolute left-4 top-0 bottom-0 w-1 bg-gray-200">
                            <div class="absolute top-0 left-0 w-full h-1/3 bg-blue-600"></div>
                        </div>

                        <div class="space-y-6 pl-8">
                            @foreach($routeStops as $index => $stop)
                                <div class="relative">
                                    <!-- Stop Circle -->
                                    <div class="absolute left-0 top-1/2 -translate-y-1/2">
                                        <div class="w-8 h-8 rounded-full {{ $index === 0 ? 'bg-blue-600' : 'bg-gray-300' }} flex items-center justify-center text-white">
                                            {{ $stop['sequence'] }}
                                        </div>
                                    </div>

                                    <!-- Stop Info -->
                                    <div class="ml-8">
                                        <div class="font-medium text-lg">{{ $stop['name'] }}</div>
                                        <div class="text-sm text-gray-500">
                                            @if($index === 0)
                                                <span class="font-semibold">Departure:</span> {{ $stop['departure'] }}
                                            @elseif($index === count($routeStops) - 1)
                                                <span class="font-semibold">Arrival:</span> {{ $stop['arrival'] }}
                                            @else
                                                <span class="font-semibold">Arrival:</span> {{ $stop['arrival'] }} | 
                                                <span class="font-semibold">Departure:</span> {{ $stop['departure'] }}
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Train Icon for Current Position -->
                                    @if($index === 0)
                                        <div class="absolute right-0 top-1/2 -translate-y-1/2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M4 16v6h6v-6h-6zm10 0v6h6v-6h-6zm-10-10v6h6v-6h-6zm10 0v6h6v-6h-6zm-10-10v6h6v-6h-6zm10 0v6h6v-6h-6z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
