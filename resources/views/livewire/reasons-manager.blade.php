<div class="bg-white shadow-sm rounded-lg">
    <div class="p-6">
        <!-- Form -->
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">Code</label>
                    <input type="text" wire:model="code" id="code" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" wire:model="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Descriptions Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-medium text-gray-700">Descriptions</label>
                    @if(count($this->getAvailableAdditionalLanguages()) > 0)
                        <div class="flex gap-2">
                            @foreach($this->getAvailableAdditionalLanguages() as $lang)
                                <button 
                                    type="button" 
                                    wire:click="addDescriptionLanguage('{{ $lang }}')"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                                    Add {{ $availableLanguages[$lang] }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- English Description (Always visible) -->
                <div>
                    <label for="description-en" class="block text-sm font-medium text-gray-700">
                        Description (English) <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        wire:model="descriptions.en" 
                        id="description-en" 
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="Enter description in English">
                    @error('descriptions.en') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Additional Language Descriptions -->
                @if(isset($descriptions['nl']))
                    <div class="flex items-start gap-2">
                        <div class="flex-1">
                            <label for="description-nl" class="block text-sm font-medium text-gray-700">
                                Description (Dutch)
                            </label>
                            <input 
                                type="text" 
                                wire:model="descriptions.nl" 
                                id="description-nl" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Enter description in Dutch">
                            @error('descriptions.nl') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <button 
                            type="button" 
                            wire:click="removeDescriptionLanguage('nl')"
                            class="mt-6 inline-flex items-center px-2 py-1.5 text-xs font-medium text-red-700 hover:text-red-900">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif

                @if(isset($descriptions['fr']))
                    <div class="flex items-start gap-2">
                        <div class="flex-1">
                            <label for="description-fr" class="block text-sm font-medium text-gray-700">
                                Description (French)
                            </label>
                            <input 
                                type="text" 
                                wire:model="descriptions.fr" 
                                id="description-fr" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Enter description in French">
                            @error('descriptions.fr') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <button 
                            type="button" 
                            wire:click="removeDescriptionLanguage('fr')"
                            class="mt-6 inline-flex items-center px-2 py-1.5 text-xs font-medium text-red-700 hover:text-red-900">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif
            </div>
            <div class="flex justify-end space-x-3">
                @if($editingId)
                    <button type="button" wire:click="cancel" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </button>
                @endif
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                    {{ $editingId ? 'Update' : 'Add' }} Reason
                </button>
            </div>
        </form>

        <!-- Table -->
        <div class="mt-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($reasons as $reason)
                            @php
                                $allDescriptions = $reason->getAllDescriptions();
                                $descriptionParts = [];
                                if (!empty($allDescriptions['en'])) {
                                    $descriptionParts[] = 'EN: ' . $allDescriptions['en'];
                                }
                                if (!empty($allDescriptions['nl'])) {
                                    $descriptionParts[] = 'NL: ' . $allDescriptions['nl'];
                                }
                                if (!empty($allDescriptions['fr'])) {
                                    $descriptionParts[] = 'FR: ' . $allDescriptions['fr'];
                                }
                                $descriptionDisplay = !empty($descriptionParts) ? implode(' | ', $descriptionParts) : '';
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $reason->code }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $reason->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $descriptionDisplay }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="edit({{ $reason->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                    <button wire:click="delete({{ $reason->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    No reasons found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> 