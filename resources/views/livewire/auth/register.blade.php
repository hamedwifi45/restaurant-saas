<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-7">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-4">
            @csrf
            <div class="grid gap-2">
                <label for="name" class="text-sm font-bold text-zinc-700">{{ __('Name') }}</label>
                <input id="name" name="name" value="{{ old('name') }}" type="text" required autofocus autocomplete="name" placeholder="{{ __('Full name') }}" class="w-full rounded-xl border border-zinc-200 px-4 py-3 outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10">
                @error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-2">
                <label for="email" class="text-sm font-bold text-zinc-700">{{ __('Email address') }}</label>
                <input id="email" name="email" value="{{ old('email') }}" type="email" required autocomplete="email" placeholder="email@example.com" class="w-full rounded-xl border border-zinc-200 px-4 py-3 outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10">
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
            <button type="submit" data-test="register-user-button" class="mt-1 w-full rounded-xl bg-orange-600 px-5 py-3.5 font-black text-white transition hover:bg-orange-700">
                {{ __('Create account') }}
            </button>
        </form>

        <p class="text-center text-sm text-zinc-600">
            {{ __('Already have an account?') }}
            <a href="{{ route('login') }}" class="font-bold text-orange-700">{{ __('Log in') }}</a>
        </p>
    </div>
</x-layouts::auth>
