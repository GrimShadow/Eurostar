<div class="flex items-center justify-between">
    <div>
        <p class="text-sm text-gray-500">Maintenance Mode</p>
        <p class="text-xs text-gray-400 mt-1">When enabled, only administrators can access the system</p>
    </div>
    <button 
        type="button" 
        wire:click="toggleMaintenanceMode"
        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 {{ $maintenanceMode ? 'bg-red-600' : 'bg-gray-200' }}"
        role="switch"
        aria-checked="{{ $maintenanceMode ? 'true' : 'false' }}"
    >
        <span
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $maintenanceMode ? 'translate-x-5' : 'translate-x-0' }}"
        ></span>
    </button>
</div> 