<div>
    @if (session()->has('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    <x-primary-button class="mb-4" wire:click="openModal">Add User</x-primary-button>

    <!-- Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50">
        <div class="fixed top-0 left-0 z-[99] flex items-center justify-center w-screen h-screen">
            <div class="absolute inset-0 w-full h-full bg-white backdrop-blur-sm bg-opacity-70"></div>
            <div class="relative w-full py-6 bg-white border shadow-lg px-7 border-neutral-200 sm:max-w-lg sm:rounded-lg">
                <div class="flex items-center justify-between pb-3">
                    <h3 class="text-lg font-semibold">{{ $isEditing ? 'Edit' : 'Add' }} User</h3>
                    <button wire:click="closeModal"
                        class="absolute top-0 right-0 flex items-center justify-center w-8 h-8 mt-5 mr-5 text-gray-600 rounded-full hover:text-gray-800 hover:bg-gray-50">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="relative w-auto pb-8">
                    <form wire:submit.prevent="saveUser">
                        <div class="mb-4">
                            <x-input-label for="name" value="Name" />
                            <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" />
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <x-input-label for="email" value="Email" />
                            <x-text-input wire:model="email" id="email" type="email" class="mt-1 block w-full" />
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <x-input-label for="password" value="Password" />
                            <x-text-input wire:model="password" id="password" type="password" class="mt-1 block w-full" />
                            @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <x-input-label for="role" value="Role" />
                            <select wire:model="role" id="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="user">User</option>
                                <option value="administrator">Administrator</option>
                            </select>
                            @error('role') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end space-x-2">
                            <x-secondary-button wire:click="closeModal" type="button">Cancel</x-secondary-button>
                            <x-primary-button type="submit">{{ $isEditing ? 'Update' : 'Save' }} User</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

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
                                <td class="px-5 py-4 text-sm font-medium whitespace-nowrap">{{ $user->name }}</td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap">{{ $user->email }}</td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $user->role === 'administrator' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm font-medium text-right whitespace-nowrap">
                                    <button wire:click="editUser({{ $user->id }})" class="text-blue-600 hover:text-blue-700">
                                        Edit
                                    </button>
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