<x-admin-layout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 sm:text-4xl">
                                Settings
                            </h1>
                            <p class="mt-2 text-lg text-gray-600">
                                Manage your application configuration and preferences
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Settings Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-1 gap-8">

                    <!-- System Info Card -->
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300">
                        <div class="px-6 py-5">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                            </path>
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">System Information</h3>
                                    <p class="text-sm text-gray-500">Application version and system details</p>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <dl class="grid grid-cols-1 gap-2 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Version</dt>
                                        <dd class="font-medium text-gray-900">2511.4</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Environment</dt>
                                        <dd class="font-medium text-gray-900">{{ config('app.env') }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Last Updated</dt>
                                        <dd class="font-medium text-gray-900">{{ now()->format('M j, Y') }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <!-- Train Status Settings Card -->
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300">
                        <div x-data="{ open: false }" class="divide-y divide-gray-100">
                            <!-- Card Header -->
                            <div class="px-6 py-5">
                                <button @click="open = !open"
                                    class="w-full flex items-center justify-between text-left focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-lg p-2 -m-2 transition-colors duration-200 hover:bg-gray-50">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">Train Status Settings</h3>
                                            <p class="text-sm text-gray-500">Manage train status types and their display
                                                colors</p>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-gray-400 transition-transform duration-200"
                                            :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </button>
                            </div>

                            <!-- Card Content -->
                            <div x-show="open" class="px-6 pb-6">
                                <div class="pt-4">
                                    <livewire:train-statuses />
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Check-in Status Settings Card -->
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300">
                        <div x-data="{ open: false }" class="divide-y divide-gray-100">
                            <!-- Card Header -->
                            <div class="px-6 py-5">
                                <button @click="open = !open"
                                    class="w-full flex items-center justify-between text-left focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-lg p-2 -m-2 transition-colors duration-200 hover:bg-gray-50">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">Check-in Status Settings</h3>
                                            <p class="text-sm text-gray-500">Manage check-in status types and their display
                                                colors</p>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-gray-400 transition-transform duration-200"
                                            :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </button>
                            </div>

                            <!-- Card Content -->
                            <div x-show="open" class="px-6 pb-6">
                                <div class="pt-4">
                                    <livewire:check-in-statuses />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
