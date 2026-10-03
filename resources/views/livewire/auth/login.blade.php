<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-7">
        <x-auth-header
            :title="__('Log in to your account')"
            :description="__('Enter your email and password below to log in')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />
        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <div class="grid gap-2">
                <label for="email" class="text-sm font-bold text-zinc-700">{{ __('Email address') }}</label>
                <input
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                    @error('email') aria-invalid="true" @enderror
                    class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-left text-zinc-900 outline-none transition placeholder:text-zinc-400 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10"
                >
                @error('email')
                    <p class="text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between gap-3">
                    <label for="password" class="text-sm font-bold text-zinc-700">{{ __('Password') }}</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm font-bold text-orange-700 hover:text-orange-800">
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif
                </div>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="{{ __('Password') }}"
                    class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-left text-zinc-900 outline-none transition placeholder:text-zinc-400 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10"
                >
                @error('password')
                    <p class="text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label for="remember" class="flex items-center gap-3 text-sm font-medium text-zinc-600">
                <input
                    id="remember"
                    name="remember"
                    type="checkbox"
                    value="1"
                    @checked(old('remember'))
                    class="size-4 rounded border-zinc-300 text-orange-600 focus:ring-orange-500"
                >
                <span>{{ __('Remember me') }}</span>
            </label>

            <button
                type="submit"
                data-test="login-button"
                class="mt-1 w-full rounded-xl bg-orange-600 px-5 py-3.5 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 focus:outline-none focus:ring-4 focus:ring-orange-500/30"
            >
                {{ __('Log in') }}
            </button>
        </form>

        <p class="text-center text-sm text-zinc-600">
            <span>{{ __('Don\'t have an account?') }}</span>
            <a href="{{ route('register') }}" class="font-bold text-orange-700 hover:text-orange-800">{{ __('Sign up') }}</a>
        </p>
    </div>
</x-layouts::auth>
