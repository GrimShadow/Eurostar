<div>
    @if (session()->has('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    <x-primary-button class="mb-4" wire:click="openModal">Add Group</x-primary-button>

    <!-- Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50">
        <div class="fixed top-0 left-0 z-[99] flex items-center justify-center w-screen h-screen">
            <div class="absolute inset-0 w-full h-full bg-white backdrop-blur-sm bg-opacity-70"></div>
            <div class="relative w-full py-6 bg-white border shadow-lg px-7 border-neutral-200 sm:max-w-lg sm:rounded-lg">
                <div class="flex items-center justify-between pb-3">
                    <h3 class="text-lg font-semibold">{{ $isEditing ? 'Edit' : 'Add' }} Group</h3>
                    <button wire:click="closeModal"
                        class="absolute top-0 right-0 flex items-center justify-center w-8 h-8 mt-5 mr-5 text-gray-600 rounded-full hover:text-gray-800 hover:bg-gray-50">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="relative w-auto pb-8">
                    <form wire:submit.prevent="saveGroup">
                        <div class="mb-4">
                            <x-input-label for="name" value="Name" />
                            <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" />
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <x-input-label for="description" value="Description" />
                            <x-text-input wire:model="description" id="description" type="text" class="mt-1 block w-full" />
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <x-input-label for="users" value="Users" />
                            <div class="mt-1 border border-gray-300 rounded-md p-4 max-h-[300px] overflow-y-auto">
                                <div class="space-y-2">
                                    @foreach($availableUsers as $user)
                                        <div class="flex items-center">
                                            <input 
                                                type="checkbox" 
                                                id="user_{{ $user->id }}"
                                                value="{{ $user->id }}"
                                                wire:model.live="selectedUsers"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                            >
                                            <label for="user_{{ $user->id }}" class="ml-2 block text-sm text-gray-900">
                                                {{ $user->name }} <span class="text-gray-500">({{ $user->email }})</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mt-2 flex justify-between items-center">
                                <span class="text-sm text-gray-600">Selected Users: {{ count($selectedUsers) }}</span>
                                @if(count($selectedUsers) > 0)
                                    <button type="button" wire:click="clearSelection" class="text-sm text-red-600 hover:text-red-800">
                                        Clear Selection
                                    </button>
                                @endif
                            </div>
                            @error('selectedUsers') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end space-x-2">
                            <x-secondary-button wire:click="closeModal" type="button">Cancel</x-secondary-button>
                            <x-primary-button type="submit">{{ $isEditing ? 'Update' : 'Save' }} Group</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Groups Table -->
    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full">
                <div class="overflow-hidden border rounded-lg">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <tr class="text-neutral-500">
                                <th class="px-5 py-3 text-xs font-medium text-left uppercase">Name</th>
                                <th class="px-5 py-3 text-xs font-medium text-left uppercase">Description</th>
                                <th class="px-5 py-3 text-xs font-medium text-left uppercase">Users</th>
                                <th class="px-5 py-3 text-xs font-medium text-right uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($groups as $group)
                            <tr class="text-neutral-800">
                                <td class="px-5 py-4 text-sm font-medium whitespace-nowrap">{{ $group->name }}</td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap">{{ $group->description }}</td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap">
                                    <div class="group relative">
                                        <span>{{ $group->users->count() }} users</span>
                                        @if($group->users->count() > 0)
                                            <div class="hidden group-hover:block absolute z-10 bg-white border rounded-lg shadow-lg p-4 min-w-[200px]">
                                                <ul class="space-y-1">
                                                    @foreach($group->users as $user)
                                                        <li>{{ $user->name }} ({{ $user->email }})</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm font-medium text-right whitespace-nowrap">
                                    <button wire:click="editGroup({{ $group->id }})" class="text-blue-600 hover:text-blue-700 mr-2">
                                        Edit
                                    </button>
                                    <button wire:click="deleteGroup({{ $group->id }})" 
                                        wire:confirm="Are you sure you want to delete this group?"
                                        class="text-red-600 hover:text-red-700">
                                        Delete
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