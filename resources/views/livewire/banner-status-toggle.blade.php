<div class="flex items-center justify-between">
    <div>
        <p class="text-sm text-gray-500">Status Banner Visibility</p>
    </div>
    <button 
        type="button" 
        wire:click="toggleBanner"
        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $bannerStatus ? 'bg-blue-600' : 'bg-gray-200' }}"
        role="switch"
        aria-checked="{{ $bannerStatus ? 'true' : 'false' }}"
    >
        <span
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $bannerStatus ? 'translate-x-5' : 'translate-x-0' }}"
        ></span>
    </button>
</div> 