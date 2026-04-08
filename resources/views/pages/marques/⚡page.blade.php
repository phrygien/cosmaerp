<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="max-w-7xl mx-auto">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Marques') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">
            {{ __('Gérez et configurez les marques disponibles sur la plateforme ERP') }}
        </flux:subheading>
    </div>

    <livewire:pages::marques.list />
</div>
