<?php

use Livewire\Component;
use App\Models\BonCommande;
use Livewire\Attributes\On;

new class extends Component
{
    public ?BonCommande $bon = null;

    #[On('open-detail-reception')]
    public function load(int $id): void
    {
        $this->bon = BonCommande::query()
            ->with([
                'commande.fournisseur',
                'commande.details.product',
                'receptions.detail_commande',
                'magasinLivraison',
            ])
            ->withSum('receptions', 'recu')
            ->withSum('receptions', 'invendable')
            ->findOrFail($id);

        $this->modal('detail-reception')->show();
    }
};
?>

<div>
    <flux:modal name="detail-reception" class="w-full max-w-4xl">
        @if ($bon)
            <div class="space-y-6">

                {{-- En-tête --}}
                <div class="pr-8">
                    <flux:heading size="lg">
                        {{ __('Réception') }} — {{ $bon->numero_compte ?? '#' . $bon->id }}
                    </flux:heading>
                    <flux:text class="mt-1 text-zinc-500">
                        {{ $bon->commande?->fournisseur?->nom ?? '—' }}
                        · {{ $bon->created_at->format('d/m/Y') }}
                        @if ($bon->magasinLivraison)
                            · {{ $bon->magasinLivraison->nom }}
                        @endif
                    </flux:text>

                    <div class="flex flex-wrap gap-2 mt-3">
                        @if ($bon->date_livraison_prevue)
                            @php
                                $dateLivraison = \Carbon\Carbon::parse($bon->date_livraison_prevue);
                                $isLate = $dateLivraison->isPast();
                            @endphp
                            <flux:badge :color="$isLate ? 'red' : 'zinc'" size="sm">
                                {{ __('Livraison') }} {{ $dateLivraison->format('d/m/Y') }}
                                @if ($isLate) · {{ __('Retard') }} @endif
                            </flux:badge>
                        @endif
                        @if ($bon->montant_commande_net)
                            <flux:badge color="blue" size="sm">
                                {{ number_format($bon->montant_commande_net, 2) }} €
                            </flux:badge>
                        @endif
                    </div>
                </div>

                <flux:separator />

                {{-- Table des détails --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="py-2.5 px-3 text-left font-semibold text-zinc-600 dark:text-zinc-300">{{ __('Produit') }}</th>
                            <th class="py-2.5 px-3 text-center font-semibold text-zinc-600 dark:text-zinc-300 hidden sm:table-cell">{{ __('Qté commandée') }}</th>
                            <th class="py-2.5 px-3 text-center font-semibold text-zinc-600 dark:text-zinc-300">{{ __('Reçu') }}</th>
                            <th class="py-2.5 px-3 text-center font-semibold text-zinc-600 dark:text-zinc-300">{{ __('Invendable') }}</th>
                            <th class="py-2.5 px-3 text-right font-semibold text-zinc-600 dark:text-zinc-300 hidden md:table-cell">{{ __('Montant ligne') }}</th>
                            <th class="py-2.5 px-3 text-center font-semibold text-zinc-600 dark:text-zinc-300">{{ __('État') }}</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($bon->commande?->details ?? [] as $detail)
                            @php
                                $receptionsDetail = $bon->receptions->where('detail_commande_id', $detail->id);
                                $totalRecu        = $receptionsDetail->sum('recu');
                                $totalInvendable  = $receptionsDetail->sum('invendable');
                                $pct = $detail->quantite > 0
                                    ? round(($totalRecu / $detail->quantite) * 100)
                                    : 0;
                                $synthese = match(true) {
                                    $pct >= 100 => ['color' => 'green',  'label' => __('Complet')],
                                    $pct > 0    => ['color' => 'yellow', 'label' => __('Partiel')],
                                    default     => ['color' => 'zinc',   'label' => __('En attente')],
                                };
                            @endphp

                            {{-- Ligne produit --}}
                            <tr class="bg-zinc-50/60 dark:bg-zinc-800/30">
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-2">
                                        <flux:icon name="cube" class="size-4 text-zinc-400 shrink-0" />
                                        <div>
                                            <p class="font-semibold text-zinc-800 dark:text-zinc-100">
                                                {{ $detail->product?->designation ?? '—' }}
                                            </p>
                                            <p class="text-xs text-zinc-400 mt-0.5">
                                                PU net : {{ number_format($detail->pu_achat_net, 2) }} €
                                                @if ($detail->taux_remise > 0)
                                                    · <span class="text-green-500">-{{ $detail->taux_remise }}%</span>
                                                @endif
                                                · TVA {{ $detail->tax }}%
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-center hidden sm:table-cell text-zinc-600 dark:text-zinc-300 font-medium">
                                    {{ $detail->quantite }}
                                </td>
                                <td class="py-3 px-3 text-center font-semibold">
                                    {{ $totalRecu }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    @if ($totalInvendable > 0)
                                        <flux:badge size="sm" color="red" inset="top bottom">{{ $totalInvendable }}</flux:badge>
                                    @else
                                        <span class="text-zinc-400">0</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right hidden md:table-cell font-medium text-zinc-700 dark:text-zinc-200">
                                    {{ number_format($detail->pu_achat_net * $detail->quantite, 2) }} €
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <flux:badge size="sm" :color="$synthese['color']" inset="top bottom">
                                        {{ $synthese['label'] }}
                                    </flux:badge>
                                </td>
                            </tr>

                            {{-- Sous-lignes réceptions --}}
                            @forelse ($receptionsDetail as $reception)
                                @php
                                    $badge = match($reception->state) {
                                        'received' => ['color' => 'green',  'label' => __('Reçu')],
                                        'partial'  => ['color' => 'yellow', 'label' => __('Partiel')],
                                        'rejected' => ['color' => 'red',    'label' => __('Rejeté')],
                                        default    => ['color' => 'zinc',   'label' => __('En attente')],
                                    };
                                    $pctLine = $detail->quantite > 0
                                        ? round(($reception->recu / $detail->quantite) * 100)
                                        : 0;
                                @endphp
                                <tr class="bg-blue-50/20 dark:bg-blue-900/10 border-l-2 border-rose-200 dark:border-rose-800">
                                    <td class="py-2 pl-8 pr-3">
                                        <div class="flex items-center gap-1.5 text-xs text-zinc-500">
                                            <flux:icon name="arrow-turn-down-right" class="size-3 text-zinc-300" />
                                            <span>{{ __('Réception') }} #{{ $reception->id }}</span>
                                            <span class="text-zinc-300">·</span>
                                            <span>{{ $reception->created_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                    </td>
                                    <td class="py-2 px-3 text-center text-xs text-zinc-500 hidden sm:table-cell">—</td>
                                    <td class="py-2 px-3 text-center text-xs font-medium">
                                        {{ $reception->recu }}
                                        <span class="text-zinc-400">({{ $pctLine }}%)</span>
                                    </td>
                                    <td class="py-2 px-3 text-center">
                                        @if ($reception->invendable > 0)
                                            <flux:badge size="sm" color="red" inset="top bottom">{{ $reception->invendable }}</flux:badge>
                                        @else
                                            <span class="text-zinc-400 text-xs">0</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 hidden md:table-cell"></td>
                                    <td class="py-2 px-3 text-center">
                                        <flux:badge size="sm" :color="$badge['color']" inset="top bottom">
                                            {{ $badge['label'] }}
                                        </flux:badge>
                                    </td>
                                </tr>
                            @empty
                                <tr class="bg-blue-50/10 dark:bg-blue-900/5">
                                    <td colspan="6" class="py-2 pl-8 text-xs text-zinc-400 italic">
                                        {{ __('Aucune réception pour ce produit') }}
                                    </td>
                                </tr>
                            @endforelse

                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-zinc-400">
                                    <flux:icon name="inbox" class="mx-auto mb-2" style="width:32px;height:32px;" />
                                    {{ __('Aucun produit dans cette commande') }}
                                </td>
                            </tr>
                        @endforelse
                        </tbody>

                        {{-- Totaux --}}
                        @if (($bon->commande?->details ?? collect())->isNotEmpty())
                            <tfoot class="border-t-2 border-zinc-200 dark:border-zinc-700">
                            <tr class="font-semibold text-zinc-700 dark:text-zinc-200">
                                <td class="py-3 px-3">{{ __('Total') }}</td>
                                <td class="py-3 px-3 text-center hidden sm:table-cell">
                                    {{ $bon->commande->details->sum('quantite') }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    {{ $bon->receptions_sum_recu ?? $bon->receptions->sum('recu') }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    @php $totalInv = $bon->receptions_sum_invendable ?? $bon->receptions->sum('invendable'); @endphp
                                    @if ($totalInv > 0)
                                        <flux:badge color="red" size="sm" inset="top bottom">{{ $totalInv }}</flux:badge>
                                    @else
                                        <span class="text-zinc-400">0</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right hidden md:table-cell">
                                    {{ $bon->montant_commande_net ? number_format($bon->montant_commande_net, 2) . ' €' : '—' }}
                                </td>
                                <td></td>
                            </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Fermer') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @else
            <div class="flex items-center justify-center py-12">
                <flux:icon name="arrow-path" class="animate-spin text-zinc-400 size-6" />
            </div>
        @endif
    </flux:modal>
</div>
