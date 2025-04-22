<div class="space-y-6">
    <!-- Global Check-in Time Setting -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Global Check-in Time Setting</h3>
        <p class="text-sm text-gray-600 mb-4">
            Set the default check-in start time offset for all trains. This is the number of minutes before departure that check-in starts.
        </p>
        
        <div class="flex items-center space-x-4">
            <div class="flex-1">
                <label for="globalCheckInOffset" class="block text-sm font-medium text-gray-700">Minutes Before Departure</label>
                <input type="number" 
                       wire:model="globalCheckInOffset" 
                       id="globalCheckInOffset"
                       min="1"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500 sm:text-sm">
            </div>
            <button wire:click="updateGlobalOffset"
                    class="mt-6 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-neutral-600 hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                Save Global Setting
            </button>
        </div>
    </div>

    <!-- Specific Train Settings -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Specific Train Settings</h3>
        <p class="text-sm text-gray-600 mb-4">
            Set custom check-in start times for specific trains. These settings will override the global setting for the specified trains.
        </p>

        <!-- Add New Train Setting -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h4 class="text-md font-medium text-gray-900 mb-4">Add New Train Setting</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="newTrainId" class="block text-sm font-medium text-gray-700">Train ID</label>
                    <input type="text" 
                           wire:model="newTrainId" 
                           id="newTrainId"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500 sm:text-sm"
                           placeholder="Enter train ID">
                </div>
                <div>
                    <label for="newTrainOffset" class="block text-sm font-medium text-gray-700">Minutes Before Departure</label>
                    <input type="number" 
                           wire:model="newTrainOffset" 
                           id="newTrainOffset"
                           min="1"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500 sm:text-sm">
                </div>
            </div>
            <button wire:click="addSpecificTrain"
                    class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-neutral-600 hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                Add Train Setting
            </button>
        </div>

        <!-- Existing Train Settings -->
        @if(count($specificTrainTimes) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Train ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Minutes Before Departure</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($specificTrainTimes as $trainId => $offset)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $trainId }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $offset }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="removeSpecificTrain('{{ $trainId }}')"
                                            class="text-red-600 hover:text-red-900">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 italic">No specific train settings configured yet.</p>
        @endif
    </div>
</div> 