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
        $this->emailSent = false;

        $this->commande = $this->loadCommande($id);

        dd($this->commande);
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

        $email = $commande->fournisseur?->email;

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
    <flux:modal name="bon-commande" class="w-full max-w-4xl">
        @if ($commande)
            <div class="space-y-6">

                {{-- En-tête --}}
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading size="lg">Bon de commande</flux:heading>
                        <flux:text class="mt-1 text-zinc-500">
                            {{ $commande->libelle ?? '—' }}
                        </flux:text>
                    </div>
                    @php
                        $statusColor = match($commande->status) {
                            -1 => 'red', 1 => 'blue', 2 => 'yellow', 3 => 'green', default => 'zinc',
                        };
                        $statusLabel = match($commande->status) {
                            -1 => 'Annulée', 1 => 'Créée', 2 => 'Facturée', 3 => 'Clôturée', default => '—',
                        };
                    @endphp
                    <flux:badge :color="$statusColor">{{ $statusLabel }}</flux:badge>
                </div>

                <flux:separator />

                {{-- Infos bon de commande --}}
                @if ($bonCommande)
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 text-sm">
                        <div>
                            <p class="text-zinc-500 text-xs mb-0.5">Code fournisseur</p>
                            <p class="font-medium">{{ $bonCommande->code_fournisseur ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 text-xs mb-0.5">N° compte</p>
                            <p class="font-medium">{{ $bonCommande->numero_compte ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 text-xs mb-0.5">Date commande</p>
                            <p class="font-medium">
                                {{ $bonCommande->date_commande
                                    ? \Carbon\Carbon::parse($bonCommande->date_commande)->translatedFormat('d F Y')
                                    : '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-zinc-500 text-xs mb-0.5">Livraison prévue</p>
                            <p class="font-medium">
                                {{ $bonCommande->date_livraison_prevue
                                    ? \Carbon\Carbon::parse($bonCommande->date_livraison_prevue)->translatedFormat('d F Y')
                                    : '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-zinc-500 text-xs mb-0.5">Magasin facturation</p>
                            <p class="font-medium">{{ $bonCommande->magasinFacturation?->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 text-xs mb-0.5">Magasin livraison</p>
                            <p class="font-medium">{{ $bonCommande->magasinLivraison?->name ?? '—' }}</p>
                        </div>
                    </div>
                @else
                    <flux:callout icon="information-circle" color="blue">
                        <flux:callout.heading>Aucun bon de commande</flux:callout.heading>
                        <flux:callout.text>Aucun bon de commande n'a encore été généré pour cette commande.</flux:callout.text>
                    </flux:callout>
                @endif

                {{-- Fournisseur / Magasin --}}
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-zinc-500 text-xs mb-0.5">Fournisseur</p>
                        <p class="font-medium">{{ $commande->fournisseur?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-zinc-500 text-xs mb-0.5">Magasin livraison</p>
                        <p class="font-medium">{{ $commande->magasinLivraison?->name ?? '—' }}</p>
                    </div>
                </div>

                <flux:separator />

                {{-- Lignes de détail --}}
                <div>
                    <flux:heading size="sm" class="mb-3">Lignes de commande</flux:heading>

                    <flux:table variant="bordered">
                        <flux:table.columns>
                            <flux:table.column>Produit</flux:table.column>
                            <flux:table.column>Qté</flux:table.column>
                            <flux:table.column>PU HT</flux:table.column>
                            <flux:table.column>Remise</flux:table.column>
                            <flux:table.column>PU net</flux:table.column>
                            <flux:table.column>Total HT</flux:table.column>
                            <flux:table.column class="hidden md:table-cell">Destinations</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @forelse ($commande->details as $detail)
                                <flux:table.row :key="$detail->id">
                                    <flux:table.cell class="font-medium text-sm">
                                        {{ $detail->product?->name ?? '—' }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-sm">
                                        {{ $detail->quantite }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-sm whitespace-nowrap">
                                        {{ number_format($detail->pu_achat_HT, 2, ',', ' ') }} €
                                    </flux:table.cell>
                                    <flux:table.cell class="text-sm">
                                        {{ $detail->taux_remise ? $detail->taux_remise . ' %' : '—' }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-sm whitespace-nowrap">
                                        {{ number_format($detail->pu_achat_net, 2, ',', ' ') }} €
                                    </flux:table.cell>
                                    <flux:table.cell class="text-sm font-medium whitespace-nowrap">
                                        {{ number_format($detail->pu_achat_net * $detail->quantite, 2, ',', ' ') }} €
                                    </flux:table.cell>
                                    <flux:table.cell class="hidden md:table-cell">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($detail->destinations as $dest)
                                                <flux:badge size="sm" color="zinc">
                                                    {{ $dest->magasin?->name ?? '—' }} ({{ $dest->quantite }})
                                                </flux:badge>
                                            @endforeach
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="7">
                                        <p class="text-center text-zinc-400 text-sm py-4">Aucune ligne de commande</p>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{-- Total --}}
                <div class="flex justify-end">
                    <div class="text-right space-y-1">
                        <p class="text-sm text-zinc-500">Montant total</p>
                        <p class="text-2xl font-bold">
                            {{ number_format($commande->montant_total, 2, ',', ' ') }} €
                        </p>
                        @if ($bonCommande?->montant_commande_net)
                            <p class="text-sm text-zinc-500">
                                Net :
                                <span class="font-medium text-zinc-700 dark:text-zinc-200">
                                    {{ number_format($bonCommande->montant_commande_net, 2, ',', ' ') }} €
                                </span>
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Footer avec actions --}}
                <div class="flex items-center justify-between pt-2">

                    {{-- Actions PDF / Email --}}
                    <div class="flex items-center gap-2">

                        {{-- Bouton PDF : lien direct vers la route, nouvel onglet --}}
                        <a href="{{ route('bon-commande.pdf', $commandeId) }}" target="_blank">
                            <flux:button variant="filled" size="sm">
                                <svg xmlns="http://www.w3.org/2000/svg" aria-label="PDF" role="img" viewBox="0 0 512 512" class="w-4 h-4 mr-2">
                                    <rect width="512" height="512" rx="15%" fill="#c80a0a"></rect>
                                    <path fill="#ffffff" d="M413 302c-9-10-29-15-56-15-16 0-33 2-53 5a252 252 0 0 1-52-69c10-30 17-59 17-81 0-17-6-44-30-44-7 0-13 4-17 10-10 18-6 58 13 100a898 898 0 0 1-50 117c-53 22-88 46-91 65-2 9 4 24 25 24 31 0 65-45 91-91a626 626 0 0 1 92-24c38 33 71 38 87 38 32 0 35-23 24-35zM227 111c8-12 26-8 26 16 0 16-5 42-15 72-18-42-18-75-11-88zM100 391c3-16 33-38 80-57-26 44-52 72-68 72-10 0-13-9-12-15zm197-98a574 574 0 0 0-83 22 453 453 0 0 0 36-84 327 327 0 0 0 47 62zm13 4c32-5 59-4 71-2 29 6 19 41-13 33-23-5-42-18-58-31z"></path>
                                </svg>
                                Télécharger le bon de commande
                            </flux:button>
                        </a>

                        {{-- Bouton Email --}}
                        <flux:button
                            wire:click="sendEmail"
                            wire:loading.attr="disabled"
                            wire:target="sendEmail"
                            :icon="$emailSent ? 'check' : 'envelope'"
                            variant="outline"
                            size="sm"
                            :disabled="$emailSent || !$commande->fournisseur?->email"
                        >
                            <span wire:loading.remove wire:target="sendEmail">
                                {{ $emailSent ? 'Email envoyé' : 'Envoyer par email' }}
                            </span>
                            <span wire:loading wire:target="sendEmail">
                                Envoi en cours…
                            </span>
                        </flux:button>

                        {{-- Avertissement si pas d'email fournisseur --}}
                        @if (!$commande->fournisseur?->email)
                            <flux:tooltip content="Aucune adresse email pour ce fournisseur">
                                <flux:icon name="exclamation-triangle" class="text-amber-400 w-4 h-4" />
                            </flux:tooltip>
                        @endif

                    </div>

                    <flux:modal.close>
                        <flux:button variant="ghost">Fermer</flux:button>
                    </flux:modal.close>
                </div>

            </div>
        @endif
    </flux:modal>
</div>
