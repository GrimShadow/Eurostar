<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Page Header -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                        Admin Settings</h2>
                    <p class="mt-2 text-sm text-gray-600">Manage system-wide settings and configurations.</p>
                </div>
            </div>

            

            <!-- System Information Card -->
            <div class="bg-white shadow-sm sm:rounded-xl p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">System Information</h3>
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Environment</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ config('app.env') }}</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Debug Mode</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ config('app.debug') ? 'Enabled' : 'Disabled' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Maintenance Mode Card -->
            <div class="bg-white shadow-sm sm:rounded-xl p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Maintenance Mode</h3>
                <div class="space-y-4">
                    <livewire:maintenance-mode-toggle />
                </div>
            </div>

            <!-- Cache Management Card -->
            <div class="bg-white shadow-sm sm:rounded-xl p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Cache Management</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Clear application cache</p>
                        </div>
                        <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Clear Cache
                        </button>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Clear view cache</p>
                        </div>
                        <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Clear Views
                        </button>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Clear all announcements</p>
                        </div>
                        <livewire:clear-announcements />
                    </div>
                </div>
            </div>

            <!-- Banner Settings Card -->
            <div class="bg-white shadow-sm sm:rounded-xl p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Banner Settings</h3>
                <div class="space-y-4">
                    <livewire:banner-status-toggle />
                </div>
            </div>

            <!-- Log Settings Card -->
            <div class="bg-white shadow-sm sm:rounded-xl p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Log Settings</h3>
                <p class="text-sm text-gray-600 mb-4">Control which types of logs are written to help with debugging and monitoring.</p>
                <div class="space-y-4">
                    <livewire:log-settings />
                </div>
            </div>

            <!-- Active Users Card -->
            <div class="bg-white shadow-sm sm:rounded-xl p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Active Users</h3>
                <div class="space-y-4">
                    <livewire:active-users />
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
