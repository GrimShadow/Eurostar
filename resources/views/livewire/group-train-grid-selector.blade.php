<div>
    <div class="mb-4">
        <input type="text" 
               wire:model.live="search" 
               placeholder="Search routes..." 
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
    </div>

    <div class="space-y-2 max-h-96 overflow-y-auto">
        @foreach($routes as $route)
            <div class="flex items-center justify-between p-2 bg-white rounded-lg shadow-sm">
                <div class="flex items-center space-x-3">
                    <div class="w-4 h-4 rounded-full" style="background-color: #{{ $route->route_color ?? '9CA3AF' }}"></div>
                    <span class="text-sm font-medium text-gray-900">{{ $route->route_long_name }}</span>
                </div>
                <button wire:click="toggleRoute('{{ $route->route_id }}')"
                        class="px-3 py-1 text-sm font-medium rounded-md {{ in_array($route->route_id, $selectedRoutes) ? 'bg-neutral-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ in_array($route->route_id, $selectedRoutes) ? 'Selected' : 'Select' }}
                </button>
            </div>
        @endforeach
    </div>
</div> 