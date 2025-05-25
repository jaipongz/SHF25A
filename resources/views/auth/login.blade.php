<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
        <div class="text-center mb-6">
      <h1 class="text-4xl font-bold text-orange-600">Central<span class="text-blue-700"> PLATING</span></h1>
    </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="mb-4 text-sm text-green-600">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block mb-1 font-medium text-gray-700">Email</label>
                <div class="flex items-center border rounded-lg px-3 py-2">
                    <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.657 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <input type="email" id="email" name="email" :value="old('email')" required autofocus
                        autocomplete="username" placeholder="Enter email"
                        class="w-full border-none focus:outline-none focus:ring-0">
                </div>
                @error('email')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block mb-1 font-medium text-gray-700">Password</label>
                <div class="flex items-center border rounded-lg px-3 py-2">
                    <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 11c0-1.105.895-2 2-2s2 .895 2 2v1m0 4h-6m2-4v4m-6 0a2 2 0 01-2-2v-4a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H6z" />
                    </svg>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                        placeholder="Enter password" class="w-full border-none focus:outline-none focus:ring-0">
                </div>
                @error('password')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="remember"
                        class="rounded border-gray-300 text-orange-500 shadow-sm focus:ring-orange-500">
                    <span class="ml-2 text-sm text-gray-600">Remember me</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:underline">
                        Forgot your password?
                    </a>
                @endif
            </div>

            <button type="submit"
                class="w-full bg-orange-500 hover:bg-orange-600 text-white py-2 rounded-lg transition duration-200">
                LOGIN
            </button>
        </form>
    </div>
</body>

</html>
