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
}">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-4">
        @foreach($trains as $train)
        <div class="border bg-white border-gray-300 rounded-3xl dark:border-gray-600 h-auto min-h-[8rem] md:min-h-[16rem]">
            <div class="p-4 flex flex-col h-full">
                <!-- Header with Train Number and Menu -->
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <x-train-front-on />
                        <span class="text-xl font-bold truncate">{{ $train['number'] }}</span>
                    </div>
                    <button type="button" 
                        @click.stop="modalOpen = true; selectedTrain = $el.dataset.train" 
                        data-train='@json($train)'
                        class="inline-flex items-center justify-center h-8 w-8 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors flex-shrink-0">
                        <x-heroicon-s-bars-3-bottom-right class="w-5 h-5" />
                    </button>
                </div>

                <!-- Train Info -->
                <div class="space-y-2 overflow-hidden">
                    <!-- Destination & Route -->
                    <div>
                        <p class="text-sm text-gray-600 truncate">
                            {{ $train['route_name'] }}
                        </p>
                    </div>

                    <!-- Departure Time -->
                    <div>
                        <h4 class="text-xs font-medium text-gray-600 uppercase">
                            Departure
                        </h4>
                        <p class="text-lg font-bold text-gray-900">
                            {{ $train['departure'] }}
                        </p>
                    </div>

                    <!-- Status -->
                    <div>
                        <h4 class="text-xs font-medium text-gray-600 uppercase">
                            Status
                        </h4>
                        <p class="text-lg font-bold truncate {{ $train['status_color'] === 'green' ? 'text-green-600' : ($train['status_color'] === 'red' ? 'text-red-600' : 'text-gray-900') }}">
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
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-gray-900">
                            Update Train Status
                        </h3>
                        <button @click="modalOpen = false"
                            class="text-gray-400 hover:text-gray-600 focus:outline-none">
                            <x-heroicon-s-x-mark class="w-6 h-6" />
                        </button>
                    </div>

                    <!-- Modal Content -->
                    <div class="mb-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select x-model="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach($statuses as $status)
                                    <option value="{{ $status->status }}">{{ ucfirst($status->status) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="status === 'delayed'" x-transition>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Departure Time</label>
                            <input 
                                type="time" 
                                x-model="newTime"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex justify-end space-x-3">
                        <button @click="modalOpen = false" type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </button>
                        <button 
                            @click="
                                $wire.updateTrainStatus(
                                    JSON.parse(selectedTrain).number, 
                                    status, 
                                    status === 'delayed' ? newTime : null
                                );
                                modalOpen = false;
                            " 
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Update Status
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
