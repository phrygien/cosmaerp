<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\BonCommande;
use App\Models\Commande;
use App\Mail\BonCommandeMail;
use App\Services\CurrencyService;
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

<style>
    @import url('https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&display=swap');

    #bon-commande-inner * {
        font-family: 'Josefin Sans', sans-serif;
        letter-spacing: 0.02em;
    }

    .bc-label {
        display: inline-block;
        background: #3f3f46; /* zinc-700 */
        color: #fff;
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        padding: 2px 10px;
        margin-bottom: 10px;
    }

    .bc-info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        border-bottom: 1px solid #e5e7eb;
    }
    .bc-info-cell {
        padding: 14px 18px;
        border-right: 1px solid #e5e7eb;
    }
    .bc-info-cell:last-child { border-right: none; }

    .bc-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .bc-table thead tr { border-bottom: 2px solid #3f3f46; /* zinc-700 */ }
    .bc-table thead th {
        padding: 10px 10px;
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #3f3f46; /* zinc-700 */
        background: #f4f4f5; /* zinc-100 */
    }
    .bc-table tbody tr { border-bottom: 1px solid #f3f4f6; transition: background 0.15s; }
    .bc-table tbody tr:hover { background: #f4f4f5; /* zinc-100 */ }
    .bc-table td { padding: 10px 10px; vertical-align: middle; }

    .bc-remise-badge {
        display: inline-flex;
        align-items: center;
        background: #e4e4e7; /* zinc-200 */
        color: #27272a; /* zinc-800 */
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 8px;
        letter-spacing: 0.06em;
    }

    .bc-dest-badge {
        display: inline-flex;
        align-items: center;
        background: #f4f4f5; /* zinc-100 */
        color: #52525b; /* zinc-600 */
        font-size: 0.65rem;
        font-weight: 600;
        padding: 2px 8px;
        letter-spacing: 0.04em;
    }

    .bc-totaux { width: 100%; max-width: 320px; margin-left: auto; font-size: 0.82rem; }
    .bc-totaux-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .bc-totaux-label { color: #6b7280; }
    .bc-totaux-value { font-weight: 600; color: #111; }
    .bc-totaux-net {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        background: #3f3f46; /* zinc-700 */
        padding: 12px 16px;
        margin-top: 10px;
    }
    .bc-totaux-net-label { color: #d4d4d8; /* zinc-300 */ font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.12em; }
    .bc-totaux-net-value { color: #fff; font-size: 1.2rem; font-weight: 700; }

    .bc-status {
        display: inline-block;
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        padding: 2px 10px;
    }
    .bc-status-red    { background: #fee2e2; color: #991b1b; }
    .bc-status-blue   { background: #dbeafe; color: #1e40af; }
    .bc-status-yellow { background: #fef9c3; color: #854d0e; }
    .bc-status-green  { background: #dcfce7; color: #166534; }
    .bc-status-zinc   { background: #f4f4f5; color: #3f3f46; }
</style>

<div>
    <flux:modal name="bon-commande" class="w-full max-w-6xl">
        @if ($commande)

            @php
                $currency    = app(CurrencyService::class);
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

            <div id="bon-commande-inner" class="space-y-0 overflow-hidden">

                {{-- ══ EN-TÊTE ══ --}}
                <div class="px-1 pb-4 pt-1 flex items-start justify-between gap-4">
                    <div>
                        <flux:heading size="lg">Bon de commande</flux:heading>
                        <flux:text class="mt-1 text-zinc-500">
                            {{ $commande->libelle ?? 'Commande sans libellé' }}
                        </flux:text>
                        @if($bonCommande?->date_commande)
                            <flux:text class="text-zinc-400 text-xs mt-0.5">
                                {{ \Carbon\Carbon::parse($bonCommande->date_commande)->translatedFormat('d F Y') }}
                            </flux:text>
                        @endif
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="bc-status bc-status-{{ $statusColor }}" style="margin-top:4px;">
                            {{ $statusLabel }}
                        </span>
                        <div class="flex gap-2">
                            <a href="{{ route('bon-commande.pdf', $commandeId) }}" target="_blank">
                                <flux:button size="sm" variant="ghost" icon="arrow-down-tray">PDF</flux:button>
                            </a>
                            <flux:button
                                wire:click="sendEmail"
                                wire:loading.attr="disabled"
                                wire:target="sendEmail"
                                :icon="$emailSent ? 'check-circle' : 'paper-airplane'"
                                size="sm"
                                variant="ghost"
                                :disabled="$emailSent || !$commande->fournisseur?->email"
                            >
                                <span wire:loading.remove wire:target="sendEmail">
                                    {{ $emailSent ? 'Envoyé' : 'Envoyer' }}
                                </span>
                                <span wire:loading wire:target="sendEmail">Envoi…</span>
                            </flux:button>
                        </div>
                        @if($bonCommande?->numero_compte)
                            <div class="text-right">
                                <p class="text-xs text-zinc-400 uppercase tracking-widest mb-0.5">N° Compte</p>
                                <p class="font-bold text-zinc-800 dark:text-zinc-100 text-sm">{{ $bonCommande->numero_compte }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <flux:separator />

                {{-- ══ FOURNISSEUR / FACTURATION / LIVRAISON ══ --}}
                <div class="bc-info-grid">
                    <div class="bc-info-cell">
                        <span class="bc-label">Fournisseur</span>
                        <p class="font-bold text-sm text-zinc-700">
                            {{ $commande->fournisseur?->name ?? '—' }}
                        </p>
                        @if($commande->fournisseur?->email)
                            <p class="text-xs text-zinc-500 mt-1 flex items-center gap-1">
                                <flux:icon.envelope class="w-3 h-3" />
                                {{ $commande->fournisseur->email }}
                            </p>
                        @endif
                        @if($bonCommande?->code_fournisseur)
                            <p class="text-xs text-zinc-400 mt-1">Code : {{ $bonCommande->code_fournisseur }}</p>
                        @endif
                    </div>

                    <div class="bc-info-cell">
                        <span class="bc-label">Facturation</span>
                        <p class="font-semibold text-sm text-zinc-800">
                            {{ $bonCommande?->magasinFacturation?->name ?? '—' }}
                        </p>
                        @if($bonCommande?->numero_compte)
                            <p class="text-xs text-zinc-400 mt-1">N° compte : {{ $bonCommande->numero_compte }}</p>
                        @endif
                    </div>

                    <div class="bc-info-cell">
                        <span class="bc-label">Livraison</span>
                        <p class="font-semibold text-sm text-zinc-800">
                            {{ $bonCommande?->magasinLivraison?->name ?? $commande->magasinLivraison?->name ?? '—' }}
                        </p>
                        @if($bonCommande?->date_livraison_prevue)
                            <p class="text-xs text-zinc-500 mt-1 flex items-center gap-1">
                                <flux:icon.calendar class="w-3 h-3" />
                                Prévue le {{ \Carbon\Carbon::parse($bonCommande->date_livraison_prevue)->translatedFormat('d F Y') }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- ══ ALERTE SI PAS DE BON DE COMMANDE ══ --}}
                @unless($bonCommande)
                    <div class="px-5 pt-5">
                        <flux:callout icon="information-circle" color="blue">
                            <flux:callout.heading>Aucun bon de commande généré</flux:callout.heading>
                            <flux:callout.text>Aucun bon de commande n'a encore été associé à cette commande.</flux:callout.text>
                        </flux:callout>
                    </div>
                @endunless

                {{-- ══ LIGNES DE COMMANDE ══ --}}
                <div class="p-5 space-y-4">
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <span class="bc-label" style="margin-bottom:0;">Lignes de commande</span>
                        <span class="text-xs text-zinc-400">
                            {{ $commande->details->count() }} produit(s)
                            &bull;
                            {{ $commande->details->sum('quantite') }} unité(s)
                        </span>
                    </div>

                    <div class="overflow-x-auto border border-zinc-200">
                        <table class="bc-table">
                            <thead>
                            <tr>
                                <th class="text-left">EAN</th>
                                <th class="text-left">Produit</th>
                                <th class="text-center">Qté</th>
                                <th class="text-right">PU brut HT</th>
                                <th class="text-center">Remise</th>
                                <th class="text-right">PU net HT</th>
                                <th class="text-right">Total HT</th>
                                <th class="text-left hidden md:table-cell">Destinations</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($commande->details as $detail)
                                <tr>
                                    <td class="hidden lg:table-cell">
                                        @if($detail->product?->EAN)
                                            <div style="display:flex; flex-direction:column; gap:3px;">
                                                {!! DNS1D::getBarcodeSVG(
                                                    $detail->product->EAN,
                                                    strlen($detail->product->EAN) === 8 ? 'EAN8' : 'EAN13',
                                                    1.2, 35, 'auto', false
                                                ) !!}
                                                <span class="text-zinc-400" style="font-family:monospace; font-size:0.6rem; letter-spacing:0.1em;">
                                                    {{ $detail->product->EAN }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-zinc-300">—</span>
                                        @endif
                                    </td>

                                    <td class="font-semibold text-zinc-800" style="max-width:200px;">
                                        <span style="display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; line-height:1.4;">
                                            {{ $detail->product?->designation ?? '—' }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <span style="display:inline-flex; align-items:center; justify-content:center; width:2rem; height:1.5rem; background:#f4f4f5; font-size:0.75rem; font-weight:700; color:#3f3f46;">
                                            {{ $detail->quantite }}
                                        </span>
                                    </td>

                                    <td class="text-right text-zinc-600 tabular-nums whitespace-nowrap">
                                        {{ $currency->format($detail->pu_achat_HT) }}
                                    </td>

                                    <td class="text-center">
                                        @if($detail->taux_remise)
                                            <span class="bc-remise-badge">{{ $detail->taux_remise }} %</span>
                                        @else
                                            <span class="text-zinc-300">—</span>
                                        @endif
                                    </td>

                                    <td class="text-right tabular-nums whitespace-nowrap text-zinc-700">
                                        {{ $currency->format($detail->pu_achat_net) }}
                                    </td>

                                    <td class="text-right tabular-nums whitespace-nowrap font-bold text-zinc-700">
                                        {{ $currency->format($detail->pu_achat_net * $detail->quantite) }}
                                    </td>

                                    <td class="hidden md:table-cell">
                                        <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                            @foreach ($detail->destinations as $dest)
                                                <span class="bc-dest-badge">
                                                    {{ $dest->magasin?->name ?? '—' }} ({{ $dest->quantite }})
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-zinc-400 text-sm" style="padding:40px 0;">
                                        <flux:icon.inbox class="w-8 h-8 mx-auto mb-2 text-zinc-300" />
                                        Aucune ligne de commande
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ══ TOTAUX ══ --}}
                <div class="px-5 pb-6">
                    <span class="bc-label">Récapitulatif</span>
                    <div class="bc-totaux mt-2">

                        <div class="bc-totaux-row">
                            <span class="bc-totaux-label">Montant brut HT</span>
                            <span class="bc-totaux-value">{{ $currency->format($totalBrut) }}</span>
                        </div>

                        @if($remisePct > 0)
                            <div class="bc-totaux-row">
                                <span class="bc-totaux-label">Remise ({{ $remisePct }} %)</span>
                                <span class="font-semibold text-zinc-700">
                                    − {{ $currency->format($totalBrut - $totalNet) }}
                                </span>
                            </div>
                        @endif

                        @if($commande->remise_facture > 0)
                            <div class="bc-totaux-row">
                                <span class="bc-totaux-label">Remise facture ({{ $commande->remise_facture }} %)</span>
                                <span class="bc-remise-badge">{{ $commande->remise_facture }} %</span>
                            </div>
                        @endif

                        <div class="bc-totaux-net">
                            <span class="bc-totaux-net-label">Total net HT</span>
                            <span class="bc-totaux-net-value">
                                {{ $currency->format($bonCommande?->montant_commande_net ?? $commande->montant_total) }}
                            </span>
                        </div>

                    </div>
                </div>

            </div>
        @endif
    </flux:modal>
</div>
