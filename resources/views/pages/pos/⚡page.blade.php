<?php

use App\Models\Product;
use Livewire\Component;

new class extends Component
{
    public string $search = '';

    public function getProductsProperty()
    {
        return Product::with(['marque', 'categorie'])
            ->when($this->search, function ($query) {
                $query->where('EAN', 'like', "%{$this->search}%")
                    ->orWhere('designation', 'like', "%{$this->search}%")
                    ->orWhere('article', 'like', "%{$this->search}%");
            })
            ->take(5)
            ->get();
    }
};
?>

<div class="max-w-7xl mx-auto px-3 sm:px-4">
    <div class="mb-2 mt-2">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Search by EAN code, name or reference"
        />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

        <!-- Liste produits : une card par produit -->
        <div class="space-y-3 order-2 md:order-1">
            @forelse ($this->products as $product)
                <flux:card>
                    <div class="flex items-center gap-4">

                        <!-- Image / illustration produit -->
                        <div class="w-24 h-24 shrink-0 bg-zinc-50 dark:bg-zinc-800 rounded-lg overflow-hidden flex items-center justify-center">
                            @if($product->image ?? false)
                                <img src="{{ $product->image }}" class="object-cover w-full h-full" />
                            @else
                                <svg viewBox="0 0 200 300" class="w-full h-full max-w-[160px]" xmlns="http://www.w3.org/2000/svg">
                                    <ellipse cx="100" cy="278" rx="60" ry="9" fill="#000000" opacity="0.10" />
                                    <path d="M78 18 c-3-4-9-2-9 2 c0 4 9 10 9 10 s9-6 9-10 c0-4-6-6-9-2 Z" fill="#db2777" opacity="0.9" />
                                    <path d="M120 10 c-2-3-6-1-6 1 c0 3 6 7 6 7 s6-4 6-7 c0-2-4-4-6-1 Z" fill="#f472b6" opacity="0.85" />
                                    <g stroke="#18181b" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.85">
                                        <path d="M40 260 C10 250 8 210 28 195 C12 190 8 160 26 145" />
                                        <path d="M28 195 C14 200 -2 188 4 168" />
                                    </g>
                                    <ellipse cx="24" cy="146" rx="9" ry="5" fill="#db2777" transform="rotate(-35 24 146)" opacity="0.85" />
                                    <ellipse cx="6" cy="167" rx="7" ry="4" fill="#f9a8d4" transform="rotate(20 6 167)" />
                                    <circle cx="18" cy="120" r="3" fill="#18181b" opacity="0.7" />
                                    <circle cx="30" cy="108" r="2" fill="#db2777" />
                                    <g stroke="#18181b" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.85">
                                        <path d="M160 260 C190 250 192 210 172 195 C188 190 192 160 174 145" />
                                        <path d="M172 195 C186 200 202 188 196 168" />
                                    </g>
                                    <ellipse cx="176" cy="146" rx="9" ry="5" fill="#db2777" transform="rotate(35 176 146)" opacity="0.85" />
                                    <ellipse cx="194" cy="167" rx="7" ry="4" fill="#f9a8d4" transform="rotate(-20 194 167)" />
                                    <circle cx="182" cy="120" r="3" fill="#18181b" opacity="0.7" />
                                    <circle cx="170" cy="108" r="2" fill="#db2777" />
                                    <circle cx="100" cy="52" r="30" fill="#18181b" />
                                    <circle cx="100" cy="52" r="30" fill="url(#gradCapFragrance{{ $product->id }})" />
                                    <ellipse cx="90" cy="40" rx="9" ry="13" fill="#fce7f3" opacity="0.25" />
                                    <rect x="90" y="80" width="20" height="16" fill="#18181b" />
                                    <path d="M90 96 C90 96 66 106 58 122 L142 122 C134 106 110 96 110 96 Z" fill="#27272a" />
                                    <ellipse cx="100" cy="196" rx="72" ry="82" fill="#be185d" />
                                    <ellipse cx="100" cy="196" rx="72" ry="82" fill="url(#gradBodyFragrance{{ $product->id }})" />
                                    <ellipse cx="100" cy="196" rx="72" ry="82" fill="none" stroke="#18181b" stroke-width="2" opacity="0.15" />
                                    <ellipse cx="76" cy="160" rx="10" ry="46" fill="#fce7f3" opacity="0.35" />
                                    <circle cx="100" cy="200" r="46" fill="#fdf2f8" stroke="#db2777" stroke-width="1.5" />
                                    <g stroke="#db2777" stroke-width="1.5" fill="none" stroke-linecap="round">
                                        <path d="M100 180 C92 186 92 196 100 202 C108 196 108 186 100 180 Z" />
                                        <path d="M78 200 C86 194 96 196 100 202" />
                                        <path d="M122 200 C114 194 104 196 100 202" />
                                        <path d="M100 202 C96 212 96 220 100 226" />
                                    </g>
                                    <circle cx="100" cy="200" r="3.5" fill="#db2777" />
                                    <ellipse cx="100" cy="270" rx="58" ry="10" fill="#18181b" />
                                    <defs>
                                        <linearGradient id="gradCapFragrance{{ $product->id }}" x1="0" y1="0" x2="1" y2="1">
                                            <stop offset="0%" stop-color="#3f3f46" stop-opacity="0.7" />
                                            <stop offset="100%" stop-color="#18181b" stop-opacity="0.3" />
                                        </linearGradient>
                                        <linearGradient id="gradBodyFragrance{{ $product->id }}" x1="0" y1="0" x2="1" y2="1">
                                            <stop offset="0%" stop-color="#f472b6" stop-opacity="0.65" />
                                            <stop offset="100%" stop-color="#831843" stop-opacity="0.45" />
                                        </linearGradient>
                                    </defs>
                                </svg>
                            @endif
                        </div>

                        <!-- Infos -->
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-zinc-900 dark:text-white truncate">
                                {{ $product->designation }}
                            </div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400 truncate">
                                {{ $product->marque?->name }}
                                @if($product->categorie?->name)
                                    · {{ $product->categorie->name }}
                                @endif
                                @if($product->EAN)
                                    <span class="ml-1 text-zinc-400 dark:text-zinc-500">· EAN {{ $product->EAN }}</span>
                                @endif
                            </div>
                            <div class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ number_format($product->pght_parkod, 2) }} {{ $product->devise }}
                            </div>
                        </div>
                    </div>
                </flux:card>
            @empty
                <flux:card>
                    <div class="text-center text-zinc-400 dark:text-zinc-500 py-8">
                        No products found
                    </div>
                </flux:card>
            @endforelse
        </div>

        <!-- Panier -->
        <div class="order-1 md:order-2">
            <flux:card class="md:sticky md:top-4 flex flex-col h-auto md:h-[calc(100vh-8rem)]">
                <livewire:pages::pos.items />
            </flux:card>
        </div>
    </div>
</div>
