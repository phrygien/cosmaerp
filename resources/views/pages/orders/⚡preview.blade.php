<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\Commande;

new class extends Component
{
    public int $commande_id;

    #[Computed]
    public function commande()
    {
        return Commande::with([
            'fournisseur',
            'magasinLivraison',
        ])->findOrFail($this->commande_id);
    }

    #[Computed]
    public function details()
    {
        return \App\Models\DetailCommande::where('commande_id', $this->commande_id)
            ->with(['product.marque', 'product.categorie', 'destinations.magasin'])
            ->get();
    }

    #[Computed]
    public function totalHT(): float
    {
        return $this->details->sum(fn($d) => $d->pu_achat_HT * $d->quantite);
    }

    #[Computed]
    public function totalNet(): float
    {
        return $this->details->sum(fn($d) => $d->pu_achat_net * $d->quantite);
    }

    #[Computed]
    public function totalTax(): float
    {
        return $this->details->sum(fn($d) => ($d->pu_achat_net * $d->tax / 100) * $d->quantite);
    }

    #[Computed]
    public function totalTTC(): float
    {
        return $this->totalNet + $this->totalTax;
    }

    #[Computed]
    public function totalArticles(): int
    {
        return $this->details->sum('quantite');
    }
};
?>

<div class="space-y-8">

    {{-- ── En-tête récapitulatif ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Fournisseur</p>
            <p class="font-semibold text-gray-900 dark:text-white">
                {{ $this->commande->fournisseur?->name ?? '—' }}
            </p>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Magasin de livraison</p>
            <p class="font-semibold text-gray-900 dark:text-white">
                {{ $this->commande->magasinLivraison?->name ?? '—' }}
            </p>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Remise facture</p>
            <p class="font-semibold text-gray-900 dark:text-white">
                {{ $this->commande->remise_facture ?? 0 }} %
            </p>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Délai livraison</p>
            <p class="font-semibold text-gray-900 dark:text-white">
                {{ $this->commande->nombre_jour ?? 0 }} jour(s)
            </p>
        </div>
    </div>

    {{-- ── Tableau des lignes de commande ── --}}
    <div>
        <flux:heading size="lg" class="mb-4">Lignes de commande</flux:heading>

        @if($this->details->isEmpty())
            <div class="rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-700 py-16 text-center text-gray-400">
                <svg class="size-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm">Aucune ligne de commande</p>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Produit</flux:table.column>
                    <flux:table.column>Marque</flux:table.column>
                    <flux:table.column>PU Achat HT</flux:table.column>
                    <flux:table.column>Remise</flux:table.column>
                    <flux:table.column>PU Net</flux:table.column>
                    <flux:table.column>TVA</flux:table.column>
                    <flux:table.column>Qté totale</flux:table.column>
                    <flux:table.column>Total Net HT</flux:table.column>
                    <flux:table.column>Répartition</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach($this->details as $detail)
                        <flux:table.row :key="$detail->id">

                            {{-- Produit --}}
                            <flux:table.cell>
                                <p class="font-medium text-gray-900 dark:text-white text-sm">
                                    {{ $detail->product->designation }}
                                </p>
                                <p class="text-xs text-gray-400 font-mono">
                                    {{ $detail->product->product_code }}
                                </p>
                            </flux:table.cell>

                            {{-- Marque --}}
                            <flux:table.cell>
                                @if($detail->product->marque)
                                    <flux:badge size="sm" color="blue" inset="top bottom">
                                        {{ $detail->product->marque->name }}
                                    </flux:badge>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </flux:table.cell>

                            {{-- PU HT --}}
                            <flux:table.cell variant="strong">
                                {{ number_format($detail->pu_achat_HT, 4) }}
                            </flux:table.cell>

                            {{-- Remise --}}
                            <flux:table.cell>
                                @if($detail->taux_remise > 0)
                                    <flux:badge size="sm" color="amber" inset="top bottom">
                                        {{ $detail->taux_remise }} %
                                    </flux:badge>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </flux:table.cell>

                            {{-- PU Net --}}
                            <flux:table.cell variant="strong">
                                {{ number_format($detail->pu_achat_net, 4) }}
                            </flux:table.cell>

                            {{-- TVA --}}
                            <flux:table.cell>
                                <span class="text-xs text-gray-500">{{ $detail->tax }} %</span>
                            </flux:table.cell>

                            {{-- Quantité --}}
                            <flux:table.cell variant="strong">
                                <span class="tabular-nums">{{ $detail->quantite }}</span>
                            </flux:table.cell>

                            {{-- Total Net HT --}}
                            <flux:table.cell variant="strong">
                                <span class="tabular-nums text-indigo-600 dark:text-indigo-400">
                                    {{ number_format($detail->pu_achat_net * $detail->quantite, 2) }}
                                </span>
                            </flux:table.cell>

                            {{-- Répartition par magasin --}}
                            <flux:table.cell>
                                <div class="space-y-1 min-w-32">
                                    @foreach($detail->destinations as $dest)
                                        <div class="flex items-center justify-between gap-3 text-xs">
                                            <span class="text-gray-500 truncate max-w-24">
                                                {{ $dest->magasin->name ?? '—' }}
                                            </span>
                                            <flux:badge size="sm" color="zinc" inset="top bottom">
                                                {{ $dest->quantite }}
                                            </flux:badge>
                                        </div>
                                    @endforeach
                                </div>
                            </flux:table.cell>

                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    {{-- ── Totaux ── --}}
    @if($this->details->isNotEmpty())
        <div class="flex justify-end">
            <div class="w-full max-w-sm space-y-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">

                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span>Total articles</span>
                    <span class="font-semibold tabular-nums text-gray-900 dark:text-white">
                        {{ $this->totalArticles }}
                    </span>
                </div>

                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span>Total HT brut</span>
                    <span class="font-semibold tabular-nums text-gray-900 dark:text-white">
                        {{ number_format($this->totalHT, 2) }}
                    </span>
                </div>

                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span>Total HT net (après remises)</span>
                    <span class="font-semibold tabular-nums text-gray-900 dark:text-white">
                        {{ number_format($this->totalNet, 2) }}
                    </span>
                </div>

                @if($this->commande->remise_facture > 0)
                    <div class="flex justify-between text-sm text-amber-600 dark:text-amber-400">
                        <span>Remise facture ({{ $this->commande->remise_facture }} %)</span>
                        <span class="font-semibold tabular-nums">
                            - {{ number_format($this->totalNet * $this->commande->remise_facture / 100, 2) }}
                        </span>
                    </div>
                @endif

                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span>Total TVA</span>
                    <span class="font-semibold tabular-nums text-gray-900 dark:text-white">
                        {{ number_format($this->totalTax, 2) }}
                    </span>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between">
                    <span class="font-semibold text-gray-900 dark:text-white">Total TTC</span>
                    <span class="text-lg font-bold tabular-nums text-indigo-600 dark:text-indigo-400">
                        {{ number_format($this->totalTTC, 2) }}
                    </span>
                </div>
            </div>
        </div>
    @endif

</div>
