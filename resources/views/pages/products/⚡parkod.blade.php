<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<flux:modal name="importer-parkod" flyout>
    <div class="space-y-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Import PARKOD') }}</flux:heading>
            <flux:subheading size="lg" class="mb-6">
                {{ __('Importez et gérez les données PARKOD sur la plateforme ERP') }}
            </flux:subheading>
        </div>

        <livewire:pages::parkod.import />
    </div>
</flux:modal>
