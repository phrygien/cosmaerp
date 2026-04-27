<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="max-w-7xl mx-auto">
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">Précommande</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Nouvelle</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Nouvelle précommande') }}</flux:heading>

        {{-- Bouton Annuler global --}}
        <flux:button wire:click="confirmAnnuler" variant="danger" icon="x-circle">
            Annuler
        </flux:button>
    </div>

</div>
