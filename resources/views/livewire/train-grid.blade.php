<div x-data="{ modalOpen: false, selectedTrain: null }">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-4">
        @foreach($trains as $train)
        <div class="border bg-white border-gray-300 rounded-3xl dark:border-gray-600 h-32 md:h-64">
            <div class="p-6">
                <h3 class="text-3xl font-bold flex mb-2 items-center justify-between">
                    <div class="flex items-center">
                        <x-train-front-on />
                        {{ $train['number'] }}
                    </div>
                    <button type="button" @click.stop="modalOpen = true; selectedTrain = $el.dataset.train" 
                        data-train='@json($train)'
                        class="inline-flex items-center justify-center h-10 px-4 py-2 text-sm font-medium transition-colors border rounded-md hover:bg-neutral-100 active:bg-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-neutral-200/60 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none">
                        <x-heroicon-s-bars-3-bottom-right class="w-6 h-6" />
                    </button>
                </h3>
                <h3 class="text-xl font-bold">
                    Departure
                </h3>
                <h3 class="text-3xl font-bold flex mb-2">
                    {{ $train['departure'] }}
                </h3>
                <h3 class="text-xl font-bold">
                    Status
                </h3>
                <h3 class="text-3xl font-bold {{ $train['status_color'] === 'green' ? 'text-green-600' : ($train['status_color'] === 'red' ? 'text-red-600' : '') }}">
                    {{ $train['status'] }}
                </h3>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Modal -->
    <div x-show="modalOpen" 
        class="fixed inset-0 z-50"
        x-cloak
        @click.self="modalOpen = false"
        @keydown.escape.window="modalOpen = false">
        <div class="fixed top-0 left-0 z-[99] flex items-center justify-center w-screen h-screen">
            <div class="absolute inset-0 w-full h-full bg-white backdrop-blur-sm bg-opacity-70"></div>
            <div class="relative w-full py-6 bg-white border shadow-lg px-7 border-neutral-200 sm:max-w-lg sm:rounded-lg">
                <div class="flex items-center justify-between pb-3">
                    <h3 class="text-lg font-semibold">Train <span x-text="JSON.parse(selectedTrain)?.number"></span></h3>
                    <button @click="modalOpen = false"
                        class="absolute top-0 right-0 flex items-center justify-center w-8 h-8 mt-5 mr-5 text-gray-600 rounded-full hover:text-gray-800 hover:bg-gray-50">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="relative w-auto pb-8">
                    <p>This is placeholder text. Replace it with your own content.</p>
                </div>
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2">
                    <button @click="modalOpen = false" type="button"
                        class="inline-flex items-center justify-center h-10 px-4 py-2 text-sm font-medium transition-colors border rounded-md focus:outline-none focus:ring-2 focus:ring-neutral-100 focus:ring-offset-2">Cancel</button>
                    <button @click="modalOpen = false" type="button"
                        class="inline-flex items-center justify-center h-10 px-4 py-2 text-sm font-medium text-white transition-colors border border-transparent rounded-md focus:outline-none focus:ring-2 focus:ring-neutral-900 focus:ring-offset-2 bg-neutral-950 hover:bg-neutral-900">Continue</button>
                </div>
            </div>
        </div>
    </div>
</div>
