<x-layouts::app :title="__('Dashboard')">
    <div class="max-w-7xl mx-auto">
        <livewire:pages::stats.commande />

        <div class="mt-3">
            <livewire:pages::stats.historique-stock />
        </div>

    </div>
</x-layouts::app>
