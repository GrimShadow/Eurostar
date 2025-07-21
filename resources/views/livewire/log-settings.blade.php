<div>
    @if (session()->has('message'))
        <div class="rounded-md bg-green-50 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">
                        {{ session('message') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="space-y-6">
        <!-- GTFS Logging Section -->
        <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="text-lg font-medium text-gray-900 mb-3">GTFS Logging</h4>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Error Logs</label>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input 
                            type="checkbox" 
                            wire:model.live="gtfs_error_logs"
                            class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer {{ $gtfs_error_logs ? 'right-0 border-green-400' : 'border-gray-300' }}"
                        />
                        <label class="toggle-label block overflow-hidden h-6 rounded-full {{ $gtfs_error_logs ? 'bg-green-400' : 'bg-gray-300' }} cursor-pointer"></label>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Debug Logs</label>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input 
                            type="checkbox" 
                            wire:model.live="gtfs_debug_logs"
                            class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer {{ $gtfs_debug_logs ? 'right-0 border-green-400' : 'border-gray-300' }}"
                        />
                        <label class="toggle-label block overflow-hidden h-6 rounded-full {{ $gtfs_debug_logs ? 'bg-green-400' : 'bg-gray-300' }} cursor-pointer"></label>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Information Logs</label>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input 
                            type="checkbox" 
                            wire:model.live="gtfs_information_logs"
                            class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer {{ $gtfs_information_logs ? 'right-0 border-green-400' : 'border-gray-300' }}"
                        />
                        <label class="toggle-label block overflow-hidden h-6 rounded-full {{ $gtfs_information_logs ? 'bg-green-400' : 'bg-gray-300' }} cursor-pointer"></label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aviavox Logging Section -->
        <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="text-lg font-medium text-gray-900 mb-3">Aviavox Logging</h4>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Error Logs</label>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input 
                            type="checkbox" 
                            wire:model.live="aviavox_error_logs"
                            class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer {{ $aviavox_error_logs ? 'right-0 border-green-400' : 'border-gray-300' }}"
                        />
                        <label class="toggle-label block overflow-hidden h-6 rounded-full {{ $aviavox_error_logs ? 'bg-green-400' : 'bg-gray-300' }} cursor-pointer"></label>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Debug Logs</label>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input 
                            type="checkbox" 
                            wire:model.live="aviavox_debug_logs"
                            class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer {{ $aviavox_debug_logs ? 'right-0 border-green-400' : 'border-gray-300' }}"
                        />
                        <label class="toggle-label block overflow-hidden h-6 rounded-full {{ $aviavox_debug_logs ? 'bg-green-400' : 'bg-gray-300' }} cursor-pointer"></label>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Information Logs</label>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input 
                            type="checkbox" 
                            wire:model.live="aviavox_information_logs"
                            class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer {{ $aviavox_information_logs ? 'right-0 border-green-400' : 'border-gray-300' }}"
                        />
                        <label class="toggle-label block overflow-hidden h-6 rounded-full {{ $aviavox_information_logs ? 'bg-green-400' : 'bg-gray-300' }} cursor-pointer"></label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Automatic Rules Logging Section -->
        <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="text-lg font-medium text-gray-900 mb-3">Automatic Rules Logging</h4>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Error Logs</label>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input 
                            type="checkbox" 
                            wire:model.live="automatic_rules_error_logs"
                            class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer {{ $automatic_rules_error_logs ? 'right-0 border-green-400' : 'border-gray-300' }}"
                        />
                        <label class="toggle-label block overflow-hidden h-6 rounded-full {{ $automatic_rules_error_logs ? 'bg-green-400' : 'bg-gray-300' }} cursor-pointer"></label>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Debug Logs</label>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input 
                            type="checkbox" 
                            wire:model.live="automatic_rules_debug_logs"
                            class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer {{ $automatic_rules_debug_logs ? 'right-0 border-green-400' : 'border-gray-300' }}"
                        />
                        <label class="toggle-label block overflow-hidden h-6 rounded-full {{ $automatic_rules_debug_logs ? 'bg-green-400' : 'bg-gray-300' }} cursor-pointer"></label>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Information Logs</label>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input 
                            type="checkbox" 
                            wire:model.live="automatic_rules_information_logs"
                            class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer {{ $automatic_rules_information_logs ? 'right-0 border-green-400' : 'border-gray-300' }}"
                        />
                        <label class="toggle-label block overflow-hidden h-6 rounded-full {{ $automatic_rules_information_logs ? 'bg-green-400' : 'bg-gray-300' }} cursor-pointer"></label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcement Logging Section -->
        <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="text-lg font-medium text-gray-900 mb-3">Announcement Logging</h4>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Error Logs</label>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input 
                            type="checkbox" 
                            wire:model.live="announcement_error_logs"
                            class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer {{ $announcement_error_logs ? 'right-0 border-green-400' : 'border-gray-300' }}"
                        />
                        <label class="toggle-label block overflow-hidden h-6 rounded-full {{ $announcement_error_logs ? 'bg-green-400' : 'bg-gray-300' }} cursor-pointer"></label>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Debug Logs</label>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input 
                            type="checkbox" 
                            wire:model.live="announcement_debug_logs"
                            class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer {{ $announcement_debug_logs ? 'right-0 border-green-400' : 'border-gray-300' }}"
                        />
                        <label class="toggle-label block overflow-hidden h-6 rounded-full {{ $announcement_debug_logs ? 'bg-green-400' : 'bg-gray-300' }} cursor-pointer"></label>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Information Logs</label>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input 
                            type="checkbox" 
                            wire:model.live="announcement_information_logs"
                            class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer {{ $announcement_information_logs ? 'right-0 border-green-400' : 'border-gray-300' }}"
                        />
                        <label class="toggle-label block overflow-hidden h-6 rounded-full {{ $announcement_information_logs ? 'bg-green-400' : 'bg-gray-300' }} cursor-pointer"></label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .toggle-checkbox:checked {
            right: 0;
            border-color: #68D391;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #68D391;
        }
    </style>
</div>
