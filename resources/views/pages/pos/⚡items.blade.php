<?php

use Livewire\Component;
use Livewire\Attributes\Computed;

new class extends Component
{
    // Recherche produit
    public string $search = '';

    // Panier : [ ['produit_id' => x, 'name' => '', 'prix_unitaire' => 0, 'quantite' => 1, 'remise' => 0, 'taux_tva' => 0] ]
    public array $panier = [];

    // Client optionnel (statique pour design)
    public ?int $client_id = null;

    // Catalogue produit statique (données de design)
    private array $catalogueProduits = [
        ['id' => 1, 'name' => 'Coca-Cola 33cl', 'sku' => 'BEV-001', 'code_barre' => '5449000000996', 'prix_vente' => 45.00, 'taux_tva' => 15, 'categorie' => 'Boissons'],
        ['id' => 2, 'name' => 'Eau minérale 1.5L', 'sku' => 'BEV-002', 'code_barre' => '5449000001238', 'prix_vente' => 35.00, 'taux_tva' => 15, 'categorie' => 'Boissons'],
        ['id' => 3, 'name' => 'Pain baguette', 'sku' => 'BAK-001', 'code_barre' => '3760001234567', 'prix_vente' => 25.00, 'taux_tva' => 0, 'categorie' => 'Boulangerie'],
        ['id' => 4, 'name' => 'Croissant beurre', 'sku' => 'BAK-002', 'code_barre' => '3760001234574', 'prix_vente' => 30.00, 'taux_tva' => 0, 'categorie' => 'Boulangerie'],
        ['id' => 5, 'name' => 'Lait entier 1L', 'sku' => 'DAI-001', 'code_barre' => '3175681234567', 'prix_vente' => 55.00, 'taux_tva' => 0, 'categorie' => 'Laitier'],
        ['id' => 6, 'name' => 'Yaourt nature x4', 'sku' => 'DAI-002', 'code_barre' => '3175681234574', 'prix_vente' => 80.00, 'taux_tva' => 0, 'categorie' => 'Laitier'],
        ['id' => 7, 'name' => 'Pommes 1kg', 'sku' => 'FRU-001', 'code_barre' => '2000000123456', 'prix_vente' => 90.00, 'taux_tva' => 0, 'categorie' => 'Fruits'],
        ['id' => 8, 'name' => 'Bananes 1kg', 'sku' => 'FRU-002', 'code_barre' => '2000000123463', 'prix_vente' => 60.00, 'taux_tva' => 0, 'categorie' => 'Fruits'],
        ['id' => 9, 'name' => 'Riz basmati 5kg', 'sku' => 'GRO-001', 'code_barre' => '8901030123456', 'prix_vente' => 350.00, 'taux_tva' => 15, 'categorie' => 'Épicerie'],
        ['id' => 10, 'name' => 'Huile végétale 1L', 'sku' => 'GRO-002', 'code_barre' => '8901030123463', 'prix_vente' => 180.00, 'taux_tva' => 15, 'categorie' => 'Épicerie'],
        ['id' => 11, 'name' => 'Savon liquide 500ml', 'sku' => 'HYG-001', 'code_barre' => '6001087123456', 'prix_vente' => 120.00, 'taux_tva' => 15, 'categorie' => 'Hygiène'],
        ['id' => 12, 'name' => 'Papier toilette x6', 'sku' => 'HYG-002', 'code_barre' => '6001087123463', 'prix_vente' => 150.00, 'taux_tva' => 15, 'categorie' => 'Hygiène'],
    ];

    // Clients statiques (design)
    public array $clients = [
        ['id' => 1, 'name' => 'Client comptoir'],
        ['id' => 2, 'name' => 'Rajesh Kumar'],
        ['id' => 3, 'name' => 'Marie Lebon'],
    ];

    #[Computed]
    public function produits(): array
    {
        if (strlen($this->search) < 1) {
            return $this->catalogueProduits;
        }

        return array_values(array_filter($this->catalogueProduits, function ($produit) {
            $needle = mb_strtolower($this->search);
            return str_contains(mb_strtolower($produit['name']), $needle)
                || str_contains(mb_strtolower($produit['sku']), $needle)
                || $produit['code_barre'] === $this->search;
        }));
    }

    #[Computed]
    public function sousTotal(): float
    {
        return collect($this->panier)->sum(function ($ligne) {
            return $ligne['prix_unitaire'] * $ligne['quantite'];
        });
    }

    #[Computed]
    public function totalRemise(): float
    {
        return collect($this->panier)->sum('remise');
    }

    #[Computed]
    public function totalTva(): float
    {
        return collect($this->panier)->sum(function ($ligne) {
            $baseHt = ($ligne['prix_unitaire'] * $ligne['quantite']) - $ligne['remise'];
            return $baseHt * ($ligne['taux_tva'] / 100);
        });
    }

    #[Computed]
    public function totalTtc(): float
    {
        return $this->sousTotal - $this->totalRemise + $this->totalTva;
    }

    public function ajouterProduit(int $produitId): void
    {
        $produit = collect($this->catalogueProduits)->firstWhere('id', $produitId);

        if (! $produit) {
            return;
        }

        $index = collect($this->panier)->search(fn ($l) => $l['produit_id'] === $produit['id']);

        if ($index !== false) {
            $this->panier[$index]['quantite']++;
        } else {
            $this->panier[] = [
                'produit_id' => $produit['id'],
                'name' => $produit['name'],
                'prix_unitaire' => $produit['prix_vente'],
                'taux_tva' => $produit['taux_tva'],
                'quantite' => 1,
                'remise' => 0,
            ];
        }

        $this->search = '';
    }

    public function incrementer(int $index): void
    {
        $this->panier[$index]['quantite']++;
    }

    public function decrementer(int $index): void
    {
        if ($this->panier[$index]['quantite'] > 1) {
            $this->panier[$index]['quantite']--;
        } else {
            $this->supprimerLigne($index);
        }
    }

    public function updateQuantite(int $index, int $quantite): void
    {
        if ($quantite < 1) {
            $this->supprimerLigne($index);
            return;
        }

        $this->panier[$index]['quantite'] = $quantite;
    }

    public function supprimerLigne(int $index): void
    {
        unset($this->panier[$index]);
        $this->panier = array_values($this->panier);
    }

    public function viderPanier(): void
    {
        $this->panier = [];
        $this->client_id = null;
    }

    public function validerVente(): void
    {
        if (empty($this->panier)) {
            return;
        }

        // Phase design : on simule juste la validation, pas d'écriture DB
        $this->dispatch('vente-validee', total: $this->totalTtc);

        $this->viderPanier();
    }
};
?>

<div class="flex flex-col gap-4 lg:flex-row">
    {{-- Colonne recherche produit --}}
    <div class="lg:w-1/2">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Rechercher un produit (nom, SKU, code-barre)..."
            icon="magnifying-glass"
        />

        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
            @forelse ($this->produits as $produit)
                <button
                    type="button"
                    wire:click="ajouterProduit({{ $produit['id'] }})"
                    class="flex flex-col items-start rounded-lg border border-zinc-200 p-3 text-left transition hover:border-blue-500 hover:bg-blue-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
                >
                    <span class="text-xs font-medium text-blue-500">{{ $produit['categorie'] }}</span>
                    <span class="text-sm font-medium">{{ $produit['name'] }}</span>
                    <span class="text-xs text-zinc-500">{{ number_format($produit['prix_vente'], 2) }} MUR</span>
                </button>
            @empty
                <p class="col-span-full text-sm text-zinc-500">Aucun produit trouvé.</p>
            @endforelse
        </div>
    </div>

    {{-- Colonne panier --}}
    <div class="lg:w-1/2">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between border-b border-zinc-200 p-3 dark:border-zinc-700">
                <h3 class="font-semibold">Panier ({{ count($panier) }})</h3>
                @if(count($panier))
                    <flux:button size="sm" variant="ghost" wire:click="viderPanier">
                        Vider
                    </flux:button>
                @endif
            </div>

            {{-- Sélection client statique --}}
            <div class="border-b border-zinc-200 p-3 dark:border-zinc-700">
                <flux:select wire:model="client_id" placeholder="Client comptoir">
                    @foreach ($clients as $client)
                        <flux:select.option value="{{ $client['id'] }}">{{ $client['name'] }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="max-h-96 divide-y divide-zinc-100 overflow-y-auto dark:divide-zinc-800">
                @forelse ($panier as $index => $ligne)
                    <div class="flex items-center justify-between gap-2 p-3" wire:key="ligne-{{ $index }}">
                        <div class="flex-1">
                            <p class="text-sm font-medium">{{ $ligne['name'] }}</p>
                            <p class="text-xs text-zinc-500">
                                {{ number_format($ligne['prix_unitaire'], 2) }} MUR / unité
                            </p>
                        </div>

                        <div class="flex items-center gap-1">
                            <flux:button size="sm" variant="ghost" icon="minus" wire:click="decrementer({{ $index }})" />
                            <input
                                type="number"
                                min="1"
                                wire:change="updateQuantite({{ $index }}, $event.target.value)"
                                value="{{ $ligne['quantite'] }}"
                                class="w-12 rounded border border-zinc-200 text-center text-sm dark:border-zinc-700 dark:bg-zinc-900"
                            />
                            <flux:button size="sm" variant="ghost" icon="plus" wire:click="incrementer({{ $index }})" />
                        </div>

                        <div class="w-20 text-right text-sm font-semibold">
                            {{ number_format($ligne['prix_unitaire'] * $ligne['quantite'] - $ligne['remise'], 2) }}
                        </div>

                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="supprimerLigne({{ $index }})" />
                    </div>
                @empty
                    <p class="p-6 text-center text-sm text-zinc-500">Le panier est vide.</p>
                @endforelse
            </div>

            {{-- Totaux --}}
            <div class="space-y-1 border-t border-zinc-200 p-3 text-sm dark:border-zinc-700">
                <div class="flex justify-between">
                    <span class="text-zinc-500">Sous-total</span>
                    <span>{{ number_format($this->sousTotal, 2) }} MUR</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-500">Remise</span>
                    <span>- {{ number_format($this->totalRemise, 2) }} MUR</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-500">TVA</span>
                    <span>{{ number_format($this->totalTva, 2) }} MUR</span>
                </div>
                <div class="flex justify-between text-base font-bold">
                    <span>Total TTC</span>
                    <span>{{ number_format($this->totalTtc, 2) }} MUR</span>
                </div>
            </div>

            <div class="p-3">
                <flux:button
                    variant="primary"
                    class="w-full"
                    wire:click="validerVente"
                    :disabled="count($panier) === 0"
                >
                    Valider la vente
                </flux:button>
            </div>
        </div>
    </div>
</div>
