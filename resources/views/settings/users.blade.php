<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-10 space-y-10">
                <!-- User Management -->
                <div>
                    <h2 class="text-lg font-medium text-gray-900 mb-4">User Management</h2>
                    <livewire:user-management />
                </div>

                <!-- Group Management -->
                <div>
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Group Management</h2>
                    <livewire:group-management />
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
