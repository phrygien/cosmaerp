<?php

use Livewire\Component;
use App\Models\ReceptionCommande;
use App\Models\BonCommande;
use App\Enums\CommandeStatus;

new class extends Component
{
    public ReceptionCommande $reception;
    public ?BonCommande $bon = null;

    public function mount(ReceptionCommande $reception): void
    {
        $this->reception = $reception->load([
            'bon_commande.commande.fournisseur',
            'bon_commande.commande.magasinLivraison',
            'bon_commande.magasinLivraison',
            'bon_commande.receptions.detail_commande.product',
        ]);

        $this->bon = $this->reception->bon_commande;
    }
};
?>

<div class="max-w-7xl mx-auto">
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">{{ __('Approvisionnement') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('reception_commande.list') }}" wire:navigate>
            {{ __('Réceptions') }}
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>
            {{ $bon?->numero_compte ? '№ '.$bon->numero_compte : '#'.($bon?->id ?? '—') }}
        </flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Détail de la réception') }}</flux:heading>

        <div class="flex items-center gap-2">
            @if($bon)
                <flux:button
                    href="{{ route('reception_commande.pdf', $bon->id) }}"
                    target="_blank"
                    variant="filled"
                    icon="document-arrow-down"
                >
                    {{ __('Contrôle de réception') }}
                </flux:button>
            @endif

            @php $commande = $bon?->commande; @endphp

            @if($commande && $commande->status !== \App\Enums\CommandeStatus::Recue)
                <flux:button
                    href="{{ route('reception_commande.edit', ['reception' => $reception->id]) }}"
                    wire:navigate
                    variant="primary"
                    icon="pencil-square"
                >
                    {{ __('Modifier') }}
                </flux:button>
            @endif

            <flux:button
                href="{{ route('reception_commande.list') }}"
                wire:navigate
                variant="ghost"
                icon="arrow-left"
            >
                {{ __('Retour') }}
            </flux:button>
        </div>
    </div>

    {{-- Informations générales --}}
    <flux:card class="mt-5 p-6">
        <flux:heading size="lg" class="mb-4">{{ __('Informations générales') }}</flux:heading>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

            {{-- Bon de commande --}}
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">{{ __('Bon de commande') }}</p>
                <p class="font-semibold text-sm">
                    {{ $bon?->numero_compte ? '№ '.$bon->numero_compte : '#'.($bon?->id ?? '—') }}
                </p>
                @if($bon?->code_fournisseur)
                    <p class="text-xs text-zinc-400 mt-0.5">{{ $bon->code_fournisseur }}</p>
                @endif
            </div>

            {{-- Commande --}}
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">{{ __('Commande') }}</p>
                @if($commande)
                    <p class="font-semibold text-sm">#{{ $commande->id }}</p>
                    @if($commande->libelle)
                        <p class="text-xs text-zinc-400 mt-0.5">{{ $commande->libelle }}</p>
                    @endif
                @else
                    <p class="text-sm text-zinc-400">—</p>
                @endif
            </div>

            {{-- Fournisseur --}}
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">{{ __('Fournisseur') }}</p>
                <p class="font-semibold text-sm">{{ $commande?->fournisseur?->name ?? '—' }}</p>
            </div>

            {{-- Magasin de livraison --}}
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">{{ __('Magasin de livraison') }}</p>
                @php $magasin = $commande?->magasinLivraison ?? $bon?->magasinLivraison; @endphp
                <p class="font-semibold text-sm">{{ $magasin?->name ?? '—' }}</p>
            </div>

            {{-- Date de réception --}}
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">{{ __('Date de réception') }}</p>
                <p class="font-semibold text-sm">{{ $reception->created_at->format('d/m/Y') }}</p>
                <p class="text-xs text-zinc-400 mt-0.5">{{ $reception->created_at->format('H:i') }}</p>
            </div>

            {{-- Statut commande --}}
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">{{ __('Statut commande') }}</p>
                @if($commande)
                    <flux:badge
                        color="{{ $commande->status === \App\Enums\CommandeStatus::Recue ? 'green' : 'zinc' }}"
                        size="sm"
                    >
                        {{ $commande->status?->value ?? '—' }}
                    </flux:badge>
                @else
                    <span class="text-sm text-zinc-400">—</span>
                @endif
            </div>

        </div>
    </flux:card>

    {{-- Lignes de réception --}}
    <flux:card class="mt-5 p-6">
        <div class="flex items-center justify-between mb-4">
            <flux:heading size="lg">{{ __('Lignes de réception') }}</flux:heading>

            <div class="flex items-center gap-3">
                @php
                    $allReceptions  = $bon?->receptions ?? collect();
                    $totalRecu      = $allReceptions->sum('recu');
                    $totalInvendable = $allReceptions->sum('invendable');
                @endphp

                <flux:badge color="zinc" size="sm">
                    {{ $allReceptions->count() }} {{ __('ligne(s)') }}
                </flux:badge>

                @if($totalInvendable > 0)
                    <flux:badge color="red" size="sm">
                        {{ $totalInvendable }} {{ __('invendable(s)') }}
                    </flux:badge>
                @endif

                <flux:badge color="green" size="sm">
                    {{ $totalRecu }} {{ __('reçu(s)') }}
                </flux:badge>
            </div>
        </div>

        <flux:table variant="bordered">
            <flux:table.columns>
                <flux:table.column>{{ __('Produit') }}</flux:table.column>
                <flux:table.column>{{ __('Référence') }}</flux:table.column>
                <flux:table.column class="text-center">{{ __('Qté commandée') }}</flux:table.column>
                <flux:table.column class="text-center">{{ __('Qté reçue') }}</flux:table.column>
                <flux:table.column class="text-center">{{ __('Invendable') }}</flux:table.column>
                <flux:table.column class="text-center">{{ __('Écart') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($allReceptions as $ligne)
                    @php
                        $detail   = $ligne->detail_commande;
                        $product  = $detail?->product;
                        $quantite = $detail?->quantite ?? 0;
                        $recu     = $ligne->recu ?? 0;
                        $ecart    = $quantite - $recu;
                    @endphp

                    <flux:table.row :key="$ligne->id" wire:key="ligne-{{ $ligne->id }}">

                        {{-- Produit --}}
                        <flux:table.cell class="text-sm">
                            <p class="font-medium">
                                {{ $product?->designation ?? '—' }}
                            </p>
                            @if($product?->designation_variant)
                                <p class="text-xs text-zinc-400 mt-0.5">
                                    {{ $product->designation_variant }}
                                </p>
                            @endif
                        </flux:table.cell>

                        {{-- Référence --}}
                        <flux:table.cell class="text-sm text-zinc-400">
                            {{ $product?->product_code ?? $product?->article ?? '—' }}
                        </flux:table.cell>

                        {{-- Qté commandée --}}
                        <flux:table.cell class="text-center text-sm">
                            {{ $quantite ?: '—' }}
                        </flux:table.cell>

                        {{-- Qté reçue --}}
                        <flux:table.cell class="text-center">
                            <flux:badge color="green" size="sm" inset="top bottom">
                                {{ $recu }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Invendable --}}
                        <flux:table.cell class="text-center">
                            @if(($ligne->invendable ?? 0) > 0)
                                <flux:badge color="red" size="sm" inset="top bottom">
                                    {{ $ligne->invendable }}
                                </flux:badge>
                            @else
                                <span class="text-zinc-400 text-sm">0</span>
                            @endif
                        </flux:table.cell>

                        {{-- Écart --}}
                        <flux:table.cell class="text-center">
                            @if($ecart > 0)
                                <flux:badge color="amber" size="sm" inset="top bottom">
                                    -{{ $ecart }}
                                </flux:badge>
                            @elseif($ecart < 0)
                                <flux:badge color="blue" size="sm" inset="top bottom">
                                    +{{ abs($ecart) }}
                                </flux:badge>
                            @else
                                <flux:badge color="green" size="sm" inset="top bottom">
                                    ✓
                                </flux:badge>
                            @endif
                        </flux:table.cell>

                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <div class="flex flex-col items-center justify-center py-10 text-center">
                                <flux:icon name="inbox" class="text-zinc-400 mb-3" style="width:36px;height:36px;" />
                                <p class="text-zinc-400 text-sm">{{ __('Aucune ligne de réception') }}</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
