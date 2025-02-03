<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Page Header -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Train Status Settings</h2>
                    <p class="mt-2 text-sm text-gray-600">Manage train status types and their display colors.</p>
                </div>
            </div>

            <livewire:train-statuses />
        </div>
    </div>
</x-admin-layout>
