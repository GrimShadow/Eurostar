<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Log File Entries</h2>
                            <p class="mt-1 text-sm text-gray-500">System logs and application events</p>
                        </div>
                        
                        <!-- Export Logs Button -->
                        <div class="flex space-x-2">
                            <a href="{{ route('settings.logs.export') }}"
                               class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                               <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                               </svg>
                               Export Logs
                            </a>

                            <form action="{{ route('settings.logs.clear') }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    onclick="return confirm('Are you sure you want to clear all logs? This action cannot be undone.')"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                    Clear Logs
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Log Filter -->
                    <div class="mb-6">
                        <div class="flex space-x-2">
                            <button class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 hover:bg-gray-200">All</button>
                            <button class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200">Errors</button>
                            <button class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200">Info</button>
                        </div>
                    </div>

                    <!-- Log Contents -->
                    <div class="space-y-4">
                        @if ($logContents)
                            @php
                                $currentDate = null;
                                $logs = collect(preg_split('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $logContents, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY))
                                    ->chunk(2)
                                    ->map(function($chunk) {
                                        return [
                                            'timestamp' => $chunk->first(),
                                            'content' => $chunk->last()
                                        ];
                                    });
                            @endphp
                            
                            @foreach ($logs as $log)
                                @php
                                    $timestampString = trim($log['timestamp'] ?? '');
                                    if (empty($timestampString) || strlen($timestampString) < 19) {
                                        $timestamp = \Carbon\Carbon::now();
                                    } else {
                                        try {
                                            $timestamp = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $timestampString);
                                        } catch (\Exception $e) {
                                            $timestamp = \Carbon\Carbon::now();
                                        }
                                    }
                                    $dateString = $timestamp->format('Y-m-d');
                                    $content = trim($log['content']);
                                    $logLevel = str_contains($content, '.ERROR:') ? 'ERROR' : (str_contains($content, '.INFO:') ? 'INFO' : 'UNKNOWN');
                                @endphp

                                @if($dateString !== $currentDate)
                                    <div class="relative py-3">
                                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                            <div class="w-full border-t border-gray-200"></div>
                                        </div>
                                        <div class="relative flex justify-start">
                                            <span class="pr-3 bg-white text-sm text-gray-500">
                                                {{ $timestamp->format('F j, Y') }}
                                            </span>
                                        </div>
                                    </div>
                                    @php
                                        $currentDate = $dateString;
                                    @endphp
                                @endif

                                <div x-data="{ expanded: false }" class="bg-gray-50 rounded-lg shadow-sm">
                                    <button @click="expanded = !expanded" class="w-full px-4 py-3 text-left">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <!-- Log Level Icon -->
                                                @if ($logLevel === 'ERROR')
                                                    <span class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100">
                                                        <svg class="w-5 h-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                                        </svg>
                                                    </span>
                                                @elseif ($logLevel === 'INFO')
                                                    <span class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100">
                                                        <svg class="w-5 h-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                                        </svg>
                                                    </span>
                                                @endif

                                                <div class="flex flex-col">
                                                    <!-- Timestamp -->
                                                    <span class="text-xs text-gray-500">
                                                        {{ $timestamp->format('H:i:s') }}
                                                    </span>
                                                    <!-- Log Preview -->
                                                    <span class="text-sm font-medium text-gray-900">
                                                        {{ Str::limit(trim(preg_replace('/local\.(INFO|ERROR):\s*/', '', $content)), 100) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': expanded }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </div>
                                    </button>
                                    
                                    <!-- Expanded Content -->
                                    <div x-show="expanded" x-collapse class="px-4 pb-4">
                                        @php
                                            $messageContent = trim(preg_replace('/local\.(INFO|ERROR):\s*/', '', $content));
                                            $isJson = @json_decode($messageContent) !== null;
                                        @endphp
                                        
                                        @if($isJson)
                                            <pre class="text-sm bg-gray-900 text-white p-4 rounded-md overflow-x-auto"><code>{{ json_encode(json_decode($messageContent), JSON_PRETTY_PRINT) }}</code></pre>
                                        @else
                                            <pre class="text-sm whitespace-pre-wrap">{{ $messageContent }}</pre>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900">No logs found</h3>
                                <p class="mt-1 text-sm text-gray-500">No log entries are available at this time.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
