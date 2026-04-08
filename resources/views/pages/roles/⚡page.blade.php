<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="max-w-7xl mx-auto">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Gestion des rôles et permissions') }}</flux:heading>
        <flux:text class="mb-6 mt-2 text-base">
            {{ __('Administrez les rôles et définissez les permissions pour contrôler l’accès.') }}
        </flux:text>
        <flux:separator variant="subtle" />
    </div>


    <livewire:pages::roles.list />
</div>
