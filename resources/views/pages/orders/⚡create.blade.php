<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="max-w-7xl">
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">Commande</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Nouvelle</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <!-- Heading + bouton -->
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Nouvelle Commande') }}</flux:heading>
    </div>

</div>
