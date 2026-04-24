<?php

use Livewire\Component;
use App\Models\Commande;
use App\Models\BonCommande;

new class extends Component
{
    public int     $commande_id;
    public ?string $date_reception = null;
    public ?string $note           = null;

    public ?Commande $selectedCommande = null;
    public ?BonCommande $bonCommande   = null;

    public function mount()
    {
        $this->selectedCommande = Commande::with(['fournisseur', 'magasinLivraison', 'details'])
            ->find($this->commande_id);

        $this->bonCommande = BonCommande::where('commande_id', $this->commande_id)->first();
    }
}
?>

<div>
    @if($selectedCommande)
        <!-- Nouveau design compact -->
        <div class="sm:col-span-2 rounded-lg bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 p-6">

            {{-- En-tête --}}
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-mono font-semibold text-zinc-400 dark:text-zinc-500">
                        #{{ $selectedCommande->id }}
                    </span>
                    <span class="text-base font-semibold text-zinc-900 dark:text-white">
                        {{ $selectedCommande->libelle }}
                    </span>
                </div>
                <flux:badge color="blue" size="sm">{{ $selectedCommande->etat }}</flux:badge>
            </div>

            <!-- Grille compacte -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Fournisseur</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $selectedCommande->fournisseur?->name ?? '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Magasin</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $selectedCommande->magasinLivraison?->name ?? '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Montant total</p>
                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 tabular-nums">
                        {{ number_format($selectedCommande->montant_total, 2) }} EUR
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Bon de commande</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        @if($bonCommande)
                            #{{ $bonCommande->id }}
                        @else
                            <flux:badge color="red" size="sm">Aucun bon</flux:badge>
                        @endif
                    </p>
                </div>

                <!-- Lignes supplémentaires -->
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Date commande</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $selectedCommande->created_at?->format('d/m/Y') ?? '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Date réception</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $date_reception ? \Carbon\Carbon::parse($date_reception)->format('d/m/Y') : '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Références</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $selectedCommande->details->count() ?? 0 }} référence{{ $selectedCommande->details->count() > 1 ? 's' : '' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Note</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                        {{ $note ?: '—' }}
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
