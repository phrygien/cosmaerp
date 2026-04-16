<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\DetailCommande;
use App\Models\ReceptionCommande;

new class extends Component
{
    public int     $commande_id;
    public ?int    $bon_commande_id;
    public ?string $date_reception;
    public ?string $note;

    /**
     * État local : detail_commande_id => ['recu' => int, 'invendable' => int]
     * @var array<int, array{recu: int, invendable: int}>
     */
    public array $lignes = [];

    public function mount(): void
    {
        foreach ($this->details as $detail) {
            // Pré-remplir avec les données déjà saisies si elles existent
            $existing = ReceptionCommande::where('commande_id', $this->commande_id)
                ->where('detail_commande_id', $detail->id)
                ->first();

            $this->lignes[$detail->id] = [
                'recu'       => $existing?->recu       ?? 0,
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

    public function getEtatLigne(int $detailId): string
    {
        $ligne   = $this->lignes[$detailId] ?? ['recu' => 0, 'invendable' => 0];
        $detail  = $this->details->find($detailId);
        $attendu = $detail?->quantite ?? 0;
        $recu    = (int) $ligne['recu'];

        if ($recu === 0)          return 'non_recu';
        if ($recu >= $attendu)    return 'complet';
        return 'partiel';
    }

    public function saveLigne(int $detailId): void
    {
        $this->validateOnly("lignes.{$detailId}.recu",       ["lignes.{$detailId}.recu"       => 'integer|min:0']);
        $this->validateOnly("lignes.{$detailId}.invendable", ["lignes.{$detailId}.invendable" => 'integer|min:0']);

        $recu       = (int) ($this->lignes[$detailId]['recu']       ?? 0);
        $invendable = (int) ($this->lignes[$detailId]['invendable'] ?? 0);

        ReceptionCommande::updateOrCreate(
            [
                'commande_id'        => $this->commande_id,
                'detail_commande_id' => $detailId,
            ],
            [
                'bon_commande_id' => $this->bon_commande_id,
                'recu'            => $recu,
                'invendable'      => $invendable,
                'state'           => 1,
            ]
        );
    }

    #[Computed]
    public function totalAttendu(): int
    {
        return $this->details->sum('quantite');
    }

    #[Computed]
    public function totalRecu(): int
    {
        return collect($this->lignes)->sum(fn($l) => (int) ($l['recu'] ?? 0));
    }

    #[Computed]
    public function totalInvendable(): int
    {
        return collect($this->lignes)->sum(fn($l) => (int) ($l['invendable'] ?? 0));
    }
};
?>

<div class="space-y-6">

    {{-- Barre de progression globale --}}
    <div class="rounded-lg border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
        <div class="flex items-center justify-between mb-3 flex-wrap gap-3">
            <flux:heading size="sm">Progression de la réception</flux:heading>
            <div class="flex items-center gap-4 text-sm">
                <span class="text-gray-500">
                    Attendu : <strong class="text-gray-900 dark:text-white">{{ $this->totalAttendu }}</strong>
                </span>
                <span class="text-green-600">
                    Reçu : <strong>{{ $this->totalRecu }}</strong>
                </span>
                <span class="text-red-500">
                    Invendable : <strong>{{ $this->totalInvendable }}</strong>
                </span>
            </div>
        </div>

        @php
            $pct = $this->totalAttendu > 0
                ? min(100, round($this->totalRecu / $this->totalAttendu * 100))
                : 0;
        @endphp

        <div class="w-full bg-gray-100 dark:bg-zinc-700 rounded-full h-2.5">
            <div
                class="h-2.5 rounded-full transition-all duration-500
                    {{ $pct >= 100 ? 'bg-green-500' : ($pct > 0 ? 'bg-rose-500' : 'bg-gray-300') }}"
                style="width: {{ $pct }}%"
            ></div>
        </div>
        <p class="text-xs text-gray-400 mt-1 text-right">{{ $pct }} % réceptionné</p>
    </div>

    {{-- Table des lignes --}}
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
                    $etat = $this->getEtatLigne($detail->id);
                @endphp

                <flux:table.row :key="$detail->id"
                                class="{{ $etat === 'complet' ? 'bg-green-50 dark:bg-green-900/10' : ($etat === 'partiel' ? 'bg-amber-50 dark:bg-amber-900/10' : '') }}"
                >

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

                    {{-- Qté commandée --}}
                    <flux:table.cell variant="strong">
                        <span class="tabular-nums">{{ $detail->quantite }}</span>
                    </flux:table.cell>

                    {{-- Répartition magasins --}}
                    <flux:table.cell>
                        <div class="space-y-0.5">
                            @foreach($detail->destinations as $dest)
                                <div class="flex items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="truncate max-w-20">{{ $dest->magasin->name ?? '—' }}</span>
                                    <flux:badge size="sm" color="zinc" inset="top bottom">
                                        {{ $dest->quantite }}
                                    </flux:badge>
                                </div>
                            @endforeach
                        </div>
                    </flux:table.cell>

                    {{-- Input : Qté reçue --}}
                    <flux:table.cell>
                        <div class="w-24">
                            <flux:input
                                wire:model.live="lignes.{{ $detail->id }}.recu"
                                type="number"
                                min="0"
                                max="{{ $detail->quantite }}"
                                step="1"
                                size="sm"
                                placeholder="0"
                            />
                        </div>
                    </flux:table.cell>

                    {{-- Input : Invendable --}}
                    <flux:table.cell>
                        <div class="w-24">
                            <flux:input
                                wire:model.live="lignes.{{ $detail->id }}.invendable"
                                type="number"
                                min="0"
                                step="1"
                                size="sm"
                                placeholder="0"
                            />
                        </div>
                    </flux:table.cell>

                    {{-- Statut --}}
                    <flux:table.cell>
                        @if($etat === 'complet')
                            <flux:badge size="sm" color="green" icon="check-circle" inset="top bottom">
                                Complet
                            </flux:badge>
                        @elseif($etat === 'partiel')
                            <flux:badge size="sm" color="amber" icon="exclamation-circle" inset="top bottom">
                                Partiel
                            </flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc" inset="top bottom">
                                Non reçu
                            </flux:badge>
                        @endif
                    </flux:table.cell>

                    {{-- Action : Sauvegarder la ligne --}}
                    <flux:table.cell>
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="check"
                            inset="top bottom"
                            wire:click="saveLigne({{ $detail->id }})"
                            wire:loading.attr="disabled"
                            wire:target="saveLigne({{ $detail->id }})"
                        >
                            Sauvegarder
                        </flux:button>
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="py-12 text-center text-gray-400">
                        Aucun produit dans cette commande
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

</div>
