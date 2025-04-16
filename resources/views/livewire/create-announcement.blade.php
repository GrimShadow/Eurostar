<div>
    <form wire:submit.prevent="save" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Announcement Type</label>
            <select wire:model.live="selectedAnnouncement"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
                <option value="">Select announcement type</option>
                @foreach($templates as $type => $template)
                    <option value="{{ $type }}">{{ $template['friendly_name'] }}</option>
                @endforeach
            </select>
        </div>

        @if($selectedAnnouncement && !empty($variables))
        @foreach($variables as $id => $type)
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ $id }}</label>
            @switch($type)
            @case('zone')
            <select wire:model="selectedZone"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
                <option value="">Select Zone</option>
                @foreach($zones as $zone)
                <option value="{{ $zone->value }}">{{ $zone->value }}</option>
                @endforeach
            </select>
            @break
            @case('train')
            <select wire:model="selectedTrain"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
                <option value="">Select Train</option>
                @foreach($trains as $train)
                <option value="{{ $train['number'] }}">Train {{ $train['number'] }}</option>
                @endforeach
            </select>
            @break
            @case('datetime')
            <input type="datetime-local" wire:model="scheduledTime"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
            @break
            @case('route')
            <select wire:model="selectedRoute"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
                <option value="GBR_LON">London</option>
                <option value="FRA_PAR">Paris</option>
                <option value="BEL_BRU">Brussels</option>
                <option value="NLD_AMS">Amsterdam</option>
            </select>
            @break
            @case('reason')
            <select wire:model="selectedReason"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
                <option value="">Select Reason</option>
                @foreach($reasons as $reason)
                <option value="{{ $reason->code }}">{{ $reason->name }}</option>
                @endforeach
            </select>
            @break
            
            @case('text')
            <input type="text" wire:model="textInput"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500"
                placeholder="Enter text">
            @break
            @endswitch
        </div>
        @endforeach
        @endif

        <div class="flex justify-end space-x-2">
            <button type="submit"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-neutral-600 hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                Send Announcement
            </button>
        </div>
    </form>

    <!-- Spinner Modal Overlay -->
    <div wire:loading wire:target="save" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white p-8 rounded-lg shadow-xl text-center">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-neutral-600 border-t-transparent"></div>
            <p class="mt-4 text-lg font-medium text-neutral-600">Announcement in progress...</p>
        </div>
    </div>
</div>
