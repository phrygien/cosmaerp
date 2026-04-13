<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Commande;
use App\Models\DetailCommande;
use App\Models\DestinationDetailCommande;
use App\Models\Product;
use App\Models\Fournisseur;
use App\Models\Magasin;

new class extends Component
{
    use WithPagination;

    // ─── Navigation ───────────────────────────────────────────────
    public int $currentStep = 1;
    public int $totalSteps  = 3;

    // ─── Step 1 – Infos générales ─────────────────────────────────
    public string $libelle              = '';
    public ?int   $fournisseur_id       = null;
    public ?int   $magasin_livraison_id = null;
    public float  $remise_facture       = 0;
    public float  $montant_minimum      = 0;
    public int    $nombre_jour          = 30;

    // ─── Step 2 – Catalogue produits ──────────────────────────────
    public string $search        = '';
    public string $sortBy        = 'designation';
    public string $sortDirection = 'asc';

    // ─── Modal destination ────────────────────────────────────────
    public bool   $modalOpen        = false;
    public ?int   $modalProductId   = null;
    public string $modalDesignation = '';
    public string $modalArticle     = '';
    public float  $modalTva         = 0;
    public float  $modalPuAchatHt   = 0;
    public float  $modalTauxRemise  = 0;

    // Destinations : [['magasin_id' => X, 'magasin_name' => '…', 'quantite' => 0], …]
    public array $modalDestinations = [];

    // ─── Lignes confirmées (detail_commande en mémoire) ───────────
    // [['product_id', 'designation', 'article', 'tva',
    //   'pu_achat_HT', 'taux_remise', 'pu_achat_net',
    //   'quantite', 'destinations' => [...]], …]
    public array $lignes = [];

    // ─── Sort + pagination ────────────────────────────────────────
    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy        = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // ─── Computed ─────────────────────────────────────────────────
    #[Computed]
    public function fournisseurs()
    {
        return Fournisseur::orderBy('name')->get();
    }

    #[Computed]
    public function magasins()
    {
        return Magasin::orderBy('name')->get();
    }

    #[Computed]
    public function products()
    {
        $excludedIds = collect($this->lignes)->pluck('product_id')->all();

        return Product::query()
            ->where('state', 1)
            ->whereNotIn('id', $excludedIds)
            ->when(strlen($this->search) >= 2, fn($q) =>
            $q->where(fn($sub) =>
            $sub->where('designation',   'like', '%' . $this->search . '%')
                ->orWhere('article',      'like', '%' . $this->search . '%')
                ->orWhere('product_code', 'like', '%' . $this->search . '%')
                ->orWhere('ref_fabri_n_1','like', '%' . $this->search . '%')
            )
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    #[Computed]
    public function montantTotal(): float
    {
        return collect($this->lignes)->sum(function ($l) {
            $ht  = ($l['quantite'] ?? 0) * ($l['pu_achat_net'] ?? 0);
            $tva = $ht * (($l['tva'] ?? 0) / 100);
            return $ht + $tva;
        });
    }

    // ─── Modal : ouvrir ───────────────────────────────────────────
    public function openModal(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) return;

        $this->modalProductId   = $product->id;
        $this->modalDesignation = $product->designation;
        $this->modalArticle     = $product->article ?? '';
        $this->modalTva         = (float) ($product->tva ?? 0);
        $this->modalPuAchatHt   = 0;
        $this->modalTauxRemise  = 0;

        $this->modalDestinations = $this->magasins
            ->map(fn($m) => [
                'magasin_id'   => $m->id,
                'magasin_name' => $m->name,
                'quantite'     => 0,
            ])
            ->values()
            ->toArray();

        $this->modalOpen = true;
    }

    public function closeModal(): void
    {
        $this->modalOpen         = false;
        $this->modalProductId    = null;
        $this->modalDestinations = [];
    }

    // ─── Confirmer le produit depuis le modal ─────────────────────
    public function confirmProduct(): void
    {
        $this->validate([
            'modalPuAchatHt'               => 'required|numeric|min:0',
            'modalTauxRemise'              => 'numeric|min:0|max:100',
            'modalDestinations'            => 'required|array',
            'modalDestinations.*.quantite' => 'required|integer|min:0',
        ], [
            'modalPuAchatHt.required' => 'Le prix unitaire est obligatoire.',
            'modalPuAchatHt.min'      => 'Le prix unitaire doit être positif.',
        ]);

        $totalQte = (int) collect($this->modalDestinations)->sum('quantite');

        if ($totalQte <= 0) {
            $this->addError('modalDestinations', 'La quantité totale doit être supérieure à 0.');
            return;
        }

        $puNet = $this->modalPuAchatHt * (1 - $this->modalTauxRemise / 100);

        $this->lignes[] = [
            'product_id'   => $this->modalProductId,
            'designation'  => $this->modalDesignation,
            'article'      => $this->modalArticle,
            'tva'          => $this->modalTva,
            'pu_achat_HT'  => $this->modalPuAchatHt,
            'taux_remise'  => $this->modalTauxRemise,
            'pu_achat_net' => $puNet,
            'quantite'     => $totalQte,
            'destinations' => collect($this->modalDestinations)
                ->filter(fn($d) => $d['quantite'] > 0)
                ->values()
                ->toArray(),
        ];

        $this->closeModal();
        unset($this->products); // reset computed cache
    }

    // ─── Supprimer une ligne confirmée ────────────────────────────
    public function removeLigne(int $index): void
    {
        array_splice($this->lignes, $index, 1);
        $this->lignes = array_values($this->lignes);
        unset($this->products);
    }

    // ─── Validation par step ──────────────────────────────────────
    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'libelle'              => 'required|string|min:3',
                'fournisseur_id'       => 'required|exists:fournisseur,id',
                'magasin_livraison_id' => 'required|exists:magasin,id',
                'remise_facture'       => 'numeric|min:0|max:100',
                'montant_minimum'      => 'numeric|min:0',
                'nombre_jour'          => 'integer|min:1',
            ],
            2 => [
                'lignes' => 'required|array|min:1',
            ],
            default => [],
        };
    }

    protected function messagesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'libelle.required'              => 'Le libellé est obligatoire.',
                'fournisseur_id.required'       => 'Veuillez choisir un fournisseur.',
                'magasin_livraison_id.required' => 'Veuillez choisir un magasin de livraison.',
            ],
            2 => [
                'lignes.required' => 'Ajoutez au moins un produit.',
                'lignes.min'      => 'Ajoutez au moins un produit.',
            ],
            default => [],
        };
    }

    // ─── Navigation ───────────────────────────────────────────────
    public function nextStep(): void
    {
        $this->validate(
            $this->rulesForStep($this->currentStep),
            $this->messagesForStep($this->currentStep)
        );
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    // ─── Sauvegarde finale ────────────────────────────────────────
    public function save(): void
    {
        $commande = Commande::create([
            'libelle'              => $this->libelle,
            'fournisseur_id'       => $this->fournisseur_id,
            'magasin_livraison_id' => $this->magasin_livraison_id,
            'remise_facture'       => $this->remise_facture,
            'montant_minimum'      => $this->montant_minimum,
            'nombre_jour'          => $this->nombre_jour,
            'montant_total'        => $this->montantTotal,
            'status'               => 'draft',
            'state'                => 1,
            'etat'                 => 'en_attente',
        ]);

        foreach ($this->lignes as $ligne) {
            $detail = DetailCommande::create([
                'commande_id'  => $commande->id,
                'product_id'   => $ligne['product_id'],
                'pu_achat_HT'  => $ligne['pu_achat_HT'],
                'tax'          => $ligne['tva'],
                'taux_remise'  => $ligne['taux_remise'],
                'pu_achat_net' => $ligne['pu_achat_net'],
                'quantite'     => $ligne['quantite'],
                'state'        => 1,
            ]);

            foreach ($ligne['destinations'] as $dest) {
                DestinationDetailCommande::create([
                    'detail_commande_id' => $detail->id,
                    'magasin_id'         => $dest['magasin_id'],
                    'quantite'           => $dest['quantite'],
                    'state'              => 1,
                ]);
            }
        }

        session()->flash('success', 'Commande créée avec succès !');
        $this->redirect(route('commandes.index'));
    }
};
?>

<div class="max-w-7xl mx-auto">
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">Commandes</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Création de commande</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Nouvelle Commande') }}</flux:heading>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- STEPPER                                                        -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    @php
        $steps = [1 => 'Informations générales', 2 => 'Sélection des produits', 3 => 'Récapitulatif'];
    @endphp
    <nav aria-label="Progress" class="mb-6">
        <ol role="list" class="divide-y divide-gray-300 rounded-md border border-gray-300 md:flex md:divide-y-0">
            @foreach ($steps as $step => $label)
                <li class="relative md:flex md:flex-1">
                    @if ($currentStep > $step)
                        <span class="group flex w-full items-center">
                            <span class="flex items-center px-6 py-4 text-sm font-medium">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-indigo-600">
                                    <svg class="size-6 text-white" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 0 1 .208 1.04l-9 13.5a.75.75 0 0 1-1.154.114l-6-6a.75.75 0 0 1 1.06-1.06l5.353 5.353 8.493-12.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" /></svg>
                                </span>
                                <span class="ml-4 text-sm font-medium text-gray-900">{{ $label }}</span>
                            </span>
                        </span>
                    @elseif ($currentStep === $step)
                        <span class="flex items-center px-6 py-4 text-sm font-medium" aria-current="step">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full border-2 border-indigo-600">
                                <span class="text-indigo-600">{{ str_pad($step, 2, '0', STR_PAD_LEFT) }}</span>
                            </span>
                            <span class="ml-4 text-sm font-medium text-indigo-600">{{ $label }}</span>
                        </span>
                    @else
                        <span class="group flex items-center">
                            <span class="flex items-center px-6 py-4 text-sm font-medium">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full border-2 border-gray-300">
                                    <span class="text-gray-500">{{ str_pad($step, 2, '0', STR_PAD_LEFT) }}</span>
                                </span>
                                <span class="ml-4 text-sm font-medium text-gray-500">{{ $label }}</span>
                            </span>
                        </span>
                    @endif
                    @if ($step < count($steps))
                        <div class="absolute top-0 right-0 hidden h-full w-5 md:block" aria-hidden="true">
                            <svg class="size-full text-gray-300" viewBox="0 0 22 80" fill="none" preserveAspectRatio="none">
                                <path d="M0 -2L20 40L0 82" vector-effect="non-scaling-stroke" stroke="currentcolor" stroke-linejoin="round" />
                            </svg>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- STEP 1 – Informations générales                               -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    @if ($currentStep === 1)
        <flux:card class="p-8">
            <flux:heading size="lg" class="mb-6">Informations générales</flux:heading>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Libellé <span class="text-red-500">*</span></flux:label>
                        <flux:input wire:model="libelle" placeholder="Ex : Commande mensuelle avril 2025" />
                        <flux:error name="libelle" />
                    </flux:field>
                </div>
                <div>
                    <flux:field>
                        <flux:label>Fournisseur <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model="fournisseur_id">
                            <flux:select.option value="">-- Sélectionner --</flux:select.option>
                            @foreach ($this->fournisseurs as $f)
                                <flux:select.option value="{{ $f->id }}">{{ $f->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="fournisseur_id" />
                    </flux:field>
                </div>
                <div>
                    <flux:field>
                        <flux:label>Magasin de livraison <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model="magasin_livraison_id">
                            <flux:select.option value="">-- Sélectionner --</flux:select.option>
                            @foreach ($this->magasins as $m)
                                <flux:select.option value="{{ $m->id }}">{{ $m->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="magasin_livraison_id" />
                    </flux:field>
                </div>
                <div>
                    <flux:field>
                        <flux:label>Remise facture (%)</flux:label>
                        <flux:input wire:model="remise_facture" type="number" min="0" max="100" step="0.01" placeholder="0" />
                        <flux:error name="remise_facture" />
                    </flux:field>
                </div>
                <div>
                    <flux:field>
                        <flux:label>Montant minimum</flux:label>
                        <flux:input wire:model="montant_minimum" type="number" min="0" step="0.01" placeholder="0.00" />
                        <flux:error name="montant_minimum" />
                    </flux:field>
                </div>
                <div>
                    <flux:field>
                        <flux:label>Délai de paiement (jours)</flux:label>
                        <flux:input wire:model="nombre_jour" type="number" min="1" placeholder="30" />
                        <flux:error name="nombre_jour" />
                    </flux:field>
                </div>
            </div>
            <div class="mt-8 flex justify-end">
                <flux:button wire:click="nextStep" variant="primary">Suivant &rarr;</flux:button>
            </div>
        </flux:card>
    @endif

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- STEP 2 – Sélection des produits                               -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    @if ($currentStep === 2)
        <div class="space-y-6">

            {{-- ── Catalogue produits ── --}}
            <flux:card class="p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <flux:heading size="lg">Catalogue produits</flux:heading>
                        <flux:badge color="zinc" size="sm">Cliquez sur une ligne pour l'ajouter</flux:badge>
                    </div>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Rechercher désignation, code, article…"
                        icon="magnifying-glass"
                        class="w-80"
                    />
                </div>

                <flux:error name="lignes" class="mb-3" />

                <flux:table :paginate="$this->products">
                    <flux:table.columns>
                        <flux:table.column class="w-10"></flux:table.column>

                        <flux:table.column
                            sortable
                            :sorted="$sortBy === 'product_code'"
                            :direction="$sortDirection"
                            wire:click="sort('product_code')"
                        >Code</flux:table.column>

                        <flux:table.column
                            sortable
                            :sorted="$sortBy === 'designation'"
                            :direction="$sortDirection"
                            wire:click="sort('designation')"
                        >Désignation</flux:table.column>

                        <flux:table.column
                            sortable
                            :sorted="$sortBy === 'article'"
                            :direction="$sortDirection"
                            wire:click="sort('article')"
                        >Article</flux:table.column>

                        <flux:table.column
                            sortable
                            :sorted="$sortBy === 'marque_code'"
                            :direction="$sortDirection"
                            wire:click="sort('marque_code')"
                        >Marque</flux:table.column>

                        <flux:table.column
                            sortable
                            :sorted="$sortBy === 'tva'"
                            :direction="$sortDirection"
                            wire:click="sort('tva')"
                        >TVA %</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->products as $product)
                            <flux:table.row
                                :key="$product->id"
                                wire:click="openModal({{ $product->id }})"
                                class="cursor-pointer hover:bg-indigo-50 transition-colors"
                            >
                                {{-- Indicateur checkbox visuel --}}
                                <flux:table.cell>
                                    <div class="flex size-5 items-center justify-center rounded border-2 border-gray-300 bg-white group-hover:border-indigo-400">
                                        <svg class="size-3 text-indigo-500 opacity-0" viewBox="0 0 12 10" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="1,5 4,8 11,1"/>
                                        </svg>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell class="whitespace-nowrap font-mono text-xs text-gray-500">
                                    {{ $product->product_code }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <span class="font-medium text-gray-900">{{ $product->designation }}</span>
                                    @if ($product->designation_variant)
                                        <span class="block text-xs text-gray-400">{{ $product->designation_variant }}</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell class="text-sm text-gray-600">
                                    {{ $product->article }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($product->marque_code)
                                        <flux:badge size="sm" color="zinc" inset="top bottom">
                                            {{ $product->marque?->name ?? $product->marque_code }}
                                        </flux:badge>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell variant="strong">
                                    {{ $product->tva ? $product->tva . ' %' : '—' }}
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="py-10 text-center text-sm text-gray-400">
                                    @if ($search)
                                        Aucun produit trouvé pour « {{ $search }} ».
                                    @else
                                        Tous les produits ont été ajoutés à la commande.
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>

            {{-- ── Lignes confirmées ── --}}
            @if (count($lignes) > 0)
                <flux:card class="p-6">
                    <div class="mb-4 flex items-center gap-3">
                        <flux:heading size="lg">Produits ajoutés</flux:heading>
                        <flux:badge color="indigo">{{ count($lignes) }}</flux:badge>
                    </div>

                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Désignation</flux:table.column>
                            <flux:table.column>PU Achat HT</flux:table.column>
                            <flux:table.column>Remise</flux:table.column>
                            <flux:table.column>PU Net HT</flux:table.column>
                            <flux:table.column>TVA</flux:table.column>
                            <flux:table.column>Qté totale</flux:table.column>
                            <flux:table.column>Destinations</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($lignes as $index => $ligne)
                                <flux:table.row :key="$index">
                                    <flux:table.cell>
                                        <span class="font-medium text-gray-900">{{ $ligne['designation'] }}</span>
                                        <span class="block text-xs text-gray-400">{{ $ligne['article'] }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell variant="strong">
                                        {{ number_format($ligne['pu_achat_HT'], 2) }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-sm text-gray-600">
                                        {{ $ligne['taux_remise'] }} %
                                    </flux:table.cell>
                                    <flux:table.cell variant="strong">
                                        {{ number_format($ligne['pu_achat_net'], 2) }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-sm text-gray-600">
                                        {{ $ligne['tva'] }} %
                                    </flux:table.cell>
                                    <flux:table.cell variant="strong">
                                        {{ $ligne['quantite'] }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @foreach ($ligne['destinations'] as $dest)
                                            <span class="block text-xs text-gray-600">
                                                <span class="font-medium">{{ $dest['magasin_name'] }}</span> : {{ $dest['quantite'] }}
                                            </span>
                                        @endforeach
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button
                                            wire:click="removeLigne({{ $index }})"
                                            variant="ghost"
                                            size="sm"
                                            icon="trash"
                                            inset="top bottom"
                                            class="text-red-400 hover:text-red-600"
                                        />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>

                    <div class="mt-4 flex justify-end">
                        <div class="rounded-lg bg-indigo-50 px-6 py-3 flex items-center gap-6">
                            <span class="text-sm font-medium text-indigo-700">Total TTC estimé</span>
                            <span class="text-xl font-bold text-indigo-900">{{ number_format($this->montantTotal, 2) }}</span>
                        </div>
                    </div>
                </flux:card>
            @endif

        </div>

        <div class="mt-6 flex justify-between">
            <flux:button wire:click="prevStep" variant="ghost">&larr; Retour</flux:button>
            <flux:button wire:click="nextStep" variant="primary">Suivant &rarr;</flux:button>
        </div>

        <!-- ══════════════════════════════════════════════════════════ -->
        <!-- MODAL – Répartition des stocks par magasin                -->
        <!-- ══════════════════════════════════════════════════════════ -->
        <flux:modal wire:model="modalOpen" class="w-full max-w-2xl">
            <div class="p-6">

                {{-- En-tête --}}
                <div class="mb-6">
                    <flux:heading size="lg">Configurer le produit</flux:heading>
                    <p class="mt-1 text-sm text-gray-500">
                        <span class="font-semibold text-gray-800">{{ $modalDesignation }}</span>
                        @if ($modalArticle)
                            &nbsp;&middot;&nbsp;{{ $modalArticle }}
                        @endif
                        &nbsp;&middot;&nbsp;TVA {{ $modalTva }} %
                    </p>
                </div>

                {{-- Prix unitaire & remise --}}
                <div class="mb-5 grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Prix unitaire achat HT <span class="text-red-500">*</span></flux:label>
                        <flux:input
                            wire:model.live="modalPuAchatHt"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                        />
                        <flux:error name="modalPuAchatHt" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Taux de remise (%)</flux:label>
                        <flux:input
                            wire:model.live="modalTauxRemise"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            placeholder="0"
                        />
                        <flux:error name="modalTauxRemise" />
                    </flux:field>
                </div>

                {{-- PU Net calculé en temps réel --}}
                <div class="mb-6 flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 border border-gray-200">
                    <span class="text-sm text-gray-600">Prix unitaire net HT calculé</span>
                    <span class="text-base font-bold text-gray-900">
                        {{ number_format($modalPuAchatHt * (1 - $modalTauxRemise / 100), 2) }}
                    </span>
                </div>

                {{-- Répartition par magasin --}}
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="sm">Répartition par magasin</flux:heading>
                    @php $totalQteModal = collect($modalDestinations)->sum('quantite'); @endphp
                    <span class="text-sm text-gray-500">
                        Total :
                        <span class="font-semibold {{ $totalQteModal > 0 ? 'text-indigo-600' : 'text-gray-400' }}">
                            {{ $totalQteModal }} unité(s)
                        </span>
                    </span>
                </div>

                <flux:error name="modalDestinations" class="mb-2" />

                <div class="divide-y divide-gray-100 rounded-lg border border-gray-200 overflow-hidden">
                    @foreach ($modalDestinations as $i => $dest)
                        <div class="flex items-center justify-between px-4 py-3 {{ $dest['quantite'] > 0 ? 'bg-indigo-50' : 'bg-white' }} transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600">
                                    {{ strtoupper(substr($dest['magasin_name'], 0, 2)) }}
                                </div>
                                <span class="text-sm font-medium text-gray-800">{{ $dest['magasin_name'] }}</span>
                            </div>
                            <flux:input
                                wire:model.live="modalDestinations.{{ $i }}.quantite"
                                type="number"
                                min="0"
                                size="sm"
                                class="w-24 text-right"
                                placeholder="0"
                            />
                        </div>
                    @endforeach
                </div>

                {{-- Actions --}}
                <div class="mt-6 flex justify-end gap-3">
                    <flux:button wire:click="closeModal" variant="ghost">Annuler</flux:button>
                    <flux:button wire:click="confirmProduct" variant="primary" icon="check">
                        Confirmer
                    </flux:button>
                </div>

            </div>
        </flux:modal>
    @endif

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- STEP 3 – Récapitulatif                                        -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    @if ($currentStep === 3)
        <flux:card class="p-8">
            <flux:heading size="lg" class="mb-6">Récapitulatif de la commande</flux:heading>

            {{-- Infos générales --}}
            <div class="mb-6 rounded-lg border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-700">Informations générales</h3>
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 px-4 py-4">
                    <div>
                        <dt class="text-xs text-gray-500">Libellé</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $libelle }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Fournisseur</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ $this->fournisseurs->firstWhere('id', $fournisseur_id)?->name ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Magasin de livraison</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ $this->magasins->firstWhere('id', $magasin_livraison_id)?->name ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Remise facture</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $remise_facture }} %</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Montant minimum</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ number_format($montant_minimum, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Délai de paiement</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $nombre_jour }} jours</dd>
                    </div>
                </dl>
            </div>

            {{-- Détail commande --}}
            <div class="mb-6 rounded-lg border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-700">Détail commande ({{ count($lignes) }} produits)</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left   text-xs font-medium text-gray-500 uppercase">Désignation</th>
                        <th class="px-4 py-3 text-right  text-xs font-medium text-gray-500 uppercase">PU HT</th>
                        <th class="px-4 py-3 text-right  text-xs font-medium text-gray-500 uppercase">Remise</th>
                        <th class="px-4 py-3 text-right  text-xs font-medium text-gray-500 uppercase">PU Net</th>
                        <th class="px-4 py-3 text-right  text-xs font-medium text-gray-500 uppercase">TVA</th>
                        <th class="px-4 py-3 text-right  text-xs font-medium text-gray-500 uppercase">Qté</th>
                        <th class="px-4 py-3 text-right  text-xs font-medium text-gray-500 uppercase">Total TTC</th>
                        <th class="px-4 py-3 text-left   text-xs font-medium text-gray-500 uppercase">Destinations</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach ($lignes as $ligne)
                        @php
                            $ht  = $ligne['quantite'] * $ligne['pu_achat_net'];
                            $ttc = $ht * (1 + $ligne['tva'] / 100);
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $ligne['designation'] }}
                                <span class="block text-xs text-gray-400">{{ $ligne['article'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format($ligne['pu_achat_HT'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ $ligne['taux_remise'] }} %</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format($ligne['pu_achat_net'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ $ligne['tva'] }} %</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ $ligne['quantite'] }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format($ttc, 2) }}</td>
                            <td class="px-4 py-3">
                                @foreach ($ligne['destinations'] as $dest)
                                    <span class="block text-xs text-gray-600">
                                            <span class="font-medium">{{ $dest['magasin_name'] }}</span> : {{ $dest['quantite'] }}
                                        </span>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot class="bg-indigo-50">
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-right text-sm font-semibold text-indigo-700">Total TTC</td>
                        <td class="px-4 py-3 text-right text-base font-bold text-indigo-900">{{ number_format($this->montantTotal, 2) }}</td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
            </div>

            <div class="flex justify-between">
                <flux:button wire:click="prevStep" variant="ghost">&larr; Retour</flux:button>
                <flux:button wire:click="save" variant="primary" icon="check">Confirmer la commande</flux:button>
            </div>
        </flux:card>
    @endif
</div>
