<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Page Header -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Settings</h2>
                    <p class="mt-2 text-sm text-gray-600">Manage your application settings</p>
                </div>
            </div>

            <!-- Train Status Accordion -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div x-data="{ open: false }" class="border-b border-gray-200">
                    <button 
                        @click="open = !open" 
                        class="w-full px-4 py-6 text-left focus:outline-none"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium leading-6 text-gray-900">Train Status Settings</h3>
                                <p class="mt-1 text-sm text-gray-500">Manage train status types and their display colors</p>
                            </div>
                            <svg 
                                class="h-6 w-6 text-gray-400 transition-transform" 
                                :class="{ 'rotate-180': open }"
                                xmlns="http://www.w3.org/2000/svg" 
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>

                    <!-- Accordion Content -->
                    <div 
                        x-show="open" 
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 transform translate-y-0"
                        x-transition:leave-end="opacity-0 transform -translate-y-2"
                        class="px-4 pb-6"
                    >
                        <livewire:train-statuses />
                    </div>
                </div>
            </div>

            <!-- Reasons Management -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6">
                    <livewire:reasons-management />
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
