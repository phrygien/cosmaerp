<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Commande;

new class extends Component
{
    use WithPagination;

    public int $commandeId;
    public string $sortBy = 'id';
    public string $sortDirection = 'asc';

    public function mount(int $id): void
    {
        $this->commandeId = $id;
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function commande(): Commande
    {
        return Commande::with(['fournisseur', 'magasinLivraison'])->findOrFail($this->commandeId);
    }

    #[\Livewire\Attributes\Computed]
    public function details()
    {
        return \App\Models\DetailCommande::query()
            ->with('product')
            ->where('commande_id', $this->commandeId)
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(10);
    }
};
?>

<div class="max-w-7xl mx-auto">
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">Commande</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Détails</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Précommande') }}</flux:heading>

        <flux:button variant="primary" class="w-full sm:w-auto" href="{{ route('orders.create') }}" wire:navigate>
            Bon de commande
        </flux:button>
    </div>

    {{-- Informations générales de la commande --}}
    <flux:card class="mt-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
                <flux:subheading>Référence</flux:subheading>
                <p class="mt-1 font-semibold text-zinc-800 dark:text-zinc-100">
                    {{ $this->commande->libelle ?? '—' }}
                </p>
            </div>
            <div>
                <flux:subheading>Fournisseur</flux:subheading>
                <p class="mt-1 font-semibold text-zinc-800 dark:text-zinc-100">
                    {{ $this->commande->fournisseur?->name ?? '—' }}
                </p>
            </div>
            <div>
                <flux:subheading>Magasin de livraison</flux:subheading>
                <p class="mt-1 font-semibold text-zinc-800 dark:text-zinc-100">
                    {{ $this->commande->magasinLivraison?->name ?? '—' }}
                </p>
            </div>
            <div>
                <flux:subheading>Montant total</flux:subheading>
                <p class="mt-1 font-semibold text-zinc-800 dark:text-zinc-100">
                    {{ number_format($this->commande->montant_total, 2, ',', ' ') }} €
                </p>
            </div>
            <div>
                <flux:subheading>Statut</flux:subheading>
                <div class="mt-1">
                    <flux:badge size="sm" color="blue">
                        {{ $this->commande->status?->label() ?? $this->commande->status }}
                    </flux:badge>
                </div>
            </div>
            <div>
                <flux:subheading>État</flux:subheading>
                <div class="mt-1">
                    <flux:badge size="sm" color="zinc">
                        {{ $this->commande->etat?->label() ?? $this->commande->etat }}
                    </flux:badge>
                </div>
            </div>
            <div>
                <flux:subheading>Remise facture</flux:subheading>
                <p class="mt-1 font-semibold text-zinc-800 dark:text-zinc-100">
                    {{ $this->commande->remise_facture ?? '0' }} %
                </p>
            </div>
            <div>
                <flux:subheading>Montant minimum</flux:subheading>
                <p class="mt-1 font-semibold text-zinc-800 dark:text-zinc-100">
                    {{ number_format($this->commande->montant_minimum, 2, ',', ' ') }} €
                </p>
            </div>
        </div>
    </flux:card>

    {{-- Tableau des lignes de commande --}}
    <flux:card class="mt-5">
        <flux:heading size="lg" class="mb-4">Lignes de commande</flux:heading>

        <flux:table :paginate="$this->details">
            <flux:table.columns>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'product_id'"
                    :direction="$sortDirection"
                    wire:click="sort('product_id')"
                >
                    Produit
                </flux:table.column>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'quantite'"
                    :direction="$sortDirection"
                    wire:click="sort('quantite')"
                >
                    Quantité
                </flux:table.column>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'pu_achat_HT'"
                    :direction="$sortDirection"
                    wire:click="sort('pu_achat_HT')"
                >
                    PU Achat HT
                </flux:table.column>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'taux_remise'"
                    :direction="$sortDirection"
                    wire:click="sort('taux_remise')"
                >
                    Remise
                </flux:table.column>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'tax'"
                    :direction="$sortDirection"
                    wire:click="sort('tax')"
                >
                    TVA
                </flux:table.column>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'pu_achat_net'"
                    :direction="$sortDirection"
                    wire:click="sort('pu_achat_net')"
                >
                    PU Net
                </flux:table.column>
                <flux:table.column>Montant ligne</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->details as $detail)
                    <flux:table.row :key="$detail->id">
                        {{-- Produit --}}
                        <flux:table.cell>
                            <div class="flex flex-col">
                                <span class="font-medium text-zinc-800 dark:text-zinc-100">
                                    {{ $detail->product?->designation ?? '—' }}
                                </span>
                                <span class="text-xs text-zinc-500">
                                    {{ $detail->product?->product_code ?? '' }}
                                </span>
                            </div>
                        </flux:table.cell>

                        {{-- Quantité --}}
                        <flux:table.cell variant="strong">
                            {{ $detail->quantite }}
                        </flux:table.cell>

                        {{-- PU Achat HT --}}
                        <flux:table.cell class="whitespace-nowrap">
                            {{ number_format($detail->pu_achat_HT, 2, ',', ' ') }} €
                        </flux:table.cell>

                        {{-- Remise --}}
                        <flux:table.cell>
                            <flux:badge size="sm" color="amber" inset="top bottom">
                                {{ $detail->taux_remise ?? 0 }} %
                            </flux:badge>
                        </flux:table.cell>

                        {{-- TVA --}}
                        <flux:table.cell class="whitespace-nowrap">
                            {{ $detail->tax ?? 0 }} %
                        </flux:table.cell>

                        {{-- PU Net --}}
                        <flux:table.cell class="whitespace-nowrap" variant="strong">
                            {{ number_format($detail->pu_achat_net, 2, ',', ' ') }} €
                        </flux:table.cell>

                        {{-- Montant ligne = quantite × pu_achat_net --}}
                        <flux:table.cell class="whitespace-nowrap" variant="strong">
                            {{ number_format($detail->quantite * $detail->pu_achat_net, 2, ',', ' ') }} €
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
