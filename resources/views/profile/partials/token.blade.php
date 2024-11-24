<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('API Tokens') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Create new API tokens that can be used to access the API.') }}
        </p>
    </header>

    <form method="post" action="{{ route('token.create') }}" class="mt-6 space-y-6">
        @csrf

        <div>
            <x-input-label for="token_name" value="{{ __('Token Name') }}" />
            <x-text-input id="token_name" name="token_name" type="text" class="mt-1 block w-full" required />
            <x-input-error :messages="$errors->get('token_name')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Create Token') }}</x-primary-button>
        </div>
    </form>

    @if(session()->has('token'))
        <div class="mt-4 p-4 bg-gray-100 rounded">
            <p class="text-sm text-gray-600 mb-2">{{ __('Please copy your new API token. For your security, it won\'t be shown again.') }}</p>
            <div class="flex items-center space-x-2">
                <input type="text" value="{{ str_replace('1|', '', session('token')) }}" 
                    class="flex-1 p-2 text-sm bg-white border rounded" 
                    readonly 
                    onclick="this.select()"
                >
                <button type="button" onclick="navigator.clipboard.writeText('{{ str_replace('1|', '', session('token')) }}')"
                    class="p-2 text-sm text-gray-600 hover:text-gray-900">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                    </svg>
                </button>
            </div>
        </div>
    @endif

    @if(isset($tokens) && $tokens->count() > 0)
        <div class="mt-6">
            <h3 class="text-md font-medium text-gray-900 mb-4">{{ __('Your API Tokens') }}</h3>
            <div class="space-y-2">
                @foreach($tokens as $token)
                    <div class="flex items-center justify-between p-3 bg-white border rounded">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $token->name }}</p>
                            <p class="text-xs text-gray-500">{{ __('Created') }}: {{ $token->created_at->diffForHumans() }}</p>
                        </div>
                        <form method="post" action="{{ route('token.destroy', $token) }}" class="inline">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-900">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>