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

    public function mount(int $commande_id): void
    {
        $this->commandeId = $commande_id;
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

    #[\Livewire\Attributes\Computed]
    public function historique()
    {
        return \App\Models\HistoriqueQuantiteDetailCommande::query()
            ->with(['product', 'user'])
            ->where('commande_id', $this->commandeId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function formatCurrency(?float $amount): string
    {
        return app(\App\Services\CurrencyService::class)->format($amount);
    }

    public function updateStatus(): void
    {
        $commande = $this->commande;
        $nextStatus = match($commande->status) {
            \App\Enums\CommandeStatus::Cree     => \App\Enums\CommandeStatus::Facturee,
            \App\Enums\CommandeStatus::Facturee => \App\Enums\CommandeStatus::Cloturee,
            \App\Enums\CommandeStatus::Cloturee => \App\Enums\CommandeStatus::Recue,
            default => null,
        };

        if ($nextStatus === null) return;

        $commande->update(['status' => $nextStatus]);
        $this->unsetComputedProperties(['commande']);

        \Flux\Flux::toast(
            heading: 'Statut mis à jour',
            text: "La commande est maintenant « {$nextStatus->label()} ».",
            variant: 'success'
        );
    }

    #[\Livewire\Attributes\Computed]
    public function isEditable(): bool
    {
        return !in_array($this->commande->status, [
            \App\Enums\CommandeStatus::Cloturee,
            \App\Enums\CommandeStatus::Recue,
            \App\Enums\CommandeStatus::Annulee,
        ]);
    }
};
?>

<div class="max-w-5xl mx-auto">
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('orders.list') }}" wire:navigate>Commande</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Détails</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- En-tête façon "Order #3001" --}}
    <div class="flex flex-col gap-4 mb-1 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex flex-wrap items-center gap-3">
            <flux:heading size="xl" level="1" class="break-words">
                Commande {{ $this->commande->libelle ?? '#'.$this->commande->id }}
            </flux:heading>
            <flux:badge size="sm" color="lime">
                {{ $this->commande->status?->label() ?? $this->commande->status }}
            </flux:badge>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button size="sm" variant="danger" wire:click="delete" wire:confirm="Êtes-vous sûr de vouloir supprimer cette commande ?" :disabled="!$this->isEditable" title="Supprimer">
                <i class="hgi-stroke hgi-delete-02"></i>
            </flux:button>

            @if($this->isEditable)
                <flux:button size="sm" variant="primary" href="{{ route('orders.edit', ['commande_id' => $this->commandeId]) }}" wire:navigate title="Modifier">
                    <i class="hgi-stroke hgi-pencil-edit-01"></i>
                </flux:button>
            @else
                <flux:button size="sm" variant="primary" disabled title="Modifier">
                    <i class="hgi-stroke hgi-pencil-edit-01"></i>
                </flux:button>
            @endif

            @php
                $nextStatus = match($this->commande->status) {
                    \App\Enums\CommandeStatus::Cree     => \App\Enums\CommandeStatus::Facturee,
                    \App\Enums\CommandeStatus::Facturee => \App\Enums\CommandeStatus::Cloturee,
                    \App\Enums\CommandeStatus::Cloturee => \App\Enums\CommandeStatus::Recue,
                    default => null,
                };
            @endphp

            @if($nextStatus)
                <flux:button
                    size="sm"
                    variant="primary"
                    color="violet"
                    wire:click="updateStatus"
                    wire:confirm="Passer le statut à « {{ $nextStatus->label() }} » ?"
                >
                    <i class="hgi-stroke hgi-arrow-right-02"></i>
                    {{ $nextStatus->label() }}
                </flux:button>
            @endif

            <flux:button size="sm" href="{{ route('bon-commande.pdf', $commandeId) }}" target="_blank" title="Bon de commande">
                <i class="hgi-stroke hgi-pdf-01"></i>
            </flux:button>

            @if($this->commande->status === \App\Enums\CommandeStatus::Cloturee)
                <flux:button
                    size="sm"
                    variant="primary"
                    color="lime"
                    href="{{ route('reception_commande.create', ['commande' => $this->commandeId]) }}"
                    wire:navigate
                    title="Passer à la réception"
                >
                    <i class="hgi-stroke hgi-truck-01"></i>
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Ligne d'infos rapides façon icônes --}}
    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mb-8 mt-2 text-sm text-zinc-600 dark:text-zinc-300">
        <div class="flex items-center gap-1.5">
            <i class="hgi-stroke hgi-money-bag-01 text-zinc-400"></i>
            {{ $this->formatCurrency($this->commande->montant_total) }}
        </div>
        <div class="flex items-center gap-1.5">
            <i class="hgi-stroke hgi-building-01 text-zinc-400"></i>
            {{ $this->commande->fournisseur?->name ?? '—' }}
        </div>
        <div class="flex items-center gap-1.5">
            <i class="hgi-stroke hgi-calendar-03 text-zinc-400"></i>
            {{ $this->commande->created_at?->format('d M Y') }}
        </div>
    </div>

    {{-- Résumé --}}
    <flux:heading size="sm" class="mb-2">Résumé</flux:heading>
    <div class="mb-8">
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>Référence</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->commande->libelle ?? '—' }}</span>
        </div>
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>Fournisseur</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->commande->fournisseur?->name ?? '—' }}</span>
        </div>
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>Statut</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->commande->status?->label() ?? $this->commande->status }}</span>
        </div>
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>État</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->commande->etat?->label() ?? $this->commande->etat }}</span>
        </div>
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>Remise facture</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->commande->remise_facture ?? '0' }} %</span>
        </div>
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>Montant minimum</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->formatCurrency($this->commande->montant_minimum) }}</span>
        </div>
        <div class="flex justify-between items-center py-3">
            <flux:subheading>Montant total</flux:subheading>
            <span class="font-bold text-zinc-900 dark:text-white">{{ $this->formatCurrency($this->commande->montant_total) }}</span>
        </div>
    </div>

    {{-- Fournisseur --}}
    <flux:heading size="sm" class="mb-2">Fournisseur</flux:heading>
    <div class="mb-8">
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>Nom</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->commande->fournisseur?->name ?? '—' }}</span>
        </div>
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>Code</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->commande->fournisseur?->code ?? '—' }}</span>
        </div>
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>Raison sociale</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->commande->fournisseur?->raison_social ?? '—' }}</span>
        </div>
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>Adresse</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100 text-right">
                {{ $this->commande->fournisseur?->adresse_siege ?? '—' }}
                @if($this->commande->fournisseur?->code_postal || $this->commande->fournisseur?->ville)
                    <br>
                    <span class="text-sm text-zinc-500">{{ $this->commande->fournisseur?->code_postal }} {{ $this->commande->fournisseur?->ville }}</span>
                @endif
            </span>
        </div>
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>Téléphone</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->commande->fournisseur?->telephone ?? '—' }}</span>
        </div>
        <div class="flex justify-between items-center py-3">
            <flux:subheading>Email</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->commande->fournisseur?->mail ?? '—' }}</span>
        </div>
    </div>

    {{-- Magasin de livraison --}}
    <flux:heading size="sm" class="mb-2">Magasin de livraison</flux:heading>
    <div class="mb-8">
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>Nom</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->commande->magasinLivraison?->name ?? '—' }}</span>
        </div>
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>Type</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->commande->magasinLivraison?->type ?? '—' }}</span>
        </div>
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>Adresse</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->commande->magasinLivraison?->adress ?? '—' }}</span>
        </div>
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>Téléphone</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->commande->magasinLivraison?->telephone ?? '—' }}</span>
        </div>
        <div class="flex justify-between items-center py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:subheading>Email</flux:subheading>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->commande->magasinLivraison?->email ?? '—' }}</span>
        </div>
        <div class="flex justify-between items-center py-3">
            <flux:subheading>URL Store</flux:subheading>
            @if($this->commande->magasinLivraison?->store_url)
                <a href="{{ $this->commande->magasinLivraison->store_url }}" target="_blank" class="text-blue-500 hover:underline text-sm font-medium">
                    Voir le store
                </a>
            @else
                <span class="text-zinc-400">—</span>
            @endif
        </div>
    </div>

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
                            {{  $this->formatCurrency($detail->pu_achat_HT) }}
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
                            {{ $this->formatCurrency($detail->pu_achat_net) }}
                        </flux:table.cell>

                        {{-- Montant ligne = quantite × pu_achat_net --}}
                        <flux:table.cell class="whitespace-nowrap" variant="strong">
                            {{  $this->formatCurrency($detail->quantite * $detail->pu_achat_net) }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Historique des modifications --}}
    <flux:card class="mt-5">
        <flux:heading size="lg" class="mb-4">Historique des modifications</flux:heading>

        <flux:table :paginate="$this->historique">
            <flux:table.columns>
                <flux:table.column>Produit</flux:table.column>
                <flux:table.column>Ancienne qté</flux:table.column>
                <flux:table.column>Nouvelle qté</flux:table.column>
                <flux:table.column>Variation</flux:table.column>
                <flux:table.column>Motif</flux:table.column>
                <flux:table.column>Modifié par</flux:table.column>
                <flux:table.column>Date</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->historique as $h)
                    <flux:table.row :key="$h->id">
                        {{-- Produit --}}
                        <flux:table.cell>
                            <div class="flex flex-col">
                            <span class="font-medium text-zinc-800 dark:text-zinc-100">
                                {{ $h->product?->designation ?? '—' }}
                            </span>
                                <span class="text-xs text-zinc-500">
                                {{ $h->product?->product_code ?? '' }}
                            </span>
                            </div>
                        </flux:table.cell>

                        {{-- Ancienne quantité --}}
                        <flux:table.cell>
                            {{ $h->ancienne_quantite }}
                        </flux:table.cell>

                        {{-- Nouvelle quantité --}}
                        <flux:table.cell variant="strong">
                            {{ $h->nouvelle_quantite }}
                        </flux:table.cell>

                        {{-- Variation --}}
                        <flux:table.cell>
                            @php $diff = $h->nouvelle_quantite - $h->ancienne_quantite; @endphp
                            <flux:badge
                                size="sm"
                                inset="top bottom"
                                color="{{ $diff > 0 ? 'green' : ($diff < 0 ? 'red' : 'zinc') }}"
                            >
                                {{ $diff > 0 ? '+' : '' }}{{ $diff }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Motif --}}
                        <flux:table.cell>
                            {{ $h->motif ?? '—' }}
                        </flux:table.cell>

                        {{-- Utilisateur --}}
                        <flux:table.cell>
                            {{ $h->user?->name ?? '—' }}
                        </flux:table.cell>

                        {{-- Date --}}
                        <flux:table.cell class="whitespace-nowrap text-zinc-500 text-sm">
                            {{ $h->created_at?->format('d/m/Y H:i') }}
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center text-zinc-400 py-6">
                            Aucun historique de modification.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
