<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        {{ $group->name }} - Route Selection
                    </h2>

                    @if (session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="mt-8 border-t border-gray-200 pt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Route Selection</h3>
                        <p class="text-sm text-gray-600 mb-4">Select which routes should appear in the dashboard.</p>

                        <livewire:group-route-selector :group="$group" />
                    </div>

                    <div class="mt-8 border-t border-gray-200 pt-8">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Train Table Data</h3>
                                <p class="text-sm text-gray-600">Select which train data should appear in the dashboard table.</p>
                            </div>
                        </div>
                        
                        <livewire:group-train-table-selector :group="$group" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 