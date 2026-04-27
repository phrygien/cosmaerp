<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="max-w-7xl mx-auto">
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">Commande</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Details</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Précommande') }}</flux:heading>

        <flux:button variant="primary" class="w-full sm:w-auto" href="{{ route('orders.create') }}" wire:navigate>
            Bon de commande
        </flux:button>
    </div>

    <flux:card class="mt-5">

    </flux:card>

    <flux:card class="mt-5">

    </flux:card>
</div>
