<div>
    <div class="mt-4 bg-white overflow-hidden shadow-sm sm:rounded-lg" wire:key="announcements-table">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Zone</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($announcements as $announcement)
                        <tr wire:key="announcement-{{ $announcement->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $announcement->type }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $announcement->message }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $announcement->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $announcement->author }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $announcement->area }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $announcement->status === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 
                                       ($announcement->status === 'Finished' ? 'bg-green-100 text-green-800' : 
                                       'bg-red-100 text-red-800') }}">
                                    {{ $announcement->status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4" wire:ignore.self>
                <div wire:key="pagination">
                    {{ $announcements->links() }}
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Force light theme for pagination */
        .relative.z-0.inline-flex {
            background-color: white !important;
        }
        .relative.inline-flex.items-center {
            background-color: white !important;
            color: #374151 !important; /* text-gray-700 */
            border-color: #D1D5DB !important; /* border-gray-300 */
        }
        .relative.inline-flex.items-center:hover {
            background-color: #F9FAFB !important; /* bg-gray-50 */
            color: #6B7280 !important; /* text-gray-500 */
        }
        .relative.inline-flex.items-center[aria-current="page"] {
            background-color: #EFF6FF !important; /* bg-blue-50 */
            color: #2563EB !important; /* text-blue-600 */
            border-color: #3B82F6 !important; /* border-blue-500 */
        }
        .relative.inline-flex.items-center[aria-disabled="true"] {
            background-color: white !important;
            color: #6B7280 !important; /* text-gray-500 */
        }
        /* SVG icons */
        .w-5.h-5 {
            color: #6B7280 !important; /* text-gray-500 */
        }
        /* Focus states */
        .focus\:ring-gray-300:focus {
            --tw-ring-color: #D1D5DB !important; /* ring-gray-300 */
        }
        .focus\:border-blue-300:focus {
            border-color: #93C5FD !important; /* border-blue-300 */
        }
        /* Active states */
        .active\:bg-gray-100:active {
            background-color: #F3F4F6 !important; /* bg-gray-100 */
        }
        .active\:text-gray-700:active {
            color: #374151 !important; /* text-gray-700 */
        }
        /* Override dark mode classes */
        .dark .relative.z-0.inline-flex,
        .dark .relative.inline-flex.items-center,
        .dark .relative.inline-flex.items-center[aria-disabled="true"] {
            background-color: white !important;
            color: #374151 !important;
            border-color: #D1D5DB !important;
        }
        .dark .relative.inline-flex.items-center[aria-current="page"] {
            background-color: #EFF6FF !important;
            color: #2563EB !important;
            border-color: #3B82F6 !important;
        }
        .dark .w-5.h-5 {
            color: #6B7280 !important;
        }
    </style>
</div>
