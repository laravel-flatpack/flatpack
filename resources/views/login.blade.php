<x-flatpack-guest-layout>
    <x-card>
        <!-- Session Status -->
        <x-flatpack::auth-session-status class="mb-4" :status="session('status')" />

        <!-- Validation Errors -->
        <x-flatpack::auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('flatpack.login') }}">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input
                    id="email"
                    type="email"
                    name="email"
                    label="{{ __('Email') }}"
                    placeholder="{{ __('Your e-mail address') }}"
                    :value="old('email')"
                    required
                    autofocus
                />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-input
                    id="password"
                    type="password"
                    name="password"
                    label="{{ __('Password') }}"
                    placeholder="{{ __('Your password') }}"
                    required
                    autocomplete="current-password"
                />
            </div>

            <!-- Remember Me -->
            <div class="block mt-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="border-gray-300 rounded shadow-sm text-primary-300 focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50" name="remember">
                    <span class="ml-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button
                    primary
                    label="{{ __('Log in') }}"
                    type="submit"
                />
            </div>
        </form>
    </x-card>
</x-flatpack-guest-layout>
