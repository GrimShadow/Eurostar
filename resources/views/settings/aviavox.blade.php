<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Page Header -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                        Settings</h2>
                    <p class="mt-2 text-sm text-gray-600">Manage your application settings and configurations.</p>
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

            <!-- Aviavox Settings Card -->
            <div class="bg-white shadow-sm sm:rounded-xl divide-y divide-gray-200">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Aviavox Configuration</h3>
                            <p class="mt-1 text-sm text-gray-500">Configure your Aviavox connection settings for
                                announcements and communications.</p>
                        </div>
                        <!-- Test Connection Button -->
                        <form action="{{ route('settings.aviavox.test') }}" method="POST" class="flex-shrink-0">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                <svg class="mr-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        d="M6.672 1.911a1 1 0 10-1.932.518l.259.966a1 1 0 001.932-.518l-.26-.966zM2.429 4.74a1 1 0 10-.517 1.932l.966.259a1 1 0 00.517-1.932l-.966-.26zm8.814-.569a1 1 0 00-1.415-1.414l-.707.707a1 1 0 101.415 1.415l.707-.708zm-7.071 7.072l.707-.707A1 1 0 003.465 9.12l-.708.707a1 1 0 001.415 1.415zm3.2-5.171a1 1 0 00-1.3 1.3l4 10a1 1 0 001.823.075l1.38-2.759 3.018 3.02a1 1 0 001.414-1.415l-3.019-3.02 2.76-1.379a1 1 0 00-.076-1.822l-10-4z" />
                                </svg>
                                Test Connection
                            </button>
                        </form>
                    </div>

                    <!-- Settings Form -->
                    <form action="{{ route('settings.aviavox.update') }}" method="POST" class="mt-6">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-6">
                                <!-- IP Address Field -->
                                <div class="relative">
                                    <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-1">IP
                                        Address</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                            </svg>
                                        </div>
                                        <input type="text" name="ip_address" id="ip_address"
                                            value="{{ old('ip_address', $aviavoxSettings?->ip_address) }}"
                                            class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    @error('ip_address')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Port Field -->
                                <div class="relative">
                                    <label for="port" class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input type="text" name="port" id="port"
                                            value="{{ old('port', $aviavoxSettings?->port) }}"
                                            class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    @error('port')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-6">
                                <!-- Username Field -->
                                <div class="relative">
                                    <label for="username"
                                        class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <input type="text" name="username" id="username"
                                            value="{{ old('username', $aviavoxSettings?->username) }}"
                                            class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    @error('username')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Password Field -->
                                <div class="relative">
                                    <label for="password"
                                        class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </div>
                                        <input type="password" name="password" id="password" placeholder="••••••••"
                                            class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-neutral-800 text-white text-sm font-medium rounded-lg hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 transition-colors">
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <livewire:zones-table />


            <form action="{{ route('settings.aviavox.checkin-aware-fault') }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                    class="inline-flex items-center mt-4 px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Send Check-in Aware Fault
                </button>
            </form>

            <!-- Add Announcement Template Form -->
            <div class="bg-white shadow-sm sm:rounded-xl p-6 mt-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Add Announcement Template</h3>
                <form action="{{ route('settings.aviavox.storeTemplate') }}" method="POST" class="space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="friendly_name" class="block text-sm font-medium text-gray-700">Friendly Name</label>
                            <input type="text" name="friendly_name" id="friendly_name" 
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="e.g., Check-in Welcome (Closed)">
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Template Name</label>
                            <input type="text" name="name" id="name" 
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="e.g., CHECKIN_WELCOME_CLOSED">
                        </div>

                        <div>
                            <label for="xml_template" class="block text-sm font-medium text-gray-700">XML Template</label>
                            <textarea name="xml_template" id="xml_template" rows="10"
                                onchange="detectVariables(this.value)"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-mono"
                                placeholder="<AIP>..."></textarea>
                        </div>

                        <div id="variables-container" class="space-y-4">
                            <!-- Variables will be added here dynamically -->
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Add Template
                        </button>
                    </div>
                </form>
            </div>

            <!-- Existing Templates Table -->
            <div class="bg-white shadow-sm sm:rounded-xl p-6 mt-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Existing Announcement Templates</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Friendly Name
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Template Name
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Variables
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Created At
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($templates as $template)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $template->friendly_name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $template->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @foreach($template->variables as $id => $type)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2">
                                            {{ $id }}: {{ $type }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $template->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <button onclick="showXml('{{ addslashes($template->xml_template) }}')" 
                                        class="text-blue-600 hover:text-blue-900">View XML</button>
                                    <form action="{{ route('settings.aviavox.deleteTemplate', $template) }}" method="POST" class="inline ml-3">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- XML Preview Modal -->
            <div id="xmlModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
                <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">XML Template</h3>
                        <button onclick="hideXml()" class="text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <pre id="xmlContent" class="bg-gray-50 p-4 rounded-lg overflow-x-auto text-sm"></pre>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-xl p-6 mt-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Add Custom Announcement</h3>
                <form action="{{ route('settings.aviavox.custom') }}" method="POST" class="space-y-6">
                    @csrf
                    <div>
                        <label for="custom_xml" class="block text-sm font-medium text-gray-700">XML Announcement</label>
                        <textarea
                            name="custom_xml"
                            id="custom_xml"
                            rows="10"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="Paste your XML announcement here..."
                        ></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Send Announcement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>

<!-- Add Alpine.js script for dynamic form handling -->
<script>
    function updateParameters(messageName) {
        const container = document.querySelector('#parameters-container');
        container.innerHTML = '';
        
        const parameters = getMessageParameters(messageName);
        
        parameters.forEach(param => {
            const div = document.createElement('div');
            div.innerHTML = createParameterInput(param);
            container.appendChild(div);
        });
    }

    function getMessageParameters(messageName) {
        const parameterMap = {
            'CHECKIN_WELCOME_CLOSED': ['TrainNumber', 'Route', 'ScheduledTime'],
            'CHECKIN_WELCOME_OPEN': ['TrainNumber'],
            'CHECKIN_WELCOME_WAIT': ['TrainNumber', 'Quantity'],
            'DEPART_DELAY_OE': ['Reason', 'PublicTime'],
            'DEPART_DELAY_STR': ['Quantity'],
            'DEPART_BOARD_FAMILY': ['TrainNumber', 'Route'],
            'DEPART_BOARD_LOUNGE': ['TrainNumber', 'Route'],
            'DEPART_LINE_OE': ['Reason', 'Quantity'],
            'DEPART_WAIT_CHECKIN': ['TrainNumber', 'Route', 'Quantity'],
            'TRAIN_COMFORT_CATER_HOT': ['Company'],
            'TRAIN_COMFORT_CATER_LIMIT': ['Company'],
            'TRAIN_COMFORT_CATER_NO': ['Company'],
            'TRAIN_COMFORT_TOILET': ['Company'],
            'TRAIN_COMFORT_WEATHER': ['Company']
        };
        
        return parameterMap[messageName] || [];
    }

    function createParameterInput(param) {
        let input = '';
        
        switch(param) {
            case 'ScheduledTime':
            case 'PublicTime':
                input = `<input type="datetime-local" name="parameters[${param}]" 
                         class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">`;
                break;
            case 'Delay in minutes':
                input = `<input type="number" name="parameters[${param}]" min="1" max="99"
                         class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">`;
                break;
            case 'Route':
                input = `<select name="parameters[${param}]" 
                         class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="GBR_LON">London</option>
                            <option value="FRA_PAR">Paris</option>
                            <option value="BEL_BRU">Brussels</option>
                            <option value="NLD_AMS">Amsterdam</option>
                        </select>`;
                break;
            default:
                input = `<input type="text" name="parameters[${param}]" 
                         class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">`;
        }
        
        return `
            <label class="block text-sm font-medium text-gray-700">${param}</label>
            ${input}
        `;
    }

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

    function showXml(xml) {
        document.getElementById('xmlContent').textContent = xml;
        document.getElementById('xmlModal').classList.remove('hidden');
    }

    function hideXml() {
        document.getElementById('xmlModal').classList.add('hidden');
    }

</script>
