<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="w-full mx-auto">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Produit') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">
            {{ __('Gérez et configurez les produits disponibles sur la plateforme Boutiques.') }}
        </flux:subheading>
    </div>

    <livewire:pages::products.list />
</div>
