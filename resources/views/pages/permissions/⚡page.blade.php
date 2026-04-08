<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="max-w-7xl mx-auto">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Gestion des permissions') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">
            {{ __('Configurez et contrôlez les autorisations d’accès aux différentes fonctionnalités de la plateforme ERP.') }}
        </flux:subheading>
    </div>

    <livewire:pages::permissions.list />
</div>
