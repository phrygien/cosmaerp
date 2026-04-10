<x-layouts::auth :title="__('Inscription')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Créer un compte')" :description="__('Entrez vos coordonnées ci-dessous pour créer votre compte')" />

        <!-- État de la session -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Nom -->
            <flux:input
                name="name"
                :label="__('Nom')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Nom complet')"
            />

            <!-- Adresse email -->
            <flux:input
                name="email"
                :label="__('Adresse email')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@exemple.com"
            />

            <!-- Mot de passe -->
            <flux:input
                name="password"
                :label="__('Mot de passe')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Mot de passe')"
                viewable
            />

            <!-- Confirmer le mot de passe -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirmer le mot de passe')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirmer le mot de passe')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Créer le compte') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Vous avez déjà un compte ?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Connexion') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
