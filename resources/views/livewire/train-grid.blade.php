<div wire:init="loadTrains">
    <div x-data="{ 
            modalOpen: false, 
            selectedTrain: null,
            status: 'on-time',
            newTime: null,
            init() {
                this.$watch('selectedTrain', value => {
                    if (value) {
                        this.newTime = JSON.parse(value).departure;
                    }
                })
            }
        }" x-on:livewire-upload-start="isUploading = true" x-on:livewire-upload-finish="isUploading = false"
        x-on:livewire-upload-error="isUploading = false">


        <!-- Train Cards -->
        <div class="grid grid-cols-4 gap-4">
            @foreach($trains as $train)
                <div class="bg-white rounded-lg shadow-sm p-6 mb-4 hover:shadow-md transition-shadow">
                    <div class="flex flex-col h-full">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="text-lg font-semibold">{{ $train['number'] }}</h3>
                                <!-- View Route Button -->
                                <button wire:click="loadRouteStops('{{ $train['trip_id'] }}')" 
                                        class="ml-auto text-gray-500 hover:text-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M12 1.586l-4 4v12.828l4-4V1.586zM3.707 3.293A1 1 0 002 4v10a1 1 0 00.293.707L6 18.414V5.586L3.707 3.293zM17.707 5.293L14 1.586v12.828l2.293 2.293A1 1 0 0018 16V6a1 1 0 00-.293-.707z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>

                            <div class="text-sm text-gray-500">{{ explode(' - ', $train['route_name'])[0] ?? '' }}</div>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm text-gray-500 mb-1">Departure</div>
                                        <div class="text-2xl font-bold">{{ $train['departure'] }}</div>
                                        <div class="text-sm text-gray-500">Platform {{ $train['departure_platform'] }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-500 mb-1">Arrival</div>
                                        <div class="text-2xl font-bold">{{ $train['arrival'] }}</div>
                                        <div class="text-sm text-gray-500">{{ $train['destination'] }}</div>
                                        <div class="text-sm text-gray-500">Platform {{ $train['arrival_platform'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="mt-4">
                            <div class="text-sm text-gray-500 mb-1">Status</div>
                            <div class="flex items-center">
                                <span class="text-lg font-semibold {{ 
                                    $train['status_color'] === 'red' ? 'text-red-600' : 
                                    ($train['status_color'] === 'green' ? 'text-green-600' : 
                                    ($train['status_color'] === 'yellow' ? 'text-yellow-600' : 
                                    'text-gray-900')) 
                                }}">
                                    @php
                                        $status = $train['status'] ?? 'on-time';
                                        if (is_numeric($status)) {
                                            $status = 'On-time';
                                        }
                                        echo ucfirst($status);
                                    @endphp
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
                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            <button @click="modalOpen = false" type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button 
                                wire:click="updateTrainStatus(
                                    JSON.parse(selectedTrain).number,
                                    status,
                                    newTime
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
