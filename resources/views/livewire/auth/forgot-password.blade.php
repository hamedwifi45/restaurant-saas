<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-7">
        <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
            @csrf
            <div class="grid gap-2">
                <label for="email" class="text-sm font-bold text-zinc-700">{{ __('Email address') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="email@example.com" class="w-full rounded-xl border border-zinc-200 px-4 py-3 outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10">
                @error('email')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" data-test="email-password-reset-link-button" class="w-full rounded-xl bg-orange-600 px-5 py-3.5 font-black text-white transition hover:bg-orange-700">
                {{ __('Email password reset link') }}
            </button>
        </form>

        <p class="text-center text-sm text-zinc-500">
            {{ __('Or, return to') }}
            <a href="{{ route('login') }}" class="font-bold text-orange-700">{{ __('log in') }}</a>
        </p>
    </div>
</x-layouts::auth>
