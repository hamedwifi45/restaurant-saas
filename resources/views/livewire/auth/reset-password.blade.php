<x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-7">
        <x-auth-header :title="__('Reset password')" :description="__('Please enter your new password below')" />
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
            @csrf
            <input type="hidden" name="token" value="{{ request()->route('token') }}">
            <div class="grid gap-2">
                <label for="email" class="text-sm font-bold text-zinc-700">{{ __('Email') }}</label>
                <input id="email" name="email" value="{{ request('email') }}" type="email" required autocomplete="email" class="w-full rounded-xl border border-zinc-200 px-4 py-3 outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10">
                @error('email')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-2">
                <label for="password" class="text-sm font-bold text-zinc-700">{{ __('Password') }}</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="{{ __('Password') }}" passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" class="w-full rounded-xl border border-zinc-200 px-4 py-3 outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10">
                @error('password')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-2">
                <label for="password_confirmation" class="text-sm font-bold text-zinc-700">{{ __('Confirm password') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="{{ __('Confirm password') }}" passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" class="w-full rounded-xl border border-zinc-200 px-4 py-3 outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10">
            </div>
            <button type="submit" data-test="reset-password-button" class="w-full rounded-xl bg-orange-600 px-5 py-3.5 font-black text-white transition hover:bg-orange-700">
                {{ __('Reset password') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
