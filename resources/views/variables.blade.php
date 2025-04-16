<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Page Header -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Variables</h2>
                    <p class="mt-2 text-sm text-gray-600">Configure variables for the application.</p>
                </div>
            </div>

            <!-- Reasons Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Reasons</h3>
                    <livewire:reasons-manager />
                </div>
            </div>
        </div>
    </div>
</x-admin-layout> 