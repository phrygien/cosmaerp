<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
    <link rel="stylesheet" href="https://cdn.hugeicons.com/font/hgi-stroke-rounded.css" />
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
<flux:sidebar sticky collapsible class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
    <flux:sidebar.header>
        <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
        <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
    </flux:sidebar.header>

    <flux:sidebar.nav>
        <flux:sidebar.item
            :href="route('dashboard')"
            :current="request()->routeIs('dashboard')"
            wire:navigate
        >
            <x-slot name="icon"><i class="hgi-stroke hgi-home-01 text-2xl"></i></x-slot>
            {{ __('Dashboard') }}
        </flux:sidebar.item>

        <flux:sidebar.spacer />

        <flux:sidebar.group expandable expanded heading="Administration" class="grid">
            <x-slot name="icon"><i class="hgi-stroke hgi-settings-01 text-2xl"></i></x-slot>
            <flux:sidebar.item
                href="{{ route('permissions') }}"
                :current="request()->routeIs('permissions*') || request()->is('permissions*')"
                wire:navigate
            >{{ __('Permissions') }}</flux:sidebar.item>
            <flux:sidebar.item
                href="{{ route('roles') }}"
                :current="request()->routeIs('roles*') || request()->is('roles*')"
                wire:navigate
            >{{ __('Roles') }}</flux:sidebar.item>
            <flux:sidebar.item
                href="{{ route('users') }}"
                :current="request()->routeIs('users*') || request()->is('users*')"
                wire:navigate
            >{{ __('Utilisateurs') }}</flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.group expandable expanded heading="Catalogues" class="grid">
            <x-slot name="icon"><i class="hgi-stroke hgi-layers-01 text-2xl"></i></x-slot>
            <flux:sidebar.item
                href="{{ route('catalogue.marques') }}"
                :current="request()->routeIs('catalogue.marques*') || request()->is('catalogue/marques*')"
                wire:navigate
            >{{ __('Marques') }}</flux:sidebar.item>
            <flux:sidebar.item
                href="{{ route('catalogue.categories') }}"
                :current="request()->routeIs('catalogue.categories*') || request()->is('catalogue/categories*')"
                wire:navigate
            >{{ __('Categories') }}</flux:sidebar.item>
            <flux:sidebar.item
                href="{{ route('catalogue.parkod') }}"
                :current="request()->routeIs('catalogue.parkod*') || request()->is('catalogue/parkod*')"
                wire:navigate
            >{{ __('PARKOD') }}</flux:sidebar.item>
            <flux:sidebar.item
                href="{{ route('catalogue.products') }}"
                :current="request()->routeIs('catalogue.products*') || request()->is('catalogue/products*')"
                wire:navigate
            >{{ __('Produits') }}</flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.item
            :href="route('fournisseurs')"
            :current="request()->routeIs('fournisseurs*') || request()->is('fournisseurs*')"
            wire:navigate
        >
            <x-slot name="icon"><i class="hgi hgi-stroke hgi-rounded hgi-building-05 text-2xl"></i></x-slot>
            {{ __('Fournisseurs') }}
        </flux:sidebar.item>

        <flux:sidebar.item
            :href="route('magasin')"
            :current="request()->routeIs('magasin*') || request()->is('magasin*')"
            wire:navigate
        >
            <x-slot name="icon"><i class="hgi-stroke hgi-store-01 text-2xl"></i></x-slot>
            {{ __('Dépôt') }}
        </flux:sidebar.item>

        <flux:sidebar.group expandable expanded heading="Précommande" class="grid">
            <x-slot name="icon"><i class="hgi-stroke hgi-shopping-cart-01 text-2xl"></i></x-slot>
            <flux:sidebar.item
                href="{{ route('orders.list') }}"
                :current="request()->routeIs('orders.list') || (request()->is('orders*') && !request()->is('orders/create*'))"
                wire:navigate
            >{{ __('Liste') }}</flux:sidebar.item>
            <flux:sidebar.item
                href="{{ route('orders.create') }}"
                :current="request()->routeIs('orders.create*') || request()->is('orders/create*')"
                wire:navigate
            >{{ __('Nouvelle') }}</flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.spacer />

        <flux:sidebar.group expandable expanded heading="Réception" class="grid">
            <x-slot name="icon"><i class="hgi-stroke hgi-folder-01 text-2xl"></i></x-slot>
            <flux:sidebar.item
                href="{{ route('reception_commande.list') }}"
                :current="request()->routeIs('reception_commande.list') || (request()->is('reception*') && !request()->is('reception/create*'))"
                wire:navigate
            >{{ __('Historique réception') }}</flux:sidebar.item>
            <flux:sidebar.item
                href="{{ route('reception_commande.create') }}"
                :current="request()->routeIs('reception_commande.create*') || request()->is('reception/create*')"
                wire:navigate
            >{{ __('Nouvelle réception') }}</flux:sidebar.item>
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
                <flux:menu.item :href="route('profile.edit')" wire:navigate>
                    {{ __('Paramètres') }}
                </flux:menu.item>
            </flux:menu.radio.group>

            <flux:menu.separator />

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item
                    as="button"
                    type="submit"
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
