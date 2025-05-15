<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Page Header -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                        Edit Announcement Template</h2>
                    <p class="mt-2 text-sm text-gray-600">Edit your announcement template settings and configurations.</p>
                </div>
            </div>

            <!-- Success Message -->
            @if (session('success'))
            <div class="rounded-md bg-green-50 p-4 border border-green-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Edit Template Form -->
            <div class="bg-white shadow-sm sm:rounded-xl p-6">
                <form action="{{ route('settings.aviavox.updateTemplate', $template) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="friendly_name" class="block text-sm font-medium text-gray-700">Friendly Name</label>
                            <input type="text" name="friendly_name" id="friendly_name" 
                                value="{{ old('friendly_name', $template->friendly_name) }}"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="e.g., Check-in Welcome (Closed)">
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Template Name</label>
                            <input type="text" name="name" id="name" 
                                value="{{ old('name', $template->name) }}"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="e.g., CHECKIN_WELCOME_CLOSED">
                        </div>

                        <div>
                            <label for="xml_template" class="block text-sm font-medium text-gray-700">XML Template</label>
                            <textarea name="xml_template" id="xml_template" rows="10"
                                onchange="detectVariables(this.value)"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-mono"
                                placeholder="<AIP>...">{{ old('xml_template', $template->xml_template) }}</textarea>
                        </div>

                        <div id="variables-container" class="space-y-4">
                            @foreach($template->variables as $id => $type)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ $id }} Variable Type</label>
                                <select name="variables[{{ $id }}]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">Select variable type...</option>
                                    <option value="zone" {{ $type === 'zone' ? 'selected' : '' }}>Zone (from zones table)</option>
                                    <option value="train" {{ $type === 'train' ? 'selected' : '' }}>Train Number (from trips)</option>
                                    <option value="datetime" {{ $type === 'datetime' ? 'selected' : '' }}>Date/Time Input</option>
                                    <option value="route" {{ $type === 'route' ? 'selected' : '' }}>Route Selection</option>
                                    <option value="text" {{ $type === 'text' ? 'selected' : '' }}>Delay in minutes</option>
                                    <option value="reason" {{ $type === 'reason' ? 'selected' : '' }}>Reason Selection</option>
                                </select>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('settings.aviavox') }}" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </a>
                        <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Update Template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>

<script>
    function detectVariables(xml) {
        const container = document.getElementById('variables-container');
        container.innerHTML = '';
        
        // Parse XML string
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(xml, "text/xml");
        const items = xmlDoc.getElementsByTagName("Item");
        
        // Process each Item element
        for (let item of items) {
            const id = item.getAttribute("ID");
            const value = item.getAttribute("Value");
            
            // Skip MessageName as it's handled automatically
            if (id === 'MessageName') continue;
            
            // Create variable selector
            const div = document.createElement('div');
            div.innerHTML = `
                <label class="block text-sm font-medium text-gray-700">${id} Variable Type</label>
                <select name="variables[${id}]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">Select variable type...</option>
                    <option value="zone">Zone (from zones table)</option>
                    <option value="train">Train Number (from trips)</option>
                    <option value="datetime">Date/Time Input</option>
                    <option value="route">Route Selection</option>
                    <option value="text">Delay in minutes</option>
                    <option value="reason">Reason Selection</option>
                </select>
            `;
            container.appendChild(div);
        }
    }
</script> 