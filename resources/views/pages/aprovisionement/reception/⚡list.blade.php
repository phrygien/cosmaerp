<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="mt-5">
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">{{ __('Réception des commandes') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Liste') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Réception des commandes') }}</flux:heading>
    </div>
</div>
