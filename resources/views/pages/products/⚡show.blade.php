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

<div class="max-w-6xl mx-auto">

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

            <!-- Visuel produit (placeholder selon le type) -->
            @php
                $typeName = strtoupper(trim($product->type?->name ?? ''));
            @endphp
            <flux:card class="p-0 overflow-hidden">
                <div class="aspect-square w-full bg-zinc-100 dark:bg-zinc-800/60 flex items-center justify-center p-8">

                    @if($typeName === 'FRAGRANCE')
                        {{-- Flacon de parfum, version décorative (volutes & feuillage, niveaux de gris) --}}
                        <svg viewBox="0 0 200 300" class="w-full h-full max-w-[220px]" xmlns="http://www.w3.org/2000/svg">
                            <ellipse cx="100" cy="278" rx="60" ry="9" fill="#000000" opacity="0.06" />

                            <!-- Volutes décoratives gauche -->
                            <g stroke="#c4c4c9" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.8">
                                <path d="M40 260 C10 250 8 210 28 195 C12 190 8 160 26 145" />
                                <path d="M28 195 C14 200 -2 188 4 168" />
                            </g>
                            <ellipse cx="24" cy="146" rx="9" ry="5" fill="#d4d4d8" transform="rotate(-35 24 146)" />
                            <ellipse cx="6" cy="167" rx="7" ry="4" fill="#e4e4e7" transform="rotate(20 6 167)" />
                            <circle cx="18" cy="120" r="3" fill="#c4c4c9" />
                            <circle cx="30" cy="108" r="2" fill="#d4d4d8" />

                            <!-- Volutes décoratives droite -->
                            <g stroke="#c4c4c9" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.8">
                                <path d="M160 260 C190 250 192 210 172 195 C188 190 192 160 174 145" />
                                <path d="M172 195 C186 200 202 188 196 168" />
                            </g>
                            <ellipse cx="176" cy="146" rx="9" ry="5" fill="#d4d4d8" transform="rotate(35 176 146)" />
                            <ellipse cx="194" cy="167" rx="7" ry="4" fill="#e4e4e7" transform="rotate(-20 194 167)" />
                            <circle cx="182" cy="120" r="3" fill="#c4c4c9" />
                            <circle cx="170" cy="108" r="2" fill="#d4d4d8" />

                            <!-- Bouchon rond -->
                            <circle cx="100" cy="52" r="30" fill="#a1a1aa" />
                            <circle cx="100" cy="52" r="30" fill="url(#gradCapFragrance)" />
                            <ellipse cx="90" cy="40" rx="9" ry="13" fill="#e4e4e7" opacity="0.45" />

                            <!-- Col -->
                            <rect x="90" y="80" width="20" height="16" fill="#a1a1aa" />

                            <!-- Épaules -->
                            <path d="M90 96 C90 96 66 106 58 122 L142 122 C134 106 110 96 110 96 Z" fill="#c4c4c9" />

                            <!-- Corps globe -->
                            <ellipse cx="100" cy="196" rx="72" ry="82" fill="#d4d4d8" />
                            <ellipse cx="100" cy="196" rx="72" ry="82" fill="url(#gradBodyFragrance)" />
                            <ellipse cx="76" cy="160" rx="10" ry="46" fill="#f4f4f5" opacity="0.5" />

                            <!-- Médaillon central avec motif floral discret -->
                            <circle cx="100" cy="200" r="46" fill="#f4f4f5" stroke="#a1a1aa" stroke-width="1.5" />
                            <g stroke="#c4c4c9" stroke-width="1.5" fill="none" stroke-linecap="round">
                                <path d="M100 180 C92 186 92 196 100 202 C108 196 108 186 100 180 Z" />
                                <path d="M78 200 C86 194 96 196 100 202" />
                                <path d="M122 200 C114 194 104 196 100 202" />
                                <path d="M100 202 C96 212 96 220 100 226" />
                            </g>
                            <circle cx="100" cy="200" r="3.5" fill="#a1a1aa" />

                            <!-- Base -->
                            <ellipse cx="100" cy="270" rx="58" ry="10" fill="#a1a1aa" />

                            <defs>
                                <linearGradient id="gradCapFragrance" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#e4e4e7" stop-opacity="0.6" />
                                    <stop offset="100%" stop-color="#a1a1aa" stop-opacity="0.3" />
                                </linearGradient>
                                <linearGradient id="gradBodyFragrance" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#e4e4e7" stop-opacity="0.6" />
                                    <stop offset="100%" stop-color="#a1a1aa" stop-opacity="0.35" />
                                </linearGradient>
                            </defs>
                        </svg>

                    @elseif($typeName === 'BEAUTY')
                        {{-- Pot de crème / soin --}}
                        <svg viewBox="0 0 200 300" class="w-full h-full max-w-[220px]" xmlns="http://www.w3.org/2000/svg">
                            <ellipse cx="100" cy="270" rx="60" ry="10" fill="#000000" opacity="0.06" />
                            <!-- Couvercle -->
                            <rect x="40" y="40" width="120" height="46" rx="14" fill="#a1a1aa" />
                            <rect x="40" y="40" width="120" height="16" rx="8" fill="#c4c4c9" />
                            <!-- Corps du pot -->
                            <rect x="48" y="84" width="104" height="166" rx="24" fill="#d4d4d8" />
                            <rect x="48" y="84" width="104" height="166" rx="24" fill="url(#gradBeauty)" />
                            <!-- Reflet -->
                            <rect x="60" y="100" width="10" height="130" rx="5" fill="#f4f4f5" opacity="0.5" />
                            <!-- Étiquette -->
                            <rect x="66" y="150" width="68" height="54" rx="4" fill="#f4f4f5" stroke="#a1a1aa" stroke-width="1.5" />
                            <circle cx="100" cy="167" r="6" fill="none" stroke="#a1a1aa" stroke-width="2" />
                            <line x1="76" y1="185" x2="124" y2="185" stroke="#c4c4c9" stroke-width="2" stroke-linecap="round" />
                            <line x1="82" y1="194" x2="118" y2="194" stroke="#c4c4c9" stroke-width="2" stroke-linecap="round" />
                            <!-- Base -->
                            <rect x="48" y="236" width="104" height="14" rx="7" fill="#a1a1aa" />
                            <defs>
                                <linearGradient id="gradBeauty" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#e4e4e7" stop-opacity="0.6" />
                                    <stop offset="100%" stop-color="#a1a1aa" stop-opacity="0.35" />
                                </linearGradient>
                            </defs>
                        </svg>

                    @elseif($typeName === 'MAKE UP' || $typeName === 'MAKEUP')
                        {{-- Rouge à lèvres --}}
                        <svg viewBox="0 0 200 300" class="w-full h-full max-w-[220px]" xmlns="http://www.w3.org/2000/svg">
                            <ellipse cx="100" cy="270" rx="45" ry="9" fill="#000000" opacity="0.06" />
                            <!-- Capuchon (posé à côté, légèrement incliné) -->
                            <g transform="rotate(-8 40 210)">
                                <rect x="20" y="150" width="40" height="120" rx="10" fill="#c4c4c9" />
                                <rect x="20" y="150" width="40" height="18" rx="9" fill="#a1a1aa" />
                            </g>
                            <!-- Tube -->
                            <rect x="82" y="150" width="46" height="108" rx="8" fill="#a1a1aa" />
                            <rect x="82" y="150" width="46" height="14" fill="#c4c4c9" />
                            <line x1="90" y1="180" x2="120" y2="180" stroke="#71717a" stroke-width="1.5" opacity="0.5" />
                            <line x1="90" y1="200" x2="120" y2="200" stroke="#71717a" stroke-width="1.5" opacity="0.5" />
                            <line x1="90" y1="220" x2="120" y2="220" stroke="#71717a" stroke-width="1.5" opacity="0.5" />
                            <!-- Base élevatrice -->
                            <rect x="88" y="130" width="34" height="24" fill="#d4d4d8" />
                            <!-- Bâton (pointe biseautée) -->
                            <path d="M88 130 L122 130 L122 78 C122 78 108 58 105 40 C102 58 88 78 88 78 Z" fill="#e4e4e7" stroke="#a1a1aa" stroke-width="1.5" />
                            <path d="M105 40 C108 58 122 78 122 78 L112 78 C106 66 104 52 105 40 Z" fill="#c4c4c9" opacity="0.6" />
                        </svg>

                    @else
                        {{-- Générique (type non défini) --}}
                        <svg viewBox="0 0 200 300" class="w-full h-full max-w-[220px]" xmlns="http://www.w3.org/2000/svg">
                            <ellipse cx="100" cy="270" rx="55" ry="10" fill="#000000" opacity="0.06" />
                            <!-- Boîte produit générique -->
                            <rect x="45" y="70" width="110" height="180" rx="10" fill="#d4d4d8" />
                            <rect x="45" y="70" width="110" height="180" rx="10" fill="url(#gradGeneric)" />
                            <rect x="45" y="70" width="110" height="34" rx="10" fill="#c4c4c9" />
                            <!-- Ruban / repère -->
                            <rect x="92" y="70" width="16" height="180" fill="#a1a1aa" opacity="0.4" />
                            <!-- Icône point d'interrogation -->
                            <circle cx="100" cy="175" r="30" fill="#f4f4f5" stroke="#a1a1aa" stroke-width="2" />
                            <text x="100" y="187" font-family="sans-serif" font-size="34" font-weight="600" text-anchor="middle" fill="#a1a1aa">?</text>
                            <defs>
                                <linearGradient id="gradGeneric" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#e4e4e7" stop-opacity="0.6" />
                                    <stop offset="100%" stop-color="#a1a1aa" stop-opacity="0.35" />
                                </linearGradient>
                            </defs>
                        </svg>
                    @endif

                </div>
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                    <p class="text-xs text-center text-zinc-400">
                        Visuel non contractuel
                        @if($product->type?->name)
                            &middot; {{ $product->type->name }}
                        @endif
                    </p>
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
