<form wire:submit.prevent="save" class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">Type</label>
        <select wire:model.live="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
            <option value="">Select Type</option>
            <option value="audio">Audio</option>
            <option value="text">Text</option>
        </select>
        @error('type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    @if($type === 'audio')
        <div>
            <label class="block text-sm font-medium text-gray-700">Select Audio Announcement</label>
            <select wire:model="selectedAnnouncement" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
                <option value="">Select an announcement</option>
                @foreach($audioAnnouncements as $announcement)
                    <option value="{{ $announcement->id }}">
                        {{ $announcement->name }} ({{ $announcement->item_id }})
                    </option>
                @endforeach
            </select>
            @error('selectedAnnouncement') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
    @endif

    @if($type === 'text')
        <div>
            <label class="block text-sm font-medium text-gray-700">Message</label>
            <textarea wire:model="message" rows="3" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500"
                placeholder="Enter your announcement message"></textarea>
            @error('message') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
    @endif

    <div>
        <label class="block text-sm font-medium text-gray-700">Scheduled Time</label>
        <input type="time" wire:model="scheduled_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
        @error('scheduled_time') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Recurrence (optional)</label>
        <input type="text" wire:model="recurrence" placeholder="e.g., 2x 5 mins" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
        @error('recurrence') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Author</label>
        <input 
            wire:model="author" 
            type="text" 
            readonly 
            disabled
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500 bg-gray-100"
        >
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Zone</label>
        <select wire:model="area" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
            <option value="">Select Zone</option>
            @foreach($zones as $zone)
                <option value="{{ $zone->value }}">{{ $zone->value }}</option>
            @endforeach
        </select>
        @error('area') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    @if($type === 'audio')
        <div>
            <label class="block text-sm font-medium text-gray-700">Select Train</label>
            <select wire:model="selectedTrain" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-neutral-500 focus:ring-neutral-500">
                <option value="">Select a train</option>
                @foreach($trains as $train)
                    <option value="{{ $train['number'] }}">
                        Train {{ $train['number'] }} (Departure: {{ substr($train['departure_time'], 0, 5) }})
                    </option>
                @endforeach
            </select>
            @error('selectedTrain') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
    @endif

    <div class="flex justify-end space-x-2">
        <button type="button" @click="modalOpen = false" 
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
            Cancel
        </button>
        <button type="submit" 
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-neutral-600 hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
            Create Announcement
        </button>
    </div>
</form>
