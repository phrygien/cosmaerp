<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="max-w-7xl mx-auto">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Catégorie') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">
            {{ __('Gérez et configurez les catégories disponibles sur la plateforme ERP') }}
        </flux:subheading>
    </div>

    <livewire:pages::categories.list />
</div>
