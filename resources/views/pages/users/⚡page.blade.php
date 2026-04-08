<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="max-w-7xl mx-auto">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1" icon="users">{{ __('Gestion des utilisateurs') }}</flux:heading>
        <flux:text class="mb-6 mt-2 text-base">
            {{ __('Administrez les comptes utilisateurs et gérez leurs accès au sein de la plateforme ERP.') }}
        </flux:text>
        <flux:separator variant="subtle" />
    </div>


    <livewire:pages::users.list />
</div>
