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
        return (int) ($detail?->destinations->sum('quantite') ?? $detail?->quantite ?? 0);
    }

    public function getEtatLigne(int $detailId): string
    {
        $ligne   = $this->lignes[$detailId] ?? ['recu' => 0, 'invendable' => 0];
        $attendu = $this->getTotalRepartition($detailId);
        $recu    = (int) $ligne['recu'];

        if ($recu === 0)        return 'non_recu';
        if ($recu > $attendu)   return 'depasse';
        if ($recu === $attendu) return 'complet';
        return 'partiel';
    }

    public function saveLigne(int $detailId): void
    {
        $attendu = $this->getTotalRepartition($detailId);

        $this->validateOnly("lignes.{$detailId}.recu", [
            "lignes.{$detailId}.recu" => "integer|min:0|max:{$attendu}",
        ], [
            "lignes.{$detailId}.recu.max" => "Quantité max dépassée.",
        ]);

        $this->validateOnly("lignes.{$detailId}.invendable", [
            "lignes.{$detailId}.invendable" => 'integer|min:0',
        ]);

        $recu       = min((int) ($this->lignes[$detailId]['recu'] ?? 0), $attendu);
        $invendable = (int) ($this->lignes[$detailId]['invendable'] ?? 0);

        try {
            DB::transaction(function () use ($detailId, $recu, $invendable): void {
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

            $this->lignes[$detailId]['recu'] = $recu;

        } catch (\Throwable $e) {
            Log::error('Erreur sauvegarde réception', ['error' => $e->getMessage()]);
            $this->addError("lignes.{$detailId}.recu", 'Erreur de sauvegarde.');
        }
    }

    #[Computed] public function totalAttendu(): int { return $this->details->sum('quantite'); }
    #[Computed] public function totalRecu(): int { return collect($this->lignes)->sum(fn($l) => (int)($l['recu'] ?? 0)); }
    #[Computed] public function totalInvendable(): int { return collect($this->lignes)->sum(fn($l) => (int)($l['invendable'] ?? 0)); }
};
?>

<div class="space-y-8">
    <!-- === STATS CARDS - Version encore plus petite === -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

        <!-- Card Attendu -->
        <flux:card class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-700 shadow-sm">
            <div class="p-4">
                <p class="text-xl font-medium text-gray-500 dark:text-gray-400">Attendu</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1 tabular-nums">
                    {{ $this->totalAttendu }}
                </p>
                <p class="text-xl text-gray-400 mt-0.5">unités commandées</p>
            </div>
        </flux:card>

        <!-- Card Reçu -->
        <flux:card class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-700 shadow-sm">
            <div class="p-4">
                <p class="text-xl font-medium text-gray-500 dark:text-gray-400">Reçu</p>
                <p class="text-2xl font-semibold text-green-600 dark:text-green-500 mt-1 tabular-nums">
                    {{ $this->totalRecu }}
                </p>
                <p class="text-xl text-gray-400 mt-0.5">unités réceptionnées</p>
            </div>
        </flux:card>

        <!-- Card Invendable -->
        <flux:card class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-700 shadow-sm">
            <div class="p-4">
                <p class="text-xl font-medium text-gray-500 dark:text-gray-400">Invendable</p>
                <p class="text-2xl font-semibold text-red-600 dark:text-red-500 mt-1 tabular-nums">
                    {{ $this->totalInvendable }}
                </p>
                <p class="text-xl text-gray-400 mt-0.5">unités écartées</p>
            </div>
        </flux:card>

    </div>

    <!-- === TABLEAU DES PRODUITS === -->
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Produit</flux:table.column>
            <flux:table.column>Marque</flux:table.column>
            <flux:table.column>Qté commandée</flux:table.column>
            <flux:table.column>Répartition</flux:table.column>
            <flux:table.column>Qté reçue</flux:table.column>
            <flux:table.column>Invendable</flux:table.column>
            <flux:table.column>Statut</flux:table.column>
            <flux:table.column>Action</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->details as $detail)
                @php
                    $etat    = $this->getEtatLigne($detail->id);
                    $maxRecu = $detail->destinations->sum('quantite') ?: $detail->quantite;
                    $recu    = (int) ($lignes[$detail->id]['recu'] ?? 0);
                    $invalid = $recu <= 0 || $recu > $maxRecu;
                @endphp

                <flux:table.row :key="$detail->id">
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

                    <flux:table.cell variant="strong">
                        <span class="tabular-nums">{{ $detail->quantite }}</span>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="space-y-1 text-xs">
                            @foreach($detail->destinations as $dest)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">{{ $dest->magasin->name ?? '—' }}</span>
                                    <flux:badge size="sm" color="zinc">{{ $dest->quantite }}</flux:badge>
                                </div>
                            @endforeach
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="w-24">
                            <flux:input
                                wire:model.live="lignes.{{ $detail->id }}.recu"
                                type="number"
                                min="0"
                                max="{{ $maxRecu }}"
                                size="sm"
                            />
                        </div>
                        @error("lignes.{$detail->id}.recu")
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="w-24">
                            <flux:input
                                wire:model.live="lignes.{{ $detail->id }}.invendable"
                                type="number"
                                min="0"
                                size="sm"
                            />
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($etat === 'depasse')
                            <flux:badge color="red" size="sm">Dépassement</flux:badge>
                        @elseif($etat === 'complet')
                            <flux:badge color="green" size="sm">Complet</flux:badge>
                        @elseif($etat === 'partiel')
                            <flux:badge color="amber" size="sm">Partiel</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Non reçu</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:button size="sm" wire:click="saveLigne({{ $detail->id }})" :disabled="$invalid">
                            Sauvegarder
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center py-12 text-gray-400">
                        Aucun produit dans cette commande
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

</div>
