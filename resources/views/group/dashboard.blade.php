<x-app-layout>
    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-end">
                <a href="{{ route('group.routes', $group) }}" 
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                    Configure Routes
                </a>
            </div>

            <div class="overflow-hidden">
                <livewire:train-grid wire:poll.5s />
                <livewire:train-table wire:poll.5s />
            </div>

            <!-- Current Time Display -->
            <div class="fixed bottom-4 right-4">
                <div class="bg-white shadow-sm rounded-lg px-4 py-2">
                    <span class="text-gray-500">Current Time:</span>
                    <span class="font-semibold ml-2" x-data="{ time: new Date() }" x-init="setInterval(() => time = new Date(), 1000)" x-text="time.toLocaleTimeString()"></span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 