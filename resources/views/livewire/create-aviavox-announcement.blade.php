<div>
    <form wire:submit="save" class="space-y-4">
        <div>
            <label for="selectedMessage" class="block text-sm font-medium text-gray-700">Message Type</label>
            <select wire:model.live="selectedMessage" id="selectedMessage" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Select a message...</option>
                @foreach(array_keys($predefinedMessages) as $message)
                    <option value="{{ $message }}">{{ $message }}</option>
                @endforeach
            </select>
        </div>

        @if($selectedMessage && !empty($variables))
            @foreach($variables as $param => $value)
                <div>
                    <label for="{{ $param }}" class="block text-sm font-medium text-gray-700">{{ $param }}</label>
                    <input type="text" wire:model="variables.{{ $param }}" id="{{ $param }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            @endforeach
        @endif

        <div>
            <label for="zones" class="block text-sm font-medium text-gray-700">Zones</label>
            <select wire:model="zones" id="zones" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="Terminal">Terminal</option>
                <option value="Terminal,Lounge">Terminal & Lounge</option>
                <option value="Lounge">Lounge Only</option>
            </select>
        </div>

        <div class="mt-4">
            <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Create Announcement
            </button>
        </div>
    </form>
</div> 