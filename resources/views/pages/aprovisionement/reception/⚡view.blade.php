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
            'bon_commande.commande.details.product',
            'bon_commande.magasinLivraison',
            'bon_commande.magasinFacturation',
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

    @php
        $commande        = $bon?->commande;
        $fournisseur     = $commande?->fournisseur;
        $magasin         = $commande?->magasinLivraison ?? $bon?->magasinLivraison;
        $magasinFact     = $bon?->magasinFacturation;
        $allReceptions   = $bon?->receptions ?? collect();
        $totalRecu       = $allReceptions->sum('recu');
        $totalInvendable = $allReceptions->sum('invendable');
        $totalCommande   = $commande?->details->sum('quantite') ?? 0;
    @endphp

    {{-- ── Bloc principal : Bon de commande + Commande ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">

        {{-- Bon de commande --}}
        <flux:card class="p-6">
            <div class="flex items-center gap-2 mb-4">
                <flux:icon name="document-text" class="text-zinc-400" style="width:18px;height:18px;" />
                <flux:heading size="lg">{{ __('Bon de commande') }}</flux:heading>
            </div>

            <dl class="space-y-3">
                <div class="flex justify-between items-start">
                    <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Numéro') }}</dt>
                    <dd class="font-semibold text-sm text-right">
                        {{ $bon?->numero_compte ? '№ '.$bon->numero_compte : '#'.($bon?->id ?? '—') }}
                    </dd>
                </div>

                @if($bon?->code_fournisseur)
                    <div class="flex justify-between items-start">
                        <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Code fournisseur') }}</dt>
                        <dd class="text-sm text-right font-mono">{{ $bon->code_fournisseur }}</dd>
                    </div>
                @endif

                @if($bon?->date_commande)
                    <div class="flex justify-between items-start">
                        <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Date commande') }}</dt>
                        <dd class="text-sm text-right">
                            {{ \Carbon\Carbon::parse($bon->date_commande)->format('d/m/Y') }}
                        </dd>
                    </div>
                @endif

                @if($bon?->date_livraison_prevue)
                    <div class="flex justify-between items-start">
                        <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Livraison prévue') }}</dt>
                        <dd class="text-sm text-right">
                            {{ \Carbon\Carbon::parse($bon->date_livraison_prevue)->format('d/m/Y') }}
                        </dd>
                    </div>
                @endif

                <div class="flex justify-between items-start">
                    <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Magasin facturation') }}</dt>
                    <dd class="text-sm text-right">{{ $magasinFact?->name ?? '—' }}</dd>
                </div>

                <div class="flex justify-between items-start">
                    <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Magasin livraison') }}</dt>
                    <dd class="text-sm text-right">{{ $magasin?->name ?? '—' }}</dd>
                </div>

                @if($bon?->montant_commande_net)
                    <div class="pt-2 border-t border-zinc-100 dark:border-zinc-700 flex justify-between items-start">
                        <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Montant net') }}</dt>
                        <dd class="font-bold text-sm text-right">
                            {{ number_format($bon->montant_commande_net, 2, ',', ' ') }} €
                        </dd>
                    </div>
                @endif
            </dl>
        </flux:card>

        {{-- Commande liée --}}
        <flux:card class="p-6">
            <div class="flex items-center gap-2 mb-4">
                <flux:icon name="shopping-cart" class="text-zinc-400" style="width:18px;height:18px;" />
                <flux:heading size="lg">{{ __('Commande') }}</flux:heading>
            </div>

            @if($commande)
                <dl class="space-y-3">
                    <div class="flex justify-between items-start">
                        <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Référence') }}</dt>
                        <dd class="font-semibold text-sm">#{{ $commande->id }}</dd>
                    </div>

                    @if($commande->libelle)
                        <div class="flex justify-between items-start">
                            <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Libellé') }}</dt>
                            <dd class="text-sm text-right max-w-[60%]">{{ $commande->libelle }}</dd>
                        </div>
                    @endif

                    <div class="flex justify-between items-center">
                        <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Statut') }}</dt>
                        <dd>
                            <flux:badge
                                color="{{ $commande->status === \App\Enums\CommandeStatus::Recue ? 'green' : 'zinc' }}"
                                size="sm"
                            >
                                {{ $commande->status?->value ?? '—' }}
                            </flux:badge>
                        </dd>
                    </div>

                    <div class="flex justify-between items-start">
                        <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Fournisseur') }}</dt>
                        <dd class="text-sm text-right font-medium">{{ $fournisseur?->name ?? '—' }}</dd>
                    </div>

                    @if($fournisseur?->code)
                        <div class="flex justify-between items-start">
                            <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Code fournisseur') }}</dt>
                            <dd class="text-sm text-right font-mono">{{ $fournisseur->code }}</dd>
                        </div>
                    @endif

                    @if($fournisseur?->telephone)
                        <div class="flex justify-between items-start">
                            <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Téléphone') }}</dt>
                            <dd class="text-sm text-right">{{ $fournisseur->telephone }}</dd>
                        </div>
                    @endif

                    @if($fournisseur?->mail)
                        <div class="flex justify-between items-start">
                            <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Email') }}</dt>
                            <dd class="text-sm text-right">{{ $fournisseur->mail }}</dd>
                        </div>
                    @endif

                    @if($commande->montant_total)
                        <div class="pt-2 border-t border-zinc-100 dark:border-zinc-700 flex justify-between items-start">
                            <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Montant total') }}</dt>
                            <dd class="font-bold text-sm text-right">
                                {{ number_format($commande->montant_total, 2, ',', ' ') }} €
                            </dd>
                        </div>
                    @endif

                    @if($commande->date_reception)
                        <div class="flex justify-between items-start">
                            <dt class="text-xs text-zinc-400 uppercase tracking-wide">{{ __('Date réception') }}</dt>
                            <dd class="text-sm text-right">
                                {{ $commande->date_reception->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                    @endif
                </dl>
            @else
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <flux:icon name="exclamation-triangle" class="text-zinc-300 mb-2" style="width:32px;height:32px;" />
                    <p class="text-zinc-400 text-sm">{{ __('Commande introuvable') }}</p>
                </div>
            @endif
        </flux:card>

    </div>

    {{-- ── Récapitulatif réception ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5">

        <flux:card class="p-4 text-center">
            <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">{{ __('Lignes') }}</p>
            <p class="text-2xl font-bold">{{ $allReceptions->count() }}</p>
        </flux:card>

        <flux:card class="p-4 text-center">
            <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">{{ __('Qté commandée') }}</p>
            <p class="text-2xl font-bold">{{ $totalCommande }}</p>
        </flux:card>

        <flux:card class="p-4 text-center">
            <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">{{ __('Qté reçue') }}</p>
            <p class="text-2xl font-bold text-green-600">{{ $totalRecu }}</p>
        </flux:card>

        <flux:card class="p-4 text-center">
            <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">{{ __('Invendable') }}</p>
            <p class="text-2xl font-bold {{ $totalInvendable > 0 ? 'text-red-500' : 'text-zinc-300' }}">
                {{ $totalInvendable }}
            </p>
        </flux:card>

    </div>

    {{-- ── Lignes de réception ── --}}
    <flux:card class="mt-5 p-6">
        <flux:heading size="lg" class="mb-4">{{ __('Lignes de réception') }}</flux:heading>

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

                        <flux:table.cell class="text-sm">
                            <p class="font-medium">{{ $product?->designation ?? '—' }}</p>
                            @if($product?->designation_variant)
                                <p class="text-xs text-zinc-400 mt-0.5">{{ $product->designation_variant }}</p>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="text-sm text-zinc-400 font-mono">
                            {{ $product?->product_code ?? $product?->article ?? '—' }}
                        </flux:table.cell>

                        <flux:table.cell class="text-center text-sm">
                            {{ $quantite ?: '—' }}
                        </flux:table.cell>

                        <flux:table.cell class="text-center">
                            <flux:badge color="green" size="sm" inset="top bottom">
                                {{ $recu }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="text-center">
                            @if(($ligne->invendable ?? 0) > 0)
                                <flux:badge color="red" size="sm" inset="top bottom">
                                    {{ $ligne->invendable }}
                                </flux:badge>
                            @else
                                <span class="text-zinc-400 text-sm">0</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="text-center">
                            @if($ecart > 0)
                                <flux:badge color="amber" size="sm" inset="top bottom">-{{ $ecart }}</flux:badge>
                            @elseif($ecart < 0)
                                <flux:badge color="blue" size="sm" inset="top bottom">+{{ abs($ecart) }}</flux:badge>
                            @else
                                <flux:badge color="green" size="sm" inset="top bottom">✓</flux:badge>
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
