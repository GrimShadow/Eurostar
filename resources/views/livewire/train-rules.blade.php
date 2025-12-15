<div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-200">
    <!-- Create/Edit Rule Form -->
    <div class="p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
            @if($editingRuleId)
                Edit Rule
            @else
                Create New Rule
            @endif
        </h3>
        @if (session()->has('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                {{ session('error') }}
            </div>
        @endif
        @if (session()->has('message'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                {{ session('message') }}
            </div>
        @endif
        <form wire:submit="save" class="space-y-4">
            <div class="mt-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Conditions</h3>
                    <button type="button" wire:click="addCondition" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-neutral-600 hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                        Add Condition
                    </button>
                </div>
                <div class="space-y-4" wire:key="conditions-container-{{ $tableKey }}">
                    @foreach($conditions as $index => $condition)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end" wire:key="condition-{{ $index }}-{{ $tableKey }}">
                            @if($index > 0)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Logical Operator</label>
                                    <select wire:model="conditions.{{ $index }}.logical_operator" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                                        @foreach($logicalOperators as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Condition Type</label>
                                <select wire:model.live="conditions.{{ $index }}.condition_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                                    <option value="">Select Type</option>
                                    <optgroup label="Time-Based">
                                        <option value="time_until_departure">Time Until Departure</option>
                                        <option value="time_after_departure">Time After Departure</option>
                                        <option value="time_until_arrival">Time Until Arrival</option>
                                        <option value="time_after_arrival">Time After Arrival</option>
                                        <option value="minutes_until_check_in_starts">Minutes Until Check-in Starts</option>
                                        <option value="time_of_day">Time Equals</option>
                                        <option value="time_range">Time Range</option>
                                        <option value="day_of_week">Day of Week</option>
                                        <option value="is_peak_time">Is Peak Time</option>
                                    </optgroup>
                                    <optgroup label="Realtime Data">
                                        <option value="delay_minutes">Delay (Minutes)</option>
                                        <option value="delay_percentage">Delay (Percentage)</option>
                                        <option value="platform_changed">Platform Changed</option>
                                        <option value="is_cancelled">Is Cancelled</option>
                                        <option value="has_realtime_update">Has Realtime Update</option>
                                    </optgroup>
                                    <optgroup label="Route/Service">
                                        <option value="route_id">Route ID</option>
                                        <option value="direction_id">Direction</option>
                                        <option value="destination_station">Destination Station</option>
                                        <option value="wheelchair_accessible">Wheelchair Accessible</option>
                                    </optgroup>
                                    <optgroup label="General">
                                        <option value="current_status">Current Status</option>
                                        <option value="check_in_status">Check-in Status</option>
                                        <option value="train_number">Train Number</option>
                                    </optgroup>
                                </select>
                                @error("conditions.{$index}.condition_type") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Operator</label>
                                <select wire:model.live="conditions.{{ $index }}.operator" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                                    <option value="">Select Operator</option>
                                    <option value=">">Greater Than</option>
                                    <option value=">=">Greater Than or Equal To</option>
                                    <option value="<">Less Than</option>
                                    <option value="<=">Less Than or Equal To</option>
                                    <option value="=">Equals</option>
                                    <option value="!=">Not Equal To</option>
                                </select>
                                @error("conditions.{$index}.operator") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                
                               
                            </div>

                            <div wire:key="value-field-{{ $index }}-{{ $condition['condition_type'] }}">
                                <label class="block text-sm font-medium text-gray-700">Value</label>
                                @if($condition['condition_type'] === 'current_status')
                                    <select wire:model.live="conditions.{{ $index }}.value" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                                        <option value="">Select Status</option>
                                        @foreach($statuses as $status)
                                            <option value="{{ $status->id }}">{{ $status->status }}</option>
                                        @endforeach
                                    </select>
                                @elseif($condition['condition_type'] === 'check_in_status')
                                    <select wire:model.live="conditions.{{ $index }}.value" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                                        <option value="">Select Check-in Status</option>
                                        @foreach($checkInStatuses as $checkInStatus)
                                            <option value="{{ $checkInStatus->id }}">{{ $checkInStatus->status }}</option>
                                        @endforeach
                                    </select>
                                @elseif(in_array($condition['condition_type'], ['time_until_departure', 'time_after_departure', 'time_until_arrival', 'time_after_arrival', 'minutes_until_check_in_starts']))
                                    <div class="flex space-x-2">
                                        <div class="flex-1">
                                            <input type="number" 
                                                wire:model.live="conditions.{{ $index }}.value" 
                                                min="0" 
                                                step="1"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500"
                                                placeholder="Enter minutes">
                                            <p class="text-xs text-gray-500 mt-1">Value is in minutes</p>
                                        </div>
                                    </div>
                                @elseif($condition['condition_type'] === 'time_of_day')
                                    <input type="time" 
                                        wire:model.live="conditions.{{ $index }}.value" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500"
                                        placeholder="HH:MM">
                                    <p class="text-xs text-gray-500 mt-1">Enter time in 24-hour format (HH:MM)</p>
                                @elseif($condition['condition_type'] === 'day_of_week')
                                    <div class="mt-1 space-y-2">
                                        @php
                                            $dayOptions = [
                                                '1' => 'Monday',
                                                '2' => 'Tuesday',
                                                '3' => 'Wednesday',
                                                '4' => 'Thursday',
                                                '5' => 'Friday',
                                                '6' => 'Saturday',
                                                '0' => 'Sunday',
                                            ];
                                            // Ensure value is an array for checkboxes
                                            $currentValue = $condition['value'] ?? [];
                                            if (!is_array($currentValue) && !empty($currentValue)) {
                                                $currentValue = explode(',', $currentValue);
                                            } elseif (!is_array($currentValue)) {
                                                $currentValue = [];
                                            }
                                        @endphp
                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach($dayOptions as $dayValue => $dayLabel)
                                                <label class="inline-flex items-center">
                                                    <input 
                                                        type="checkbox" 
                                                        wire:click="toggleDayOfWeek({{ $index }}, '{{ $dayValue }}')"
                                                        @if(in_array($dayValue, $currentValue)) checked @endif
                                                        class="rounded border-gray-300 text-neutral-600 shadow-sm focus:border-neutral-300 focus:ring focus:ring-neutral-200 focus:ring-opacity-50"
                                                    >
                                                    <span class="ml-2 text-sm text-gray-700">{{ $dayLabel }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <input type="text" 
                                        wire:model.live="conditions.{{ $index }}.value" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500"
                                        placeholder="{{ $condition['condition_type'] === 'platform_change' ? 'Enter platform number' : 'Enter value' }}">
                                @endif
                                @error("conditions.{$index}.value") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            @if($index > 0)
                                <button type="button" wire:click="removeCondition({{ $index }})" class="mb-1 text-red-600 hover:text-red-900">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Rule Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Priority</label>
                        <input type="number" wire:model="priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500" placeholder="0 (higher = executes first)">
                        @error('priority') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Execution Mode</label>
                        <select wire:model="executionMode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                            <option value="first_match">First Match</option>
                            <option value="all_matches">All Matches</option>
                            <option value="highest_priority">Highest Priority</option>
                        </select>
                        @error('executionMode') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Active</label>
                        <div class="mt-1">
                            <input type="checkbox" wire:model="isActive" class="rounded border-gray-300 text-neutral-600 shadow-sm focus:border-neutral-300 focus:ring focus:ring-neutral-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Enable this rule</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Then</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Action Type</label>
                        <select wire:model.live="action" wire:key="action-select-{{ $tableKey }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                            <option value="">Select Action</option>
                            <option value="set_status">Set Status</option>
                            <option value="set_check_in_status">Set Check-in Status</option>
                            <option value="make_announcement">Make Announcement</option>
                            <option value="update_platform">Update Platform</option>
                        </select>
                        @error('action') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    @if($action === 'set_status')
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select wire:model.live="actionValue" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                                <option value="">Select Status</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->id }}">{{ $status->status }}</option>
                                @endforeach
                            </select>
                            @error('actionValue') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    @elseif($action === 'set_check_in_status')
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Check-in Status</label>
                            <select wire:model.live="actionValue" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                                <option value="">Select Check-in Status</option>
                                @foreach($checkInStatuses as $checkInStatus)
                                    <option value="{{ $checkInStatus->id }}">{{ $checkInStatus->status }}</option>
                                @endforeach
                            </select>
                            @error('actionValue') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    @elseif($action === 'make_announcement')
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Announcement Template</label>
                                <select wire:model.live="selectedTemplate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                                    <option value="">Select Template</option>
                                    @foreach($availableTemplates as $template)
                                        <option value="{{ $template->id }}">{{ $template->friendly_name }}</option>
                                    @endforeach
                                </select>
                                @error('selectedTemplate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Announcement Zone</label>
                                
                                <!-- Zone Selection Strategy -->
                                <div class="mb-3">
                                    <div class="flex space-x-4">
                                        <label class="inline-flex items-center">
                                            <input 
                                                type="radio" 
                                                name="zoneSelectionStrategy"
                                                wire:model.live="zoneSelectionStrategy" 
                                                value="group_zones" 
                                                class="form-radio text-neutral-600 focus:ring-neutral-500"
                                            />
                                            <span class="ml-2 text-sm text-gray-700">Use Group Zones (Dynamic)</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input 
                                                type="radio" 
                                                name="zoneSelectionStrategy"
                                                wire:model.live="zoneSelectionStrategy" 
                                                value="specific_zone" 
                                                class="form-radio text-neutral-600 focus:ring-neutral-500"
                                            />
                                            <span class="ml-2 text-sm text-gray-700">Use Specific Zone</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Zone Selection (only shown for specific zone strategy) -->
                                @if($zoneSelectionStrategy === 'specific_zone')
                                    <select wire:model="announcementZone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                                        <option value="">Select Zone</option>
                                        @foreach($zones as $zone)
                                            <option value="{{ $zone->value }}">{{ $zone->value }}</option>
                                        @endforeach
                                    </select>
                                    @error('announcementZone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                @else
                                    <div class="mt-1 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-sm text-blue-700">
                                                Announcements will automatically play in the zones associated with the train's group
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                        
                            @if($selectedTemplate && !empty($templateVariables))
                                <div class="border-t pt-4" wire:key="template-variables-{{ $selectedTemplate }}-{{ count($templateVariables) }}">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">
                                        Template Variables 
                                        <span class="text-xs text-gray-500">(Template: {{ $selectedTemplate }}, Variables: {{ count($templateVariables) }})</span>
                                    </h4>
                                    @foreach($templateVariables as $variable => $value)
                                        <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                {{ ucfirst(str_replace('_', ' ', $variable)) }}
                                            </label>
                                            
                                                                        <!-- Variable Type Selection -->
                            <div class="mb-3">
                                <div class="flex space-x-4">
                                    <label class="inline-flex items-center">
                                        <input 
                                            type="radio" 
                                            name="variableType_{{ $variable }}"
                                            wire:model.live="variableTypes.{{ $variable }}" 
                                            value="manual" 
                                            class="form-radio text-neutral-600 focus:ring-neutral-500"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Manual Input</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input 
                                            type="radio" 
                                            name="variableType_{{ $variable }}"
                                            wire:model.live="variableTypes.{{ $variable }}" 
                                            value="dynamic" 
                                            class="form-radio text-neutral-600 focus:ring-neutral-500"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">From Train Data</span>
                                    </label>
                                </div>
                            </div>
                                            
                                            <!-- Input Field (only shown for manual) -->
                                            @if(($variableTypes[$variable] ?? 'manual') === 'manual')
                                                <input type="text" 
                                                    wire:model="templateVariables.{{ $variable }}" 
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500"
                                                    placeholder="Enter {{ str_replace('_', ' ', $variable) }}">
                                                @error("templateVariables.$variable") 
                                                    <span class="text-red-500 text-sm">{{ $message }}</span> 
                                                @enderror
                                            @else
                                                <div class="mt-1 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        <span class="text-sm text-blue-700">
                                                            This will be automatically populated with {{ str_replace('_', ' ', $variable) }} from the train that triggers the rule
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @elseif($action === 'update_platform')
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Platform Number</label>
                            <input type="text" wire:model="actionValue" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500" placeholder="Enter platform number (e.g., 1, 2A, 3B)">
                            @error('actionValue') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                @if($editingRuleId)
                    <button type="button" wire:click="cancelEdit" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                        Cancel
                    </button>
                @else
                    <button type="button" wire:click="resetForm" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Reset Form
                    </button>
                @endif
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-neutral-600 hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                    @if($editingRuleId)
                        Update Rule
                    @else
                        Create Rule
                    @endif
                </button>
            </div>
        </form>
    </div>

    <!-- Rules Table -->
    <div class="p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Existing Rules</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" wire:key="rules-table-{{ $tableKey }}">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($rules as $rule)
                        <tr class="{{ $editingRuleId === $rule->id ? 'bg-blue-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @foreach($rule->conditions as $index => $condition)
                                    @if($index > 0)
                                        <span class="text-gray-500">{{ strtoupper($condition->logical_operator) }}</span>
                                    @endif
                                    When {{ str_replace('_', ' ', $condition->condition_type) }} 
                                    {{ $condition->operator }} 
                                    @if($condition->condition_type === 'current_status')
                                        @php
                                            $status = \App\Models\Status::find($condition->value);
                                        @endphp
                                        {{ $status ? $status->status : 'Unknown Status' }}
                                    @elseif($condition->condition_type === 'check_in_status')
                                        @php
                                            $checkInStatus = \App\Models\CheckInStatus::find($condition->value);
                                        @endphp
                                        {{ $checkInStatus ? $checkInStatus->status : 'Unknown Check-in Status' }}
                                    @elseif($condition->condition_type === 'train_number')
                                        {{ $condition->value }}
                                    @elseif($condition->condition_type === 'day_of_week')
                                        @php
                                            $dayNames = ['0' => 'Sunday', '1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday'];
                                            // Handle both single values and comma-separated values (for backward compatibility)
                                            $days = !empty($condition->value) ? explode(',', $condition->value) : [];
                                            $dayLabels = array_map(function($day) use ($dayNames) {
                                                return $dayNames[trim($day)] ?? trim($day);
                                            }, $days);
                                        @endphp
                                        {{ !empty($dayLabels) ? implode(', ', $dayLabels) : 'No day selected' }}
                                    @elseif($condition->condition_type === 'time_of_day')
                                        {{ $condition->value }}
                                    @else
                                        {{ $condition->value }} minutes
                                    @endif
                                    <br>
                                @endforeach
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($rule->action === 'set_status')
                                    @php
                                        $status = \App\Models\Status::find($rule->action_value);
                                    @endphp
                                    Set status to {{ $status ? $status->status : 'Unknown Status' }}
                                @elseif($rule->action === 'set_check_in_status')
                                    @php
                                        $checkInStatus = \App\Models\CheckInStatus::find($rule->action_value);
                                    @endphp
                                    Set check-in status to {{ $checkInStatus ? $checkInStatus->status : 'Unknown Check-in Status' }}
                                @elseif($rule->action === 'make_announcement')
                                    @php
                                        $announcementData = json_decode($rule->action_value, true);
                                        $template = \App\Models\AviavoxTemplate::find($announcementData['template_id']);
                                        $zoneStrategy = $announcementData['zone_strategy'] ?? 'specific_zone';
                                        $zone = $announcementData['zone'] ?? 'unknown';
                                        $variables = $announcementData['variables'] ?? [];
                                        $variableTypes = $announcementData['variable_types'] ?? [];
                                    @endphp
                                    Make announcement: {{ $template->friendly_name ?? $template->name }} 
                                    <span class="text-gray-500">
                                        @if($zoneStrategy === 'group_zones')
                                            (Dynamic: Group Zones)
                                        @else
                                            ({{ $zone }})
                                        @endif
                                    </span>
                                    @if(count($variables) > 0)
                                        <div class="text-xs text-gray-500 mt-1">
                                            Variables: 
                                            @foreach($variables as $key => $value)
                                                @php
                                                    $variableType = $variableTypes[$key] ?? 'manual';
                                                    $displayValue = $variableType === 'dynamic' ? '(from train data)' : $value;
                                                @endphp
                                                <span class="inline-flex items-center">
                                                    {{ $key }}: {{ $displayValue }}
                                                    @if($variableType === 'dynamic')
                                                        <svg class="w-3 h-3 ml-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                        </svg>
                                                    @endif
                                                </span>{{ !$loop->last ? ', ' : '' }}
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button wire:click="toggleRule({{ $rule->id }})" 
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $rule->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex space-x-3">
                                    <button wire:click="editRule({{ $rule->id }})" 
                                        class="text-blue-600 hover:text-blue-900">
                                        Edit
                                    </button>
                                    <button wire:click="deleteRule({{ $rule->id }})" 
                                        wire:confirm="Are you sure you want to delete this rule?"
                                        class="text-red-600 hover:text-red-900">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $rules->links() }}
        </div>
    </div>
</div>
