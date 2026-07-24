<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-2 mt-2">
        <flux:input icon="magnifying-glass" placeholder="Search orders" />
    </div>

    <div class="grid grid-cols-2 gap-3">
        <flux:card>

        </flux:card>

        <flux:card>
            <livewire:pages::pos.items />
        </flux:card>
    </div>
</div>
