<div class="max-w-7xl mx-auto flex flex-col">
    <div class="overflow-x-auto">
        <div class="inline-block min-w-full">
            <div class="overflow-hidden border rounded-lg">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr class="text-neutral-500">
                            <th class="px-5 py-3 text-xs font-medium text-left uppercase">Train</th>
                            <th class="px-5 py-3 text-xs font-medium text-left uppercase">Departure</th>
                            <th class="px-5 py-3 text-xs font-medium text-left uppercase">Destination</th>
                            <th class="px-5 py-3 text-xs font-medium text-left uppercase">Status</th>
                            <th class="px-5 py-3 text-xs font-medium text-left uppercase">Platform</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200">
                        @foreach($trains as $train)
                            <tr class="text-neutral-800">
                                <td class="px-5 py-4 text-sm font-medium whitespace-nowrap">{{ $train['number'] }}</td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap">{{ $train['departure'] }}</td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap">{{ $train['destination'] }}</td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap">{{ $train['status'] }}</td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap">{{ $train['platform'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-4">
        {{ $trains->links() }}
    </div>
</div> 