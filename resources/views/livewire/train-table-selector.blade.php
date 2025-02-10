<div>
    <x-modal name="train-table-selector" :show="false" maxWidth="2xl">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                Select Train Routes for Table
            </h2>
            
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($routes as $route)
                        <div class="flex items-center space-x-3 p-4 border rounded-lg {{ in_array($route->route_id, $selectedRoutes) ? 'border-neutral-500 bg-neutral-50' : 'border-gray-200' }}">
                            <input type="checkbox" 
                                wire:click="toggleRoute('{{ $route->route_id }}')"
                                {{ in_array($route->route_id, $selectedRoutes) ? 'checked' : '' }}
                                class="h-4 w-4 text-neutral-600 focus:ring-neutral-500 border-gray-300 rounded">
                            <label class="flex-1">
                                <span class="block font-medium text-gray-900">{{ $route->route_long_name }}</span>
                                <span class="block text-sm text-gray-500">{{ $route->route_short_name }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Close
                </x-secondary-button>
            </div>
        </div>
    </x-modal>
</div> 