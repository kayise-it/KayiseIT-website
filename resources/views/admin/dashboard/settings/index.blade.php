@extends('admin.dashboard.layout')

@section('page-title', 'Settings')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
            <p class="mt-1 text-sm text-gray-600">Manage your dashboard preferences and account shortcuts.</p>
        </div>

        @if(session('success'))
            <div class="mb-4 max-w-3xl bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg text-sm" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 max-w-3xl bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded-lg text-sm" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 max-w-3xl bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded-lg text-sm" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid gap-6 max-w-3xl">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900">Public website — floating buttons</h2>
                <p class="mt-1 text-sm text-gray-600">Control the green WhatsApp pill and the blue “Ask me” assistant on pages that use the main site layout (not the admin dashboard).</p>

                <form method="POST" action="{{ route('dashboard.settings.update') }}" class="mt-6 space-y-6">
                    @csrf
                    <input type="hidden" name="section" value="floating">

                    <div class="py-3 border-b border-gray-100">
                        <label for="whatsapp_e164" class="font-medium text-gray-900">WhatsApp number</label>
                        <p class="text-sm text-gray-500 mt-0.5">Banner QR codes point at this permanent URL. Change the number here; the printed QR stays the same. Leave blank to send visitors to the Contact page.</p>
                        <input type="text" name="whatsapp_e164" id="whatsapp_e164"
                               value="{{ old('whatsapp_e164', $siteSettings->whatsapp_e164) }}"
                               placeholder="27693907862"
                               inputmode="tel"
                               autocomplete="off"
                               class="mt-2 block w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-kb-500 focus:ring-kb-500 text-sm">
                        <p class="mt-2 text-sm text-gray-600">Public URL: <span class="font-mono text-xs">https://kayiseit.com/whatsapp</span></p>
                        @error('whatsapp_e164')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 py-3 border-b border-gray-100">
                        <div>
                            <p class="font-medium text-gray-900">WhatsApp floating button</p>
                            <p class="text-sm text-gray-500 mt-0.5">Shows the fixed WhatsApp shortcut on the public site.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" name="show_whatsapp_floating" value="1" class="sr-only peer"
                                @checked(old('show_whatsapp_floating', $siteSettings->show_whatsapp_floating))>
                            <span class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-kg-100 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-kg-500 relative"></span>
                        </label>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 py-3 border-b border-gray-100">
                        <div>
                            <p class="font-medium text-gray-900">“Ask me” chatbot</p>
                            <p class="text-sm text-gray-500 mt-0.5">Shows the assistant launcher and chat panel on the public site.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" name="show_chatbot_floating" value="1" class="sr-only peer"
                                @checked(old('show_chatbot_floating', $siteSettings->show_chatbot_floating))>
                            <span class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-kg-100 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-kg-500 relative"></span>
                        </label>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-kb-100 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-kb-600 focus:outline-none focus:ring-2 focus:ring-kb-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Save
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900">LMIS integration</h2>
                <p class="mt-1 text-sm text-gray-600">Push newly created programmes to LMIS. If LMIS is offline, Kayise queues the payload and retries until it is reachable.</p>
                <p class="mt-2 text-sm text-gray-500">Dev default is <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">http://localhost:3010</code>. LMIS must expose <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">POST /api/programmes</code> with Bearer auth and idempotent <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">external_id</code>, plus <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">GET /api/health</code>. Both apps need to run on the same machine (or use a public LMIS URL later). Run <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">php artisan schedule:work</code> so offline rows drain automatically.</p>

                <form method="POST" action="{{ route('dashboard.settings.update') }}" class="mt-6 space-y-5">
                    @csrf
                    <input type="hidden" name="section" value="lmis">

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 py-3 border-b border-gray-100">
                        <div>
                            <p class="font-medium text-gray-900">Enable LMIS sync</p>
                            <p class="text-sm text-gray-500 mt-0.5">When on, creating a programme also pushes it to LMIS.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" name="lmis_enabled" value="1" class="sr-only peer"
                                @checked(old('lmis_enabled', $siteSettings->lmis_enabled))>
                            <span class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-kg-100 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-kg-500 relative"></span>
                        </label>
                    </div>

                    <div>
                        <label for="lmis_base_url" class="block font-medium text-gray-900">Base URL</label>
                        <input type="url" name="lmis_base_url" id="lmis_base_url"
                               value="{{ old('lmis_base_url', $siteSettings->lmis_base_url ?: 'http://localhost:3010') }}"
                               placeholder="http://localhost:3010"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-kb-500 focus:ring-kb-500 text-sm">
                    </div>

                    <div>
                        <label for="lmis_api_token" class="block font-medium text-gray-900">API token</label>
                        <input type="password" name="lmis_api_token" id="lmis_api_token" value="" autocomplete="new-password"
                               placeholder="{{ $siteSettings->lmis_api_token ? 'Leave blank to keep the current token' : 'Bearer token for LMIS' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-kb-500 focus:ring-kb-500 text-sm">
                    </div>

                    <div class="flex flex-wrap gap-3 pt-1">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-kb-100 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-kb-600 focus:outline-none focus:ring-2 focus:ring-kb-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Save LMIS connection
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('dashboard.settings.lmis.test') }}" class="mt-3">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-kb-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Test connection
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900">Signed-in account</h2>
                <p class="mt-1 text-sm text-gray-600">Details for the user currently using the admin dashboard.</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="font-medium text-gray-500">Name</dt>
                        <dd class="text-gray-900">{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Email</dt>
                        <dd class="text-gray-900">{{ $user->email }}</dd>
                    </div>
                </dl>
                <div class="mt-6">
                    <a href="{{ route('profile.edit') }}"
                       class="inline-flex items-center px-4 py-2 bg-kb-100 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-kb-600 focus:outline-none focus:ring-2 focus:ring-kb-500 focus:ring-offset-2 transition ease-in-out duration-150 no-underline">
                        Edit profile
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900">Application</h2>
                <p class="mt-1 text-sm text-gray-600">Environment and framework information (read-only).</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="font-medium text-gray-500">Environment</dt>
                        <dd class="text-gray-900">{{ app()->environment() }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Laravel</dt>
                        <dd class="text-gray-900">{{ app()->version() }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
