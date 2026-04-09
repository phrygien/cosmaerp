<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="mt-5">

    <div>
        <flux:field class="mb-5">
            <flux:input type="file" wire:model="attachments" label="Fichiers PARKOD" multiple />
        </flux:field>

        <div class="mt-5">
            <flux:button variant="primary">Importer les fichiers</flux:button>
            <flux:button variant="danger">Annuler</flux:button>
        </div>
    </div>
</div>
