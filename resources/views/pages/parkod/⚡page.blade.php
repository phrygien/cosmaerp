<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="max-w-7xl mx-auto">

    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">PARKOD</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Importer</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Import Parkod') }}</flux:heading>
    </div>

    <livewire:pages::parkod.import />
</div>
