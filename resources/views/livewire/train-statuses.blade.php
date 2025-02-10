<div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-200">
    <!-- Create Status Form -->
    <div class="p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Add New Train Status</h3>
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status Name</label>
                    <input type="text" wire:model="newStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                    @error('newStatus') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Color</label>
                    <select wire:model="newColor" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                        <option value="gray">Gray</option>
                        <option value="red">Red</option>
                        <option value="green">Green</option>
                        <option value="yellow">Yellow</option>
                    </select>
                    @error('newColor') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-neutral-600 hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                    Add Status
                </button>
            </div>
        </form>
    </div>

    <!-- Statuses Table -->
    <div class="p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Train Statuses</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Color</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($statuses as $status)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $status->status }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $status->color === 'red' ? 'bg-red-100 text-red-800' : 
                                       ($status->color === 'green' ? 'bg-green-100 text-green-800' : 
                                       ($status->color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : 
                                       'bg-gray-100 text-gray-800')) }}">
                                    {{ ucfirst($status->color) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button wire:click="deleteStatus({{ $status->id }})" 
                                    onclick="return confirm('Are you sure you want to delete this status?')"
                                    class="text-red-600 hover:text-red-900">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $statuses->links() }}
        </div>
    </div>
</div>
