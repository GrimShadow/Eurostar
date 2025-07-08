<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="text-2xl font-bold mb-4">Log File Entries</h2>

                    <!-- Export Logs Button -->
                    <div class="mb-4">
                        <a href="{{ route('settings.logs.export') }}"
                           class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                           Export Logs
                        </a>
                    </div>

                    <!-- Log Contents -->
                    <div class="bg-gray-100 p-4 rounded overflow-x-auto max-h-96">
                        @if ($logContents)
                            @foreach (explode("\n", $logContents) as $line)
                                <div class="py-1">
                                    @if (str_contains($line, 'ERROR'))
                                        <p class="text-red-500 font-semibold">{{ $line }}</p>
                                    @elseif (str_contains($line, 'INFO'))
                                        <p class="text-blue-500">{{ $line }}</p>
                                    @elseif (str_contains($line, 'WARNING'))
                                        <p class="text-yellow-500">{{ $line }}</p>
                                    @else
                                        <p class="text-gray-700">{{ $line }}</p>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <p class="text-gray-500">No log file found or the log file is currently empty.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
