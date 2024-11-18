<x-app-layout>
    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden">
                <livewire:train-grid />

                <div class="max-w-7xl mx-auto flex flex-col">
                    <div class="overflow-x-auto">
                        <div class="inline-block min-w-full">
                            <div class="overflow-hidden border rounded-lg">
                                <table class="min-w-full divide-y divide-neutral-200">
                                    <thead class="bg-neutral-50">
                                        <tr class="text-neutral-500">
                                            <th class="px-5 py-3 text-xs font-medium text-left uppercase">Train
                                            </th>
                                            <th class="px-5 py-3 text-xs font-medium text-left uppercase">Departure
                                            </th>
                                            <th class="px-5 py-3 text-xs font-medium text-left uppercase">Destination
                                            </th>
                                            <th class="px-5 py-3 text-xs font-medium text-left uppercase">Status
                                            </th>
                                            <th class="px-5 py-3 text-xs font-medium text-left uppercase">Platform
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-neutral-200">
                                        <tr class="text-neutral-800">
                                            <td class="px-5 py-4 text-sm font-medium whitespace-nowrap">9147
                                            </td>
                                            <td class="px-5 py-4 text-sm whitespace-nowrap">14:47</td>
                                            <td class="px-5 py-4 text-sm whitespace-nowrap">Belgium</td>
                                            <td class="px-5 py-4 text-sm whitespace-nowrap">On-time</td>
                                            <td class="px-5 py-4 text-sm whitespace-nowrap">15b</td>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
