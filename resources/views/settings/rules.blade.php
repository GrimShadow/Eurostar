<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Page Header -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Rules & Triggers</h2>
                    <p class="mt-2 text-sm text-gray-600">Configure automated rules for train status updates and scheduled announcements.</p>
                </div>
            </div>

            <!-- Train Rules Section -->
            <div>
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Train Status Rules</h3>
                <p class="text-sm text-gray-600 mb-4">Create rules that automatically change train statuses based on conditions like time, current status, or train properties.</p>
            <livewire:train-rules />
            </div>

            <!-- Automated Announcements Section -->
            <div>
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Automated Announcements</h3>
                <p class="text-sm text-gray-600 mb-4">Schedule announcements to be made automatically at regular intervals during specific time periods.</p>
                <livewire:automated-announcements />
            </div>
        </div>
    </div>
</x-admin-layout>
