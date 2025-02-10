<div>
    <form wire:submit.prevent="save" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Announcement Type</label>
            <select wire:model.live="selectedAnnouncement"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
                <option value="">Select announcement type</option>
                @foreach(array_keys($templates) as $type)
                <option value="{{ $type }}">{{ $type }}</option>
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
</div>
