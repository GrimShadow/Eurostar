<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-10">
                <!-- Add user button -->
                <x-primary-button class="mb-4" wire:click="openAddUserModal">Add User</x-primary-button>

                <!-- Add user modal -->
                <x-modal name="addUserModal" title="Add User">
                    <x-text-input type="text" name="name" label="Name" />
                    <x-text-input type="email" name="email" label="Email" />
                    <x-text-input type="password" name="password" label="Password" />
                </x-modal>

                <!-- Users Table -->
                <div class="flex flex-col">
                    <div class="overflow-x-auto">
                        <div class="inline-block min-w-full">
                            <div class="overflow-hidden border rounded-lg">
                                <table class="min-w-full divide-y divide-neutral-200">
                                    <thead class="bg-neutral-50">
                                        <tr class="text-neutral-500">
                                            <th class="px-5 py-3 text-xs font-medium text-left uppercase">Name</th>
                                            <th class="px-5 py-3 text-xs font-medium text-left uppercase">Email</th>
                                            <th class="px-5 py-3 text-xs font-medium text-left uppercase">Role</th>
                                            <th class="px-5 py-3 text-xs font-medium text-right uppercase">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-neutral-200">
                                        @foreach ($users as $user)
                                        <tr class="text-neutral-800">
                                            <td class="px-5 py-4 text-sm font-medium whitespace-nowrap">
                                                {{ $user->name }}</td>
                                            <td class="px-5 py-4 text-sm whitespace-nowrap">{{ $user->email }}</td>
                                            <td class="px-5 py-4 text-sm whitespace-nowrap">Role</td>
                                            <td class="px-5 py-4 text-sm font-medium text-right whitespace-nowrap">
                                                <a class="text-blue-600 hover:text-blue-700" href="#">Edit</a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
