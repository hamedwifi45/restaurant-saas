<x-layouts::auth :title="__('Confirm password')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Confirm password')"
            :description="__('This is a secure area of the application. Please confirm your password before continuing.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify
            options-route="passkey.confirm-options"
            submit-route="passkey.confirm"
            :label="__('Confirm with passkey')"
            :loading-label="__('Confirming...')"
            :separator="__('Or confirm with password')"
        />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <div class="grid gap-2">
                <label for="password" class="text-sm font-bold text-zinc-700">{{ __('Password') }}</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="{{ __('Password') }}"
                    class="w-full rounded-xl border border-zinc-200 px-4 py-3 outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10"
                >
                @error('password')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" data-test="confirm-password-button" class="w-full rounded-xl bg-orange-600 px-5 py-3.5 font-black text-white transition hover:bg-orange-700">
                {{ __('Confirm') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
