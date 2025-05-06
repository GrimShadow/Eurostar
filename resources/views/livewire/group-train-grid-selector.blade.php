<div>
    <div class="mb-4">
        <input type="text" 
               wire:model.live="search" 
               placeholder="Search routes..." 
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
    </div>

    <div class="space-y-2 max-h-96 overflow-y-auto">
        @foreach($routes as $route)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="flex items-center justify-between p-2">
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full" style="background-color: #{{ $route->route_color ?? '9CA3AF' }}"></div>
                        <span class="text-sm font-medium text-gray-900">{{ $route->route_long_name }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button wire:click="toggleRoute('{{ $route->route_id }}')"
                                class="px-3 py-1 text-sm font-medium rounded-md {{ in_array($route->route_id, $selectedRoutes) ? 'bg-neutral-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ in_array($route->route_id, $selectedRoutes) ? 'Selected' : 'Select' }}
                        </button>
                        @if(in_array($route->route_id, $selectedRoutes))
                            <button wire:click="toggleRouteExpansion('{{ $route->route_id }}')"
                                    class="p-1 text-gray-500 hover:text-gray-700">
                                <svg class="w-5 h-5 transform transition-transform duration-200" 
                                     :class="{ 'rotate-180': $wire.expandedRoute === '{{ $route->route_id }}' }" 
                                     fill="none" 
                                     stroke="currentColor" 
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" 
                                          stroke-linejoin="round" 
                                          stroke-width="2" 
                                          d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>

                @if(in_array($route->route_id, $selectedRoutes) && $expandedRoute === $route->route_id)
                    <div class="border-t border-gray-200 p-2">
                        <div class="space-y-2">
                            @foreach($this->getStationsForRoute($route->route_id) as $station)
                                <div class="flex items-center justify-between pl-8">
                                    <span class="text-sm text-gray-700">{{ $station->stop_name }}</span>
                                    <button wire:click="toggleStation('{{ $route->route_id }}', '{{ $station->stop_id }}')"
                                            class="px-3 py-1 text-sm font-medium rounded-md {{ isset($selectedStations[$route->route_id]) && in_array($station->stop_id, $selectedStations[$route->route_id]) ? 'bg-neutral-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                        {{ isset($selectedStations[$route->route_id]) && in_array($station->stop_id, $selectedStations[$route->route_id]) ? 'Selected' : 'Select' }}
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div> 