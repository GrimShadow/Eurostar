<div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-200">
    <!-- Create Rule Form -->
    <div class="p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Create Automated Announcement Rule</h3>
        
        @if (session()->has('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                {{ session('error') }}
            </div>
        @endif

        @if (session()->has('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit="save" class="space-y-6">
            <!-- Rule Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Rule Name</label>
                <input type="text" 
                    wire:model="name" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500"
                    placeholder="e.g., Terminal Safety Announcement">
                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Time Range -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Start Time</label>
                    <input type="time" 
                        wire:model="startTime" 
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                    @error('startTime') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">End Time</label>
                    <input type="time" 
                        wire:model="endTime" 
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                    @error('endTime') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Interval (minutes)</label>
                    <input type="number" 
                        wire:model="intervalMinutes" 
                        min="1" 
                        max="1440"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500"
                        placeholder="40">
                    @error('intervalMinutes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Days of Week -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Active Days</label>
                <div class="grid grid-cols-4 md:grid-cols-7 gap-2">
                    @foreach($dayOptions as $value => $label)
                        <label class="flex items-center">
                            <input type="checkbox" 
                                wire:model="daysOfWeek" 
                                value="{{ $value }}"
                                class="rounded border-gray-300 text-neutral-600 shadow-sm focus:ring-neutral-500">
                            <span class="ml-2 text-sm text-gray-900">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('daysOfWeek') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Announcement Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    <label class="block text-sm font-medium text-gray-700">Zone</label>
                    <select wire:model="zone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500">
                        <option value="">Select Zone</option>
                        @foreach($zones as $zoneOption)
                            <option value="{{ $zoneOption->value }}">{{ $zoneOption->value }}</option>
                        @endforeach
                    </select>
                    @error('zone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Template Variables -->
            @if($selectedTemplate && count($templateVariables) > 0)
                <div class="border-t pt-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Template Variables</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($templateVariables as $variable => $value)
<div>
                                <label class="block text-sm font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $variable)) }}</label>
                                <input type="text" 
                                    wire:model="templateVariables.{{ $variable }}" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-neutral-500 focus:border-neutral-500"
                                    placeholder="Enter {{ str_replace('_', ' ', $variable) }}">
                                @error("templateVariables.$variable") 
                                    <span class="text-red-500 text-sm">{{ $message }}</span> 
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Active Toggle -->
            <div class="flex items-center">
                <input type="checkbox" 
                    wire:model="isActive" 
                    class="rounded border-gray-300 text-neutral-600 shadow-sm focus:ring-neutral-500">
                <label class="ml-2 text-sm text-gray-900">Rule is active</label>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-neutral-600 hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                    Create Rule
                </button>
            </div>
        </form>
    </div>

    <!-- Existing Rules Table -->
    <div class="p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Existing Automated Announcement Rules</h3>
        
        @if($rules->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rule Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Template</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Zone</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Triggered</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($rules as $rule)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $rule->name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="space-y-1">
                                        <div>{{ \Carbon\Carbon::parse($rule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($rule->end_time)->format('H:i') }}</div>
                                        <div class="text-xs text-gray-500">Every {{ $rule->interval_minutes }} minutes</div>
                                        <div class="text-xs text-gray-500">{{ $rule->days_of_week_text }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $rule->aviavoxTemplate->friendly_name ?? 'Template deleted' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $rule->zone }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $rule->last_triggered_at ? $rule->last_triggered_at->format('M j, H:i') : 'Never' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $rule->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button wire:click="toggleRule({{ $rule->id }})" 
                                        class="text-neutral-600 hover:text-neutral-900">
                                        {{ $rule->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                    <button wire:click="deleteRule({{ $rule->id }})" 
                                        onclick="return confirm('Are you sure you want to delete this rule?')"
                                        class="text-red-600 hover:text-red-900">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $rules->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2M7 4h10l2 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6l2-2zM9 14l2 2 4-4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No automated announcement rules</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first automated announcement rule.</p>
            </div>
        @endif
    </div>
</div>
