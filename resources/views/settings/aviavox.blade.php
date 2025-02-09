<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Page Header -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Settings</h2>
                    <p class="mt-2 text-sm text-gray-600">Manage your application settings and configurations.</p>
                </div>
            </div>

            <!-- Success Message -->
            @if (session('success'))
                <div class="rounded-md bg-green-50 p-4 border border-green-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
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
                            <p class="mt-1 text-sm text-gray-500">Configure your Aviavox connection settings for announcements and communications.</p>
                        </div>
                        <!-- Test Connection Button -->
                        <form action="{{ route('settings.aviavox.test') }}" method="POST" class="flex-shrink-0">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                <svg class="mr-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M6.672 1.911a1 1 0 10-1.932.518l.259.966a1 1 0 001.932-.518l-.26-.966zM2.429 4.74a1 1 0 10-.517 1.932l.966.259a1 1 0 00.517-1.932l-.966-.26zm8.814-.569a1 1 0 00-1.415-1.414l-.707.707a1 1 0 101.415 1.415l.707-.708zm-7.071 7.072l.707-.707A1 1 0 003.465 9.12l-.708.707a1 1 0 001.415 1.415zm3.2-5.171a1 1 0 00-1.3 1.3l4 10a1 1 0 001.823.075l1.38-2.759 3.018 3.02a1 1 0 001.414-1.415l-3.019-3.02 2.76-1.379a1 1 0 00-.076-1.822l-10-4z" />
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
                                    <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-1">IP Address</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
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
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
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
                                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
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
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </div>
                                        <input type="password" name="password" id="password"
                                            placeholder="••••••••"
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <livewire:zones-table />

            <!-- Announcements Card -->
            <div class="bg-white shadow-sm sm:rounded-xl divide-y divide-gray-200">
                <div class="p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Add Announcement</h3>
                    <form action="{{ route('settings.aviavox.storeAnnouncement') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input type="text" name="name" id="name" 
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="item_id" class="block text-sm font-medium text-gray-700 mb-1">Item ID</label>
                                <select name="item_id" id="item_id" 
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="MessageName">MessageName</option>
                                </select>
                            </div>
                            <div>
                                <label for="value" class="block text-sm font-medium text-gray-700 mb-1">Value</label>
                                <input type="text" name="value" id="value" 
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Announcement
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Announcements Table -->
                <div class="p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Existing Announcements</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($announcements as $announcement)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $announcement->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $announcement->item_id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $announcement->value }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <form action="{{ route('settings.aviavox.deleteAnnouncement', $announcement) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 focus:outline-none">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <form action="{{ route('settings.aviavox.checkin-aware-fault') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Send Check-in Aware Fault
                </button>
            </form>

            <form action="{{ route('settings.aviavox.storeMessage') }}" method="POST" class="space-y-4 mt-6">
                @csrf
                <div>
                    <label for="message_name" class="block text-sm font-medium text-gray-700">Select Message Type</label>
                    <select name="message_name" id="message_name" required 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            x-on:change="updateParameters($event.target.value)">
                        <option value="">Select a message...</option>
                        @foreach($predefinedMessages as $name => $params)
                            <option value="{{ $name }}" data-params="{{ json_encode($params) }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="parameters-container" class="space-y-4">
                    <!-- Dynamic parameters will be inserted here -->
                </div>

                <div>
                    <label for="zones" class="block text-sm font-medium text-gray-700">Zones</label>
                    <select name="zones" id="zones" required 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="Terminal">Terminal</option>
                        <option value="Terminal,Lounge">Terminal & Lounge</option>
                        <option value="Lounge">Lounge Only</option>
                    </select>
                </div>

                <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Add Message
                </button>
            </form>

            <!-- Custom XML Announcement Card -->
            <div class="bg-white shadow-sm sm:rounded-xl divide-y divide-gray-200 mt-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Send Custom XML Announcement</h3>
                    
                    <!-- Available Message Names -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Available Message Names:</label>
                            <select id="availableMessages" name="message_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" onchange="updateCustomXml(this.value)" required>
                                <option value="">Select a message name...</option>
                                @foreach($availableMessageNames as $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <input type="text" name="description" id="description" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="E.g., Check-in announcement for train 9018">
                        </div>
                    </div>

                    <form action="{{ route('settings.aviavox.sendCustom') }}" method="POST">
                        @csrf
                        <div>
                            <label for="custom_xml" class="block text-sm font-medium text-gray-700">XML Content</label>
                            <textarea name="custom_xml" id="custom_xml" rows="10" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                                ><AIP>
    <MessageID>AnnouncementTriggerRequest</MessageID>
    <MessageData>
        <AnnouncementData>
            <Item ID="MessageName" Value="CHECKIN_AWARE_FAULT"/>
            <Item ID="Zones" Value="Terminal"/>
        </AnnouncementData>
    </MessageData>
</AIP></textarea>
                        </div>
                        <button type="submit" class="mt-4 inline-flex justify-center rounded-md border border-transparent bg-red-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            Send Custom Announcement
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

<!-- Add Alpine.js script for dynamic form handling -->
<script>
function updateParameters(messageName) {
    const select = document.querySelector('#message_name');
    const option = select.querySelector(`option[value="${messageName}"]`);
    const container = document.querySelector('#parameters-container');
    container.innerHTML = '';

    if (!option) return;

    const params = JSON.parse(option.dataset.params);
    params.forEach(param => {
        const div = document.createElement('div');
        div.innerHTML = `
            <label for="${param}" class="block text-sm font-medium text-gray-700">${param}</label>
            <input type="text" name="parameters[${param}]" id="${param}" 
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                   ${param === 'ScheduledTime' ? 'placeholder="YYYY-MM-DDThh:mm:ssZ"' : ''}>
        `;
        container.appendChild(div);
    });
}

function updateCustomXml(messageName) {
    if (!messageName) return;
    
    const xmlTemplate = `<AIP>
    <MessageID>AnnouncementTriggerRequest</MessageID>
    <MessageData>
        <AnnouncementData>
            <Item ID="MessageName" Value="${messageName}"/>
            <Item ID="Zones" Value="Terminal"/>
        </AnnouncementData>
    </MessageData>
</AIP>`;
    
    document.getElementById('custom_xml').value = xmlTemplate;
}
</script>
