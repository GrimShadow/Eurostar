<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $group->name }} Announcements
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold">Group Announcements</h3>
                        <a href="{{ $group->getDashboardUrl() }}" class="text-blue-600 hover:text-blue-800">
                            Back to Dashboard
                        </a>
                    </div>

                    <!-- Announcements will be displayed here -->
                    <div class="space-y-4">
                        @forelse($group->announcements as $announcement)
                            <div class="border rounded-lg p-4">
                                <h4 class="font-medium">{{ $announcement->title }}</h4>
                                <p class="text-gray-600 mt-2">{{ $announcement->content }}</p>
                                <p class="text-sm text-gray-500 mt-2">
                                    Posted on {{ $announcement->created_at->format('M d, Y H:i') }}
                                </p>
                            </div>
                        @empty
                            <p class="text-gray-500">No announcements available for this group.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 