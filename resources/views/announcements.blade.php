<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Single Alpine component for modal management -->
            <div x-data="{ modalOpen: false }">
                <!-- Button to open modal -->
                <button @click="modalOpen = true"
                    class="inline-flex items-center justify-center h-10 px-4 py-2 text-sm font-medium transition-colors bg-white border rounded-md hover:bg-neutral-100 active:bg-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-neutral-200/60 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none">
                    New Announcement
                </button>

                <!-- Modal -->
                <div x-show="modalOpen" 
                    x-cloak
                    class="fixed inset-0 z-50 overflow-y-auto"
                    @keydown.escape.window="modalOpen = false">
                    
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black bg-opacity-50" @click="modalOpen = false"></div>

                    <!-- Modal Content -->
                    <div class="relative min-h-screen flex items-center justify-center p-4">
                        <div class="relative w-full max-w-lg bg-white rounded-lg shadow-lg p-6"
                            @click.stop>
                            <!-- Modal Header -->
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold">New Announcement</h3>
                                <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- Form Content -->
                            <livewire:create-announcement />
                            
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('livewire:initialized', () => {
                        Livewire.on('close-modal', () => {
                            Alpine.store('modalOpen', false);
                        });
                    });
                </script>
            </div>

            <!-- Livewire Table Component -->
            <livewire:announcements-table />
        </div>
    </div>
</x-app-layout>
