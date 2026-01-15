<div>
    @if($showConflictModal && $currentConflict)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeConflictModal"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    GTFS Realtime Conflict Detected
                                </h3>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-500 mb-4">
                                        A GTFS realtime update conflicts with a manual change you made. Please choose how to proceed:
                                    </p>
                                    
                                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                        <div class="space-y-2">
                                            <div>
                                                <span class="text-xs font-medium text-gray-500">Trip ID:</span>
                                                <span class="ml-2 text-sm text-gray-900">{{ $currentConflict['trip_id'] ?? 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-xs font-medium text-gray-500">Stop ID:</span>
                                                <span class="ml-2 text-sm text-gray-900">{{ $currentConflict['stop_id'] ?? 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-xs font-medium text-gray-500">Field:</span>
                                                <span class="ml-2 text-sm text-gray-900 capitalize">{{ str_replace('_', ' ', $currentConflict['field_type'] ?? 'N/A') }}</span>
                                            </div>
                                            <div class="pt-2 border-t border-gray-200">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-medium text-gray-500">Your Manual Value:</span>
                                                    <span class="text-sm font-semibold text-blue-600">{{ $currentConflict['manual_value'] ?? 'N/A' }}</span>
                                                </div>
                                                <div class="flex items-center justify-between mt-1">
                                                    <span class="text-xs font-medium text-gray-500">GTFS Realtime Value:</span>
                                                    <span class="text-sm font-semibold text-green-600">{{ $currentConflict['realtime_value'] ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if(count($conflicts) > 1)
                                        <div class="mb-4 text-xs text-gray-500">
                                            {{ count($conflicts) - 1 }} more conflict(s) pending
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" 
                            wire:click="useRealtimeValue({{ $currentConflict['id'] }})"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Use GTFS Update
                        </button>
                        <button type="button" 
                            wire:click="keepManualValue({{ $currentConflict['id'] }})"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Keep Manual Value
                        </button>
                        <button type="button" 
                            wire:click="closeConflictModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>


