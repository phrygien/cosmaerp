<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
<flux:sidebar sticky collapsible class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
    <flux:sidebar.header>
        <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
        <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
    </flux:sidebar.header>

    <flux:sidebar.nav>
        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
            {{ __('Dashboard') }}
        </flux:sidebar.item>

        <flux:sidebar.spacer />

        <flux:sidebar.group expandable expanded icon="adjustments-horizontal" heading="Administration" class="grid">
            <flux:sidebar.item href="{{ route('permissions') }}" wire:navigate>{{ __('Permissions') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('roles') }}" wire:navigate>{{ __('Roles') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('users') }}" wire:navigate>{{ __('Utilisateurs') }}</flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.group expandable expanded icon="rectangle-stack" heading="Catalogues" class="grid">
            <flux:sidebar.item href="{{ route('catalogue.marques') }}" wire:navigate>{{ __('Marques') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('catalogue.categories') }}" wire:navigate>{{ __('Categories') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('catalogue.parkod') }}" wire:navigate>{{ __('PARKOD') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('catalogue.products') }}" wire:navigate>{{ __('Produits') }}</flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.item icon="users" :href="route('fournisseurs')" :current="request()->routeIs('fournisseurs')" wire:navigate>
            {{ __('Fournisseurs') }}
        </flux:sidebar.item>

        <flux:sidebar.item icon="building-storefront" :href="route('magasin')" :current="request()->routeIs('magasin')" wire:navigate>
            {{ __('Dépôt') }}
        </flux:sidebar.item>

        <flux:sidebar.group expandable expanded icon="shopping-cart" heading="Précommande" class="grid">
            <flux:sidebar.item href="{{ route('orders.list') }}" wire:navigate>{{ __('Commandes') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('orders.create') }}" wire:navigate>{{ __('Nouvelle') }}</flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.spacer />

        <flux:sidebar.group expandable expanded icon="queue-list" heading="Réception" class="grid">
            <flux:sidebar.item href="{{ route('reception_commande.list') }}" wire:navigate>{{ __('Historique réception') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('reception_commande.create') }}" wire:navigate>{{ __('Nouvelle réception') }}</flux:sidebar.item>
        </flux:sidebar.group>

    </flux:sidebar.nav>

    <flux:sidebar.spacer />

    <x-desktop-user-menu class="sm:hidden sm:block" :name="auth()->user()->name" />
</flux:sidebar>

<!-- Mobile User Menu -->
<flux:header class="lg:hidden">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

    <flux:spacer />

    <flux:dropdown position="top" align="end">
        <flux:profile
            :initials="auth()->user()->initials()"
            icon-trailing="chevron-down"
        />

        <flux:menu>
            <flux:menu.radio.group>
                <div class="p-0 text-sm font-normal">
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                        <flux:avatar
                            :name="auth()->user()->name"
                            :initials="auth()->user()->initials()"
                        />

                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                            <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                        </div>
                    </div>
                </div>
            </flux:menu.radio.group>

            <flux:menu.separator />

            <flux:menu.radio.group>
                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                    {{ __('Paramètres') }}
                </flux:menu.item>
            </flux:menu.radio.group>

            <flux:menu.separator />

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer"
                    data-test="logout-button"
                >
                    {{ __('Déconnexion') }}
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</flux:header>

{{ $slot }}

@fluxScripts

@persist('toast')
<flux:toast position="top end" />
@endpersist
</body>
</html>
