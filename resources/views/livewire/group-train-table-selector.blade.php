<div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($routes as $route)
            <div class="flex items-center p-4 bg-white rounded-lg shadow-sm border border-gray-200">
                <input 
                    type="checkbox" 
                    id="train_table_route_{{ $route->route_id }}"
                    wire:model="selectedRoutes"
                    value="{{ $route->route_id }}"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <label for="train_table_route_{{ $route->route_id }}" class="ml-3 block text-sm font-medium text-gray-700">
                    {{ $route->route_short_name }} - {{ $route->route_long_name }}
                </label>
            </div>
        @endforeach
    </div>

    @if(count($selectedRoutes) > 0)
        <div class="mt-4 text-sm text-gray-600">
            {{ count($selectedRoutes) }} route(s) selected for train table
        </div>
    @else
        <div class="mt-4 text-sm text-gray-600">
            No routes selected for train table
        </div>
    @endif
</div> 