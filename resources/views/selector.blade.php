<x-selector-layout>
    <div class="min-h-screen flex items-center justify-center">
        <div class="w-[800px] mx-auto">
            <div class="flex flex-wrap justify-center gap-8">
                @foreach($groups as $group)
                    <a href="{{ route('dashboard') }}" class="group w-[240px] h-[240px] relative overflow-hidden shadow-sm sm:rounded-lg hover:shadow-xl transition-all duration-500 flex flex-col text-2xl font-bold text-gray-900">
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
    </div>
</x-selector-layout>
