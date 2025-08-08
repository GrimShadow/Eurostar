<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Train Information') }} - {{ $group->name }}
            </h2>
            <div class="flex items-center space-x-4">
                <a href="{{ route('group.dashboard', $group) }}" 
                   class="text-sm text-gray-600 hover:text-gray-900">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @livewire('train-info-display', ['group' => $group])
        </div>
    </div>
</x-app-layout> 