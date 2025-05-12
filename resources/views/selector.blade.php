<x-selector-layout>
    <div class="min-h-screen flex items-center justify-center">
        <div class="w-[800px] mx-auto">
            <div class="flex flex-wrap justify-center gap-8">
                @foreach($groups as $group)
                    <a href="{{ $group->getDashboardUrl() }}" class="group w-[240px] h-[240px] relative overflow-hidden shadow-sm sm:rounded-lg hover:shadow-xl transition-all duration-500 flex flex-col text-2xl font-bold text-gray-900">
                        @if($group->image)
                            <div class="absolute inset-0 bg-cover bg-center transition-all duration-500 group-hover:scale-110 group-hover:brightness-110" style="background-image: url('{{ asset('storage/' . $group->image) }}')"></div>
                            <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/50 to-transparent transition-all duration-500 group-hover:from-black/80 group-hover:via-black/40"></div>
                        @else
                            <div class="absolute inset-0 bg-gradient-to-r from-gray-800 via-gray-600 to-transparent transition-all duration-500 group-hover:from-gray-700 group-hover:via-gray-500"></div>
                        @endif
                        <div class="relative z-10 mt-auto p-4 transform transition-transform duration-500 group-hover:translate-y-1">
                            <span class="text-white drop-shadow-lg text-xl font-semibold tracking-wide">{{ $group->name }}</span>
                        </div>
                        <div class="absolute top-4 right-4 z-10 opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @if(auth()->user()->hasRole('admin'))
            <div class="fixed bottom-8 right-8">
                <a href="{{ route('settings') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </a>
            </div>
        @endif
    </div>
</x-selector-layout>
