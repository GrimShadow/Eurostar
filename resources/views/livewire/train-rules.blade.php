<div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-200">
    <!-- Create Rule Form -->
    <div class="p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Create New Rule</h3>
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Condition Type</label>
                    <select wire:model.live="conditionType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                        <option value="">Select Type</option>
                        <option value="time_until_departure">Time Until Departure</option>
                        <option value="time_since_arrival">Time Since Arrival</option>
                        <option value="platform_change">Platform Change</option>
                        <option value="delay_duration">Delay Duration</option>
                        <option value="current_status">Current Status</option>
                        <option value="time_of_day">Time of Day</option>
                    </select>
                    @error('conditionType') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Operator</label>
                    <select wire:model.live="operator" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                        <option value="">Select Operator</option>
                        <option value=">">Greater Than</option>
                        <option value="<">Less Than</option>
                        <option value="=">Equals</option>
                    </select>
                    @error('operator') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ $valueField['label'] }}</label>
                    @if($valueField['type'] === 'select')
                        <select wire:model.live="value" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                            <option value="">Select {{ $valueField['label'] }}</option>
                            @foreach($valueField['options'] as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    @elseif($valueField['type'] === 'time')
                        <input type="time" wire:model.live="value" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                    @else
                        <input type="{{ $valueField['type'] }}" wire:model.live="value" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                    @endif
                    @error('value') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Action</label>
                    <select wire:model.live="action" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                        <option value="">Select Action</option>
                        <option value="set_status">Set Status</option>
                        <option value="make_announcement">Make Announcement</option>
                    </select>
                    @error('action') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    @if($action === 'set_status')
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select wire:model.live="actionValue" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                            <option value="">Select Status</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status->id }}">{{ $status->status }}</option>
                            @endforeach
                        </select>
                        @error('actionValue') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    @elseif($action === 'make_announcement')
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Announcement Template</label>
                                <select wire:model.live="selectedTemplate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                                    <option value="">Select Template</option>
                                    @foreach($availableTemplates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                                @error('selectedTemplate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Announcement Zone</label>
                                <select wire:model.live="announcementZone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                                    <option value="">Select Zone</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->value }}">{{ $zone->value }}</option>
                                    @endforeach
                                </select>
                                @error('announcementZone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            @if($selectedTemplate && count($templateVariables) > 0)
                                <div class="border-t pt-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Template Variables</h4>
                                    @foreach($templateVariables as $variable => $value)
                                        <div class="mb-2">
                                            <label class="block text-sm font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $variable)) }}</label>
                                            <input type="text" 
                                                wire:model.live="templateVariables.{{ $variable }}" 
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500"
                                                placeholder="Enter {{ str_replace('_', ' ', $variable) }}">
                                            @error("templateVariables.$variable") 
                                                <span class="text-red-500 text-sm">{{ $message }}</span> 
                                            @enderror
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <label class="block text-sm font-medium text-gray-700">Action Value</label>
                        <select disabled class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500 bg-gray-100">
                            <option>Select an action first</option>
                        </select>
                    @endif
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-neutral-600 hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                    Create Rule
                </button>
            </div>
        </form>
    </div>

    <!-- Rules Table -->
    <div class="p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Existing Rules</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
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
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                When {{ str_replace('_', ' ', $rule->condition_type) }} {{ $rule->operator }} {{ $rule->value }} minutes
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($rule->action === 'set_status')
                                    {{ str_replace('_', ' ', $rule->action) }} to {{ $rule->status->status }}
                                @else
                                    @php
                                        $announcementData = json_decode($rule->action_value, true);
                                        $template = \App\Models\AviavoxTemplate::find($announcementData['template_id']);
                                        $zone = $announcementData['variables']['zone'] ?? 'unknown';
                                    @endphp
                                    Make announcement: {{ $template->name }} 
                                    <span class="text-gray-500">({{ $zone }})</span>
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
                                <button wire:click="deleteRule({{ $rule->id }})" 
                                    wire:confirm="Are you sure you want to delete this rule?"
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
            {{ $rules->links() }}
        </div>
    </div>
</div>
