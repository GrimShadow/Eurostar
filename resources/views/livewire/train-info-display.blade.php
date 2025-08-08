<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Train Information Display</h2>
        
        <!-- Search and Filter Controls -->
        <div class="flex flex-col sm:flex-row gap-4 mb-4">
            <div class="flex-1">
                <input type="text" 
                       wire:model.live="search" 
                       placeholder="Search trains, routes, or destinations..." 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
            </div>
            
            <div class="flex items-center space-x-4">
                <button wire:click="toggleDateFilter"
                        class="px-4 py-2 text-sm font-medium rounded-md {{ $showDateFilter ? 'bg-neutral-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ $showDateFilter ? 'Hide Date Filter' : 'Show Date Filter' }}
                </button>
                
                @if($showDateFilter)
                    <input type="date" 
                           wire:model.live="selectedDate" 
                           class="rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
                @endif
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-sm font-medium text-blue-600">Total Trains</div>
                <div class="text-2xl font-bold text-blue-900">{{ count($trains) }}</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-sm font-medium text-green-600">Unique Routes</div>
                <div class="text-2xl font-bold text-green-900">{{ $trains->pluck('route_id')->unique()->count() }}</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-sm font-medium text-purple-600">Unique Train Numbers</div>
                <div class="text-2xl font-bold text-purple-900">{{ $trains->pluck('train_number')->unique()->count() }}</div>
            </div>
            <div class="bg-orange-50 p-4 rounded-lg">
                <div class="text-sm font-medium text-orange-600">Date Range</div>
                <div class="text-2xl font-bold text-orange-900">{{ $trains->pluck('human_readable_date')->unique()->count() }}</div>
            </div>
        </div>
    </div>

    <!-- Trains Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Train Info
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Route
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Schedule
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Trip Details
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Service Details
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($trains as $train)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-4 h-4 rounded-full" style="background-color: #{{ $train['route_color'] ?? '9CA3AF' }}"></div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        Train {{ $train['train_number'] }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $train['trip_headsign'] }}
                                    </div>
                                    @if($train['human_readable_date'])
                                        <div class="text-xs text-blue-600 font-medium">
                                            {{ $train['human_readable_date'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $train['route_long_name'] }}</div>
                            <div class="text-sm text-gray-500">Route ID: {{ $train['route_id'] }}</div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <span class="font-medium">{{ substr($train['departure_time'], 0, 5) }}</span>
                                <span class="text-gray-400">→</span>
                                <span class="font-medium">{{ substr($train['arrival_time'], 0, 5) }}</span>
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $train['trip_short_name'] }}
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <div class="font-mono text-xs bg-gray-100 p-1 rounded">
                                    {{ $train['trip_id'] }}
                                </div>
                            </div>
                            @if($train['parsed_trip_id'])
                                <div class="text-xs text-gray-500 mt-1">
                                    <span class="font-medium">Train:</span> {{ $train['parsed_trip_id']['train_number'] }} 
                                    <span class="font-medium">Date:</span> {{ $train['parsed_trip_id']['date'] }}
                                </div>
                            @endif
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <div class="font-mono text-xs bg-gray-100 p-1 rounded">
                                    {{ $train['service_id'] }}
                                </div>
                            </div>
                            @if($train['parsed_service_id'])
                                <div class="text-xs text-gray-500 mt-1">
                                    <span class="font-medium">Train:</span> {{ $train['parsed_service_id']['train_number'] }} 
                                    <span class="font-medium">Date:</span> {{ $train['parsed_service_id']['date'] }}
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            @if($search)
                                No trains found matching "{{ $search }}"
                            @else
                                No trains available for the selected routes and date
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Data Format Explanation -->
    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
        <h3 class="text-sm font-medium text-blue-900 mb-2">Data Format Explanation</h3>
        <div class="text-sm text-blue-700 space-y-1">
            <p><strong>Trip ID Format:</strong> "9002-0809" = Train 9002 on August 9th</p>
            <p><strong>Service ID Format:</strong> "9002-0809" = Service for Train 9002 on August 9th</p>
            <p><strong>Date Format:</strong> MM-DD (e.g., "08-09" = August 9th)</p>
        </div>
    </div>
</div> 