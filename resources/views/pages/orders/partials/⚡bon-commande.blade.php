<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\BonCommande;
use App\Models\Commande;
use App\Mail\BonCommandeMail;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;

new class extends Component
{
    public ?int $commandeId = null;
    public ?BonCommande $bonCommande = null;
    public ?Commande $commande = null;
    public bool $emailSent = false;

    private function loadCommande(int $id): Commande
    {
        return Commande::with([
            'fournisseur',
            'magasinLivraison',
            'details.product',
            'details.destinations.magasin',
        ])->findOrFail($id);
    }

    #[On('show-bon-commande')]
    public function load(int $id): void
    {
        $this->commandeId = $id;
        $this->emailSent  = false;
        $this->commande   = $this->loadCommande($id);

        $this->bonCommande = BonCommande::with([
            'magasinFacturation',
            'magasinLivraison',
        ])->where('commande_id', $id)->first();

        Flux::modal('bon-commande')->show();
    }

    public function sendEmail(): void
    {
        $commande    = $this->loadCommande($this->commandeId);
        $bonCommande = $this->bonCommande;
        $email       = $commande->fournisseur?->email;

        if (!$email) {
            Flux::toast('Aucune adresse email trouvée pour ce fournisseur.', variant: 'danger');
            return;
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'pdf.bon-commande',
            compact('commande', 'bonCommande')
        )->setPaper('a4');

        Mail::to($email)->send(new BonCommandeMail($commande, $bonCommande, $pdf->output()));

        $this->emailSent = true;
        Flux::toast('Bon de commande envoyé à ' . $email, variant: 'success');
    }
};
?>

<div>
    <flux:modal name="bon-commande" class="w-full max-w-6xl">
        @if ($commande)

            @php
                $statusColor = match($commande->status) {
                    -1 => 'red', 1 => 'blue', 2 => 'yellow', 3 => 'green', default => 'zinc',
                };
                $statusLabel = match($commande->status) {
                    -1 => 'Annulée', 1 => 'Créée', 2 => 'Facturée', 3 => 'Clôturée', default => '—',
                };
                $totalBrut = $commande->details->sum(fn($d) => $d->pu_achat_HT  * $d->quantite);
                $totalNet  = $commande->details->sum(fn($d) => $d->pu_achat_net * $d->quantite);
                $remisePct = $totalBrut > 0 ? round((1 - $totalNet / $totalBrut) * 100, 2) : 0;
            @endphp

            <div class="space-y-0">

                {{-- ─── EN-TÊTE ─── --}}
                <div class="flex items-start justify-between pb-5 pr-14 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center gap-3">
                        {{-- Icône document --}}
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon.document-text class="w-5 h-5 text-zinc-500" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <flux:heading size="lg" class="leading-tight">
                                    {{ $commande->libelle ?? 'Commande sans libellé' }}
                                </flux:heading>
                                <flux:badge :color="$statusColor" size="sm">{{ $statusLabel }}</flux:badge>
                            </div>
                            <flux:text class="text-xs text-zinc-400 mt-0.5">
                                @if($bonCommande?->date_commande)
                                    Commande du {{ \Carbon\Carbon::parse($bonCommande->date_commande)->translatedFormat('d F Y') }}
                                    @endif
                                    @if($bonCommande?->numero_compte)
                                        &bull; N° {{ $bonCommande->numero_compte }}
                                @endif
                            </flux:text>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2">
                        <a href="{{ route('bon-commande.pdf', $commandeId) }}" target="_blank">
                            <flux:button size="sm" variant="ghost" icon="arrow-down-tray">
                                PDF
                            </flux:button>
                        </a>

                        <flux:button
                            wire:click="sendEmail"
                            wire:loading.attr="disabled"
                            wire:target="sendEmail"
                            :icon="$emailSent ? 'check-circle' : 'paper-airplane'"
                            size="sm"
                            variant="outline"
                            :disabled="$emailSent || !$commande->fournisseur?->email"
                        >
                        <span wire:loading.remove wire:target="sendEmail">
                            {{ $emailSent ? 'Envoyé' : 'Envoyer' }}
                        </span>
                            <span wire:loading wire:target="sendEmail">Envoi…</span>
                        </flux:button>
                    </div>
                </div>

                {{-- ─── BLOC FOURNISSEUR + MAGASINS ─── --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-px bg-zinc-200 dark:bg-zinc-700 border-b border-zinc-200 dark:border-zinc-700">

                    {{-- Fournisseur --}}
                    <div class="bg-white dark:bg-zinc-900 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-zinc-400 mb-2">Fournisseur</p>
                        <p class="font-semibold text-sm text-zinc-800 dark:text-zinc-100">
                            {{ $commande->fournisseur?->name ?? '—' }}
                        </p>
                        @if($commande->fournisseur?->email)
                            <p class="text-xs text-zinc-500 mt-0.5 flex items-center gap-1">
                                <flux:icon.envelope class="w-3 h-3" />
                                {{ $commande->fournisseur->email }}
                            </p>
                        @endif
                        @if($bonCommande?->code_fournisseur)
                            <p class="text-xs text-zinc-400 mt-1">Code : {{ $bonCommande->code_fournisseur }}</p>
                        @endif
                    </div>

                    {{-- Magasin facturation --}}
                    <div class="bg-white dark:bg-zinc-900 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-zinc-400 mb-2">Facturation</p>
                        <p class="font-semibold text-sm text-zinc-800 dark:text-zinc-100">
                            {{ $bonCommande?->magasinFacturation?->name ?? '—' }}
                        </p>
                        @if($bonCommande?->numero_compte)
                            <p class="text-xs text-zinc-400 mt-0.5">N° compte : {{ $bonCommande->numero_compte }}</p>
                        @endif
                    </div>

                    {{-- Magasin livraison --}}
                    <div class="bg-white dark:bg-zinc-900 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-zinc-400 mb-2">Livraison</p>
                        <p class="font-semibold text-sm text-zinc-800 dark:text-zinc-100">
                            {{ $bonCommande?->magasinLivraison?->name ?? $commande->magasinLivraison?->name ?? '—' }}
                        </p>
                        @if($bonCommande?->date_livraison_prevue)
                            <p class="text-xs text-zinc-500 mt-0.5 flex items-center gap-1">
                                <flux:icon.calendar class="w-3 h-3" />
                                Prévue le {{ \Carbon\Carbon::parse($bonCommande->date_livraison_prevue)->translatedFormat('d F Y') }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- ─── ALERTE SI PAS DE BON DE COMMANDE ─── --}}
                @unless($bonCommande)
                    <div class="px-4 pt-4">
                        <flux:callout icon="information-circle" color="blue">
                            <flux:callout.heading>Aucun bon de commande généré</flux:callout.heading>
                            <flux:callout.text>Aucun bon de commande n'a encore été associé à cette commande.</flux:callout.text>
                        </flux:callout>
                    </div>
                @endunless

                {{-- ─── LIGNES DE COMMANDE ─── --}}
                <div class="p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">Lignes de commande</flux:heading>
                        <span class="text-xs text-zinc-400">
                        {{ $commande->details->count() }} produit(s) &bull;
                        {{ $commande->details->sum('quantite') }} unité(s)
                    </span>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead>
                            <tr class="bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-700">
                                <th class="text-left px-3 py-2.5 text-xs font-semibold text-zinc-500 uppercase tracking-wider">EAN</th>
                                <th class="text-left px-3 py-2.5 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Produit</th>
                                <th class="text-center px-3 py-2.5 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Qté</th>
                                <th class="text-right px-3 py-2.5 text-xs font-semibold text-zinc-500 uppercase tracking-wider">PU brut HT</th>
                                <th class="text-center px-3 py-2.5 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Remise</th>
                                <th class="text-right px-3 py-2.5 text-xs font-semibold text-zinc-500 uppercase tracking-wider">PU net HT</th>
                                <th class="text-right px-3 py-2.5 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Total HT</th>
                                <th class="text-left px-3 py-2.5 text-xs font-semibold text-zinc-500 uppercase tracking-wider hidden md:table-cell">Destinations</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($commande->details as $detail)
                                <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition-colors">

                                    {{-- EAN + code-barres --}}
                                    <td class="px-3 py-2.5 hidden lg:table-cell">
                                        @if($detail->product?->EAN)
                                            <div class="flex flex-col items-start gap-0.5">
                                                {!! DNS1D::getBarcodeSVG(
                                                    $detail->product->EAN,
                                                    strlen($detail->product->EAN) === 8 ? 'EAN8' : 'EAN13',
                                                    1.2, 35, 'auto', false
                                                ) !!}
                                                <span class="text-[9px] text-zinc-400 font-mono tracking-widest">
                                                    {{ $detail->product->EAN }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                        @endif
                                    </td>

                                    {{-- Désignation --}}
                                    <td class="px-3 py-2.5 font-medium text-zinc-800 dark:text-zinc-100 max-w-[200px]">
                                        <span class="line-clamp-2 leading-snug">
                                            {{ $detail->product?->designation ?? '—' }}
                                        </span>
                                    </td>

                                    {{-- Qté --}}
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-6 rounded bg-zinc-100 dark:bg-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                            {{ $detail->quantite }}
                                        </span>
                                    </td>

                                    {{-- PU brut HT --}}
                                    <td class="px-3 py-2.5 text-right text-zinc-600 dark:text-zinc-400 tabular-nums whitespace-nowrap">
                                        {{ number_format($detail->pu_achat_HT, 2, ',', ' ') }} €
                                    </td>

                                    {{-- Remise --}}
                                    <td class="px-3 py-2.5 text-center">
                                        @if($detail->taux_remise)
                                            <flux:badge color="yellow" size="sm">{{ $detail->taux_remise }} %</flux:badge>
                                        @else
                                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                        @endif
                                    </td>

                                    {{-- PU net HT --}}
                                    <td class="px-3 py-2.5 text-right tabular-nums whitespace-nowrap text-zinc-700 dark:text-zinc-300">
                                        {{ number_format($detail->pu_achat_net, 2, ',', ' ') }} €
                                    </td>

                                    {{-- Total HT --}}
                                    <td class="px-3 py-2.5 text-right font-semibold tabular-nums whitespace-nowrap text-zinc-900 dark:text-zinc-100">
                                        {{ number_format($detail->pu_achat_net * $detail->quantite, 2, ',', ' ') }} €
                                    </td>

                                    {{-- Destinations --}}
                                    <td class="px-3 py-2.5 hidden md:table-cell">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($detail->destinations as $dest)
                                                <flux:badge size="sm" color="zinc">
                                                    {{ $dest->magasin?->name ?? '—' }} ({{ $dest->quantite }})
                                                </flux:badge>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-3 py-10 text-center text-zinc-400 text-sm">
                                        <flux:icon.inbox class="w-8 h-8 mx-auto mb-2 text-zinc-300" />
                                        Aucune ligne de commande
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ─── TOTAUX ─── --}}
                <div class="px-4 pb-4 flex justify-end">
                    <div class="w-full max-w-xs space-y-1.5">

                        <div class="flex items-center justify-between text-sm text-zinc-500">
                            <span>Montant brut HT</span>
                            <span class="tabular-nums font-medium text-zinc-700 dark:text-zinc-300">
                            {{ number_format($totalBrut, 2, ',', ' ') }} €
                        </span>
                        </div>

                        @if($remisePct > 0)
                            <div class="flex items-center justify-between text-sm text-zinc-500">
                                <span>Remise ({{ $remisePct }} %)</span>
                                <span class="tabular-nums font-medium text-red-500">
                            − {{ number_format($totalBrut - $totalNet, 2, ',', ' ') }} €
                        </span>
                            </div>
                        @endif

                        @if($commande->remise_facture > 0)
                            <div class="flex items-center justify-between text-sm text-zinc-500">
                                <span>Remise facture ({{ $commande->remise_facture }} %)</span>
                                <span class="tabular-nums font-medium text-red-500">—</span>
                            </div>
                        @endif

                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2 mt-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">Total net HT</span>
                                <span class="text-xl font-bold tabular-nums text-zinc-900 dark:text-zinc-50">
                                {{ number_format($bonCommande?->montant_commande_net ?? $commande->montant_total, 2, ',', ' ') }} €
                            </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        @endif
    </flux:modal>
</div>
