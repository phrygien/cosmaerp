<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\DetailCommande;
use App\Models\ReceptionCommande;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

new class extends Component
{
    public int     $commande_id;
    public ?int    $bon_commande_id;
    public ?string $date_reception;
    public ?string $note;

    public array $lignes = [];

    public function mount(): void
    {
        foreach ($this->details as $detail) {
            $existing = ReceptionCommande::where('commande_id', $this->commande_id)
                ->where('detail_commande_id', $detail->id)
                ->first();

            $totalRepartition = (int) $detail->destinations->sum('quantite') ?: $detail->quantite;

            $this->lignes[$detail->id] = [
                'recu'       => $existing?->recu       ?? $totalRepartition,
                'invendable' => $existing?->invendable ?? 0,
            ];
        }
    }

    #[Computed]
    public function details()
    {
        return DetailCommande::where('commande_id', $this->commande_id)
            ->with(['product.marque', 'product.categorie', 'destinations.magasin'])
            ->get();
    }

    private function getTotalRepartition(int $detailId): int
    {
        $detail = $this->details->find($detailId);
        return (int) ($detail?->destinations->sum('quantite') ?: $detail?->quantite ?? 0);
    }

    public function updatedLignes($value, $key): void
    {
        [$detailId, $field] = explode('.', $key);
        $detailId = (int) $detailId;
        $attendu  = $this->getTotalRepartition($detailId);

        // Cast to int, clamp negatives to 0
        $recu       = max(0, (int) ($this->lignes[$detailId]['recu']       ?? 0));
        $invendable = max(0, (int) ($this->lignes[$detailId]['invendable'] ?? 0));

        if ($field === 'recu') {
            // recu ne peut pas dépasser attendu
            $recu = min($recu, $attendu);

            // Si invendable dépasse le nouveau recu, on le recadre
            $invendable = min($invendable, $recu);
        }

        if ($field === 'invendable') {
            // invendable ne peut pas dépasser recu
            $invendable = min($invendable, $recu);
        }

        $this->lignes[$detailId]['recu']       = $recu;
        $this->lignes[$detailId]['invendable'] = $invendable;
    }

    public function getEtatLigne(int $detailId): string
    {
        $attendu    = $this->getTotalRepartition($detailId);
        $recu       = (int) ($this->lignes[$detailId]['recu']       ?? 0);
        $invendable = (int) ($this->lignes[$detailId]['invendable'] ?? 0);
        $vendable   = $recu - $invendable;

        if ($recu === 0)           return 'non_recu';
        if ($recu === $attendu && $invendable === 0) return 'complet';
        if ($recu === $attendu)    return 'complet_partiel';
        return 'partiel';
    }

    public function saveLigne(int $detailId): void
    {
        $attendu    = $this->getTotalRepartition($detailId);
        $recu       = min(max(0, (int) ($this->lignes[$detailId]['recu']       ?? 0)), $attendu);
        $invendable = min(max(0, (int) ($this->lignes[$detailId]['invendable'] ?? 0)), $recu);

        $this->lignes[$detailId]['recu']       = $recu;
        $this->lignes[$detailId]['invendable'] = $invendable;

        try {
            DB::transaction(function () use ($detailId, $recu, $invendable) {
                ReceptionCommande::updateOrCreate(
                    ['commande_id' => $this->commande_id, 'detail_commande_id' => $detailId],
                    [
                        'bon_commande_id' => $this->bon_commande_id,
                        'recu'            => $recu,
                        'invendable'      => $invendable,
                        'state'           => 1,
                    ]
                );
            });

            $this->dispatch('notify', ['type' => 'success', 'message' => 'Ligne mise à jour avec succès.']);

        } catch (\Throwable $e) {
            Log::error('Erreur sauvegarde réception', ['error' => $e->getMessage()]);
            $this->addError("lignes.{$detailId}.invendable", 'Erreur lors de la sauvegarde.');
        }
    }

    #[Computed] public function totalAttendu(): int   { return $this->details->sum('quantite'); }
    #[Computed] public function totalRecu(): int      { return collect($this->lignes)->sum(fn($l) => (int)($l['recu']       ?? 0)); }
    #[Computed] public function totalInvendable(): int { return collect($this->lignes)->sum(fn($l) => (int)($l['invendable'] ?? 0)); }
    #[Computed] public function totalVendable(): int  { return $this->totalRecu - $this->totalInvendable; }
};
?>

<div class="space-y-8">

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-700 rounded-2xl shadow-sm p-6 text-center">
            <p class="text-xs uppercase tracking-widest font-medium text-gray-500">Attendu</p>
            <p class="text-4xl font-semibold text-gray-900 dark:text-white tabular-nums mt-3">{{ $this->totalAttendu }}</p>
            <p class="text-sm text-gray-400 mt-1">unités commandées</p>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-700 rounded-2xl shadow-sm p-6 text-center">
            <p class="text-xs uppercase tracking-widest font-medium text-gray-500">Reçu</p>
            <p class="text-4xl font-semibold text-blue-600 dark:text-blue-400 tabular-nums mt-3">{{ $this->totalRecu }}</p>
            <p class="text-sm text-gray-400 mt-1">unités réceptionnées</p>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-700 rounded-2xl shadow-sm p-6 text-center">
            <p class="text-xs uppercase tracking-widest font-medium text-gray-500">Vendable</p>
            <p class="text-4xl font-semibold text-green-600 dark:text-green-500 tabular-nums mt-3">{{ $this->totalVendable }}</p>
            <p class="text-sm text-gray-400 mt-1">unités vendables</p>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-700 rounded-2xl shadow-sm p-6 text-center">
            <p class="text-xs uppercase tracking-widest font-medium text-gray-500">Invendable</p>
            <p class="text-4xl font-semibold text-red-600 dark:text-red-500 tabular-nums mt-3">{{ $this->totalInvendable }}</p>
            <p class="text-sm text-gray-400 mt-1">unités écartées</p>
        </div>
    </div>

    <!-- Tableau -->
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Produit</flux:table.column>
            <flux:table.column>Marque</flux:table.column>
            <flux:table.column>Qté commandée</flux:table.column>
            <flux:table.column>Répartition</flux:table.column>
            <flux:table.column>Qté reçue</flux:table.column>
            <flux:table.column>Invendable</flux:table.column>
            <flux:table.column>Vendable</flux:table.column>
            <flux:table.column>Statut</flux:table.column>
            <flux:table.column>Action</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->details as $detail)
                @php
                    $attendu    = $this->getTotalRepartition($detail->id);
                    $recu       = (int) ($this->lignes[$detail->id]['recu']       ?? 0);
                    $invendable = (int) ($this->lignes[$detail->id]['invendable'] ?? 0);
                    $vendable   = $recu - $invendable;
                    $etat       = $this->getEtatLigne($detail->id);
                @endphp

                <flux:table.row :key="'row-'.$detail->id">

                    <flux:table.cell>
                        <p class="font-medium text-sm">{{ $detail->product->designation }}</p>
                        <p class="text-xs text-gray-400 font-mono">{{ $detail->product->product_code }}</p>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($detail->product->marque)
                            <flux:badge size="sm" color="blue">{{ $detail->product->marque->name }}</flux:badge>
                        @else
                            <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell variant="strong">{{ $detail->quantite }}</flux:table.cell>

                    <flux:table.cell>
                        <div class="space-y-1 text-xs">
                            @foreach($detail->destinations as $dest)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">{{ $dest->magasin->name ?? '—' }}</span>
                                    <flux:badge size="sm" color="zinc">{{ $dest->quantite }}</flux:badge>
                                </div>
                            @endforeach
                            <div class="text-right text-gray-400 text-[10px] pt-1">Total : <strong>{{ $attendu }}</strong></div>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="w-28">
                            <flux:input
                                wire:model.live="lignes.{{ $detail->id }}.recu"
                                type="number"
                                min="0"
                                max="{{ $attendu }}"
                                size="sm"
                            />
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="w-28">
                            <flux:input
                                wire:model.live="lignes.{{ $detail->id }}.invendable"
                                type="number"
                                min="0"
                                max="{{ $recu }}"
                                size="sm"
                            />
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <span class="font-semibold tabular-nums
                            {{ $vendable > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                            {{ $vendable }}
                        </span>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($etat === 'complet')
                            <flux:badge color="green" size="sm">Conforme</flux:badge>
                        @elseif($etat === 'complet_partiel')
                            <flux:badge color="amber" size="sm">Complet avec écart</flux:badge>
                        @elseif($etat === 'partiel')
                            <flux:badge color="amber" size="sm">Partiel</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Non reçu</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:button size="sm" wire:click="saveLigne({{ $detail->id }})">
                            Sauvegarder
                        </flux:button>
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="9" class="text-center py-12 text-gray-400">
                        Aucun produit dans cette commande
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

</div>
