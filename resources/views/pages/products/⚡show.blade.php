<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Flux\Flux;

new class extends Component
{
    public Product $product;

    public bool $updatingState = false;

    public function mount(Product $product): void
    {
        $this->product = $product->load(['marque', 'categorie', 'type', 'ligne']);
    }

    #[On('product-updated')]
    public function refresh(): void
    {
        $this->product->refresh();
        $this->product->load(['marque', 'categorie', 'type', 'ligne']);
    }

    public function edit(): void
    {
        $this->dispatch('edit-product', id: $this->product->id);
    }

    public function confirmDelete(): void
    {
        $this->dispatch('delete-product', id: $this->product->id);
    }

    public function back(): void
    {
        $this->redirect(route('catalogue.products'), navigate: true);
    }

    public function toggleState(): void
    {
        $this->updatingState = true;

        try {
            DB::beginTransaction();

            $newState = $this->product->state == 1 ? 0 : 1;
            $this->product->state = $newState;
            $this->product->save();

            DB::commit();

            $this->dispatch('product-state-updated', id: $this->product->id, state: $newState);

            Flux::toast(
                heading: $newState == 1 ? 'Produit activé' : 'Produit désactivé',
                text: "Le produit \"{$this->product->designation}\" a été " . ($newState == 1 ? 'activé' : 'désactivé') . ' avec succès',
                variant: 'success'
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Flux::toast(
                heading: 'Erreur',
                text: "Impossible de modifier l'état du produit : " . $e->getMessage(),
                variant: 'danger'
            );
        } finally {
            $this->updatingState = false;
        }
    }
};
?>

<div class="max-w-7xl mx-auto">

    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('catalogue.products') }}" wire:navigate>Produit</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $product->designation }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <!-- En-tête -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
        <div class="flex items-start gap-3">
            <flux:button
                wire:click="back"
                variant="ghost"
                icon="arrow-left"
                size="sm"
                inset
            />
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <flux:heading size="xl" level="1">{{ $product->designation }}</flux:heading>
                    <flux:badge size="sm" :color="$product->state == 1 ? 'green' : 'zinc'">
                        {{ $product->state == 1 ? 'Actif' : 'Inactif' }}
                    </flux:badge>
                </div>
                @if($product->designation_variant)
                    <p class="text-sm text-zinc-500 mt-1">{{ $product->designation_variant }}</p>
                @endif
                <div class="flex items-center gap-2 mt-2">
                    <flux:badge size="sm" color="zinc" inset="top bottom">
                        {{ $product->article ?? $product->product_code }}
                    </flux:badge>
                    @if($product->marque)
                        <span class="text-xs text-zinc-400">•</span>
                        <span class="text-xs text-zinc-500">{{ $product->marque->name }}</span>
                    @endif
                    @if($product->categorie)
                        <span class="text-xs text-zinc-400">•</span>
                        <span class="text-xs text-zinc-500">{{ $product->categorie->name }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <flux:button wire:click="edit" variant="filled" icon="pencil">
                Modifier
            </flux:button>
            <flux:button wire:click="confirmDelete" variant="danger" icon="trash">
                Supprimer
            </flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Colonne gauche : visuel + code-barres + état -->
        <div class="lg:col-span-1 space-y-6">

            <!-- Visuel produit (placeholder) -->
            <flux:card class="p-0 overflow-hidden">
                <div class="aspect-square w-full bg-zinc-100 dark:bg-zinc-800/60 flex items-center justify-center p-8">
                    <svg viewBox="0 0 200 300" class="w-full h-full max-w-[220px]" xmlns="http://www.w3.org/2000/svg">
                        <!-- Ombre portée -->
                        <ellipse cx="100" cy="270" rx="55" ry="10" fill="#000000" opacity="0.06" />

                        <!-- Bouchon -->
                        <rect x="78" y="8" width="44" height="20" rx="4" fill="#a1a1aa" />
                        <rect x="78" y="8" width="44" height="7" rx="3.5" fill="#d4d4d8" />

                        <!-- Col du flacon -->
                        <rect x="90" y="26" width="20" height="18" fill="#a1a1aa" />

                        <!-- Épaules -->
                        <path d="M90 44 C90 44 70 52 60 66 L140 66 C130 52 110 44 110 44 Z" fill="#c4c4c9" />

                        <!-- Corps du flacon -->
                        <rect x="45" y="66" width="110" height="190" rx="16" fill="#d4d4d8" />
                        <rect x="45" y="66" width="110" height="190" rx="16" fill="url(#glassGradient)" />

                        <!-- Reflet -->
                        <rect x="58" y="80" width="12" height="160" rx="6" fill="#f4f4f5" opacity="0.55" />

                        <!-- Étiquette -->
                        <rect x="62" y="130" width="76" height="66" rx="4" fill="#f4f4f5" stroke="#a1a1aa" stroke-width="1.5" />
                        <line x1="72" y1="148" x2="128" y2="148" stroke="#a1a1aa" stroke-width="2" stroke-linecap="round" />
                        <line x1="72" y1="158" x2="118" y2="158" stroke="#c4c4c9" stroke-width="2" stroke-linecap="round" />
                        <line x1="72" y1="168" x2="122" y2="168" stroke="#c4c4c9" stroke-width="2" stroke-linecap="round" />
                        <line x1="72" y1="180" x2="100" y2="180" stroke="#c4c4c9" stroke-width="2" stroke-linecap="round" />

                        <!-- Base -->
                        <rect x="45" y="240" width="110" height="16" rx="8" fill="#a1a1aa" />

                        <defs>
                            <linearGradient id="glassGradient" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#e4e4e7" stop-opacity="0.6" />
                                <stop offset="100%" stop-color="#a1a1aa" stop-opacity="0.35" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                    <p class="text-xs text-center text-zinc-400">Visuel non contractuel</p>
                </div>
            </flux:card>

            <!-- Code-barres -->
            <flux:card class="p-5">
                <flux:heading size="sm" class="mb-3">Code-barres</flux:heading>
                @if($product->EAN)
                    <div class="flex flex-col items-center gap-2 py-2">
                        <div style="line-height:0">
                            {!! DNS1D::getBarcodeSVG(
                                $product->EAN,
                                strlen($product->EAN) === 8 ? 'EAN8' : 'EAN13',
                                2,
                                60,
                                'black',
                                false
                            ) !!}
                        </div>
                        <span class="text-sm font-mono tracking-widest select-all text-zinc-600 dark:text-zinc-300">
                            {{ $product->EAN }}
                        </span>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-6 text-center">
                        <flux:icon name="qr-code" class="text-zinc-300 mb-2" style="width: 28px; height: 28px;" />
                        <p class="text-xs text-zinc-400">Aucun EAN renseigné</p>
                    </div>
                @endif
            </flux:card>

            <!-- État -->
            <flux:card class="p-5">
                <flux:heading size="sm" class="mb-3">État du produit</flux:heading>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-zinc-500">
                        {{ $product->state == 1 ? 'Ce produit est actif' : 'Ce produit est inactif' }}
                    </span>
                    @if($updatingState)
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                    @else
                        <button
                            wire:click="toggleState"
                            type="button"
                            role="switch"
                            aria-checked="{{ $product->state == 1 ? 'true' : 'false' }}"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 hover:opacity-80"
                            style="background-color: {{ $product->state == 1 ? '#22c55e' : '#d1d5db' }}"
                        >
                            <span
                                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                style="transform: translateX({{ $product->state == 1 ? '24px' : '4px' }})"
                            />
                        </button>
                    @endif
                </div>
            </flux:card>

        </div>

        <!-- Colonne droite : détails -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Identification -->
            <flux:card class="p-5">
                <flux:heading size="sm" class="mb-4">Identification</flux:heading>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Code produit</p>
                        <p class="text-sm font-medium mt-0.5">{{ $product->product_code ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Article</p>
                        <p class="text-sm font-medium mt-0.5">{{ $product->article ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Désignation</p>
                        <p class="text-sm font-medium mt-0.5">{{ $product->designation ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Désignation variante</p>
                        <p class="text-sm font-medium mt-0.5">{{ $product->designation_variant ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Référence fabricant</p>
                        <p class="text-sm font-medium mt-0.5">{{ $product->ref_fabri_n_1 ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">EAN</p>
                        <p class="text-sm font-medium mt-0.5 font-mono">{{ $product->EAN ?? '—' }}</p>
                    </div>
                </div>
            </flux:card>

            <!-- Classification -->
            <flux:card class="p-5">
                <flux:heading size="sm" class="mb-4">Classification</flux:heading>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Marque</p>
                        <p class="text-sm font-medium mt-0.5">
                            {{ $product->marque?->name ?? '—' }}
                            @if($product->marque_code)
                                <span class="text-zinc-400 font-normal">({{ $product->marque_code }})</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Catégorie</p>
                        <p class="text-sm font-medium mt-0.5">
                            {{ $product->categorie?->name ?? '—' }}
                            @if($product->categorie_code)
                                <span class="text-zinc-400 font-normal">({{ $product->categorie_code }})</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Ligne</p>
                        <p class="text-sm font-medium mt-0.5">
                            {{ $product->ligne?->name ?? '—' }}
                            @if($product->ligne_code)
                                <span class="text-zinc-400 font-normal">({{ $product->ligne_code }})</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Type</p>
                        <p class="text-sm font-medium mt-0.5">{{ $product->type?->name ?? '—' }}</p>
                    </div>
                </div>
            </flux:card>

            <!-- Tarification & douane -->
            <flux:card class="p-5">
                <flux:heading size="sm" class="mb-4">Tarification &amp; douane</flux:heading>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Prix PARKOD</p>
                        <p class="text-sm font-medium mt-0.5">
                            @if($product->pght_parkod !== null)
                                {{ number_format((float) $product->pght_parkod, 2, ',', ' ') }} {{ $product->devise }}
                            @else
                                —
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">TVA</p>
                        <p class="text-sm font-medium mt-0.5">
                            {{ $product->tva !== null ? number_format((float) $product->tva, 2, ',', ' ') . ' %' : '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Devise</p>
                        <p class="text-sm font-medium mt-0.5">{{ $product->devise ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Code HS</p>
                        <p class="text-sm font-medium mt-0.5 font-mono">{{ $product->hs_code ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Statut PARKOD</p>
                        <p class="text-sm font-medium mt-0.5">{{ $product->statut_parkod ?? '—' }}</p>
                    </div>
                </div>
            </flux:card>

            <!-- Métadonnées -->
            <flux:card class="p-5">
                <flux:heading size="sm" class="mb-4">Métadonnées</flux:heading>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Créé le</p>
                        <p class="text-sm font-medium mt-0.5">
                            {{ $product->created_at?->format('d/m/Y à H:i') ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide">Modifié le</p>
                        <p class="text-sm font-medium mt-0.5">
                            {{ $product->updated_at?->format('d/m/Y à H:i') ?? '—' }}
                        </p>
                    </div>
                </div>
            </flux:card>

        </div>
    </div>

</div>
