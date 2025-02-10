<div wire:init="loadTrains">
    <div x-data="{ 
            modalOpen: false, 
            selectedTrain: null,
            status: 'on-time',
            newTime: null,
            init() {
                this.$watch('status', value => {
                    if (value === 'delayed' && !this.newTime) {
                        this.newTime = JSON.parse(this.selectedTrain).departure;
                    }
                })
            }
        }" x-on:livewire-upload-start="isUploading = true" x-on:livewire-upload-finish="isUploading = false"
        x-on:livewire-upload-error="isUploading = false">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-4">
            @foreach($trains as $train)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200">
                <div class="p-5 flex flex-col h-full">
                    <!-- Header with Train Number and Menu -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-2">
                            <x-train-front-on class="w-6 h-6 text-gray-600" />
                            <span class="text-lg font-semibold text-gray-900">{{ $train['number'] }}</span>
                        </div>
                        <button 
                            type="button" 
                            @click.stop="modalOpen = true; selectedTrain = $el.dataset.train"
                            data-train='@json($train)'
                            class="rounded-full p-1.5 hover:bg-gray-100 transition-colors">
                            <x-heroicon-s-bars-3-bottom-right class="w-5 h-5 text-gray-500" />
                        </button>
                    </div>

                    <!-- Train Info -->
                    <div class="space-y-4">
                        <!-- Route -->
                        <div>
                            <p class="text-sm text-gray-600 font-medium line-clamp-1">
                                {{ $train['route_name'] }}
                            </p>
                        </div>

                        <!-- Departure Time -->
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Departure</span>
                            <p class="text-lg font-semibold text-gray-900 mt-0.5">
                                {{ $train['departure'] }}
                            </p>
                        </div>

                        <!-- Status -->
                        <div class="mt-auto">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</span>
                            <p class="text-base font-semibold mt-0.5 {{ 
                                $train['status_color'] === 'red' ? 'text-red-600' : 
                                ($train['status_color'] === 'green' ? 'text-green-600' : 
                                ($train['status_color'] === 'yellow' ? 'text-yellow-600' : 
                                'text-gray-900')) 
                            }}">
                                {{ $train['status'] }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Modal -->
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

                            <div x-show="status === 'delayed'" x-transition>
                                <label class="block text-sm font-medium text-gray-700 mb-1">New Time</label>
                                <input type="time" x-model="newTime"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            <button @click="modalOpen = false" type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button x-on:click="
        let trainData = JSON.parse(selectedTrain);
        $wire.updateTrainStatus(trainData.number, status, status === 'delayed' ? newTime : null).then(() => {
            modalOpen = false;
            $wire.$refresh();
        });
    " type="button" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700">
                                Update Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
