<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — {{ \App\Models\Setting::get('nama_instansi', 'SI Puskesmas') }}</title>
    @php $logoInstansi = \App\Models\Setting::get('logo_instansi'); @endphp
    @if($logoInstansi)
        <link rel="icon" href="{{ $logoInstansi }}" type="image/png">
        <link rel="shortcut icon" href="{{ $logoInstansi }}">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-[Inter] antialiased">
    <div class="min-h-full flex items-center justify-center bg-gradient-to-br from-indigo-600 via-blue-600 to-cyan-500 px-4 py-12">
        <div class="w-full max-w-md">
            {{-- Logo / Header --}}
            <div class="text-center mb-8">
                @if($logoInstansi)
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm mb-5 shadow-lg overflow-hidden">
                        <img src="{{ $logoInstansi }}" alt="Logo" class="w-12 h-12 object-contain">
                    </div>
                @else
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm mb-5 shadow-lg">
                        <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21" />
                        </svg>
                    </div>
                @endif
                <h1 class="text-2xl font-bold text-white tracking-tight">{{ \App\Models\Setting::get('nama_instansi', 'SI Puskesmas') }}</h1>
                <p class="text-blue-100 mt-1 text-sm font-medium">{{ \App\Models\Setting::get('nama_sistem', 'Sistem Informasi Puskesmas') }}</p>
            </div>

            {{-- Login Card --}}
            <div class="bg-white rounded-2xl shadow-2xl shadow-indigo-900/20 p-8">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-900">Masuk</h2>
                    <p class="text-sm text-gray-500 mt-1">Silakan masuk ke akun Anda</p>
                </div>

                {{-- Error Messages --}}
                @if ($errors->any())
                    <div class="mb-4 flex items-center gap-2 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                            </div>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="nama@puskesmas.go.id"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                                autofocus
                                required
                            >
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </div>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                                required
                            >
                        </div>
                    </div>

                    {{-- Remember me --}}
                    <div class="flex items-center mb-6">
                        <input
                            type="checkbox"
                            id="remember"
                            name="remember"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500/20 transition"
                        >
                        <label for="remember" class="ml-2 text-sm text-gray-600 select-none">Ingat saya</label>
                    </div>

                    {{-- Submit --}}
                    <button
                        type="submit"
                        class="w-full flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-indigo-600 to-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/25 transition-all hover:from-indigo-700 hover:to-blue-700 hover:shadow-lg hover:shadow-indigo-500/30 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                    >
                        Masuk
                    </button>
                </form>
            </div>

            {{-- Footer --}}
            <p class="text-center text-blue-200/70 text-xs mt-6">&copy; {{ date('Y') }} {{ \App\Models\Setting::get('nama_instansi', 'SI Puskesmas') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
