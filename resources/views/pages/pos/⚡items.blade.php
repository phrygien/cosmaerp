<?php

use Livewire\Component;

new class extends Component
{
    public array $cartItems = [
        ['id' => 1, 'name' => 'Espresso', 'quantity' => 2, 'price' => 2.50],
        ['id' => 3, 'name' => 'Croissant', 'quantity' => 1, 'price' => 2.00],
        ['id' => 5, 'name' => 'Orange Juice', 'quantity' => 1, 'price' => 2.75],
    ];

    public float $taxRate = 0.20;

    public function increment($id): void
    {
        foreach ($this->cartItems as &$item) {
            if ($item['id'] === $id) {
                $item['quantity']++;
            }
        }
    }

    public function decrement($id): void
    {
        foreach ($this->cartItems as $key => &$item) {
            if ($item['id'] === $id) {
                $item['quantity']--;
                if ($item['quantity'] <= 0) {
                    unset($this->cartItems[$key]);
                }
            }
        }
        $this->cartItems = array_values($this->cartItems);
    }

    public function remove($id): void
    {
        $this->cartItems = array_values(
            array_filter($this->cartItems, fn ($item) => $item['id'] !== $id)
        );
    }

    public function getSubtotalProperty(): float
    {
        return collect($this->cartItems)->sum(fn ($item) => $item['quantity'] * $item['price']);
    }

    public function getTaxAmountProperty(): float
    {
        return $this->subtotal * $this->taxRate;
    }

    public function getTotalProperty(): float
    {
        return $this->subtotal + $this->taxAmount;
    }

    public function getItemsCountProperty(): int
    {
        return collect($this->cartItems)->sum('quantity');
    }
};
?>

<div class="flex flex-col h-full max-h-[70vh] md:max-h-none">

    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
        <flux:heading size="lg" class="text-zinc-900 dark:text-white">Order Items</flux:heading>
        @if($this->itemsCount > 0)
            <flux:badge color="zinc">{{ $this->itemsCount }} {{ $this->itemsCount > 1 ? 'items' : 'item' }}</flux:badge>
        @endif
    </div>

    <div class="flex-1 overflow-y-auto space-y-2 pr-1 min-h-[120px]">
        @forelse ($cartItems as $item)
            <flux:card class="p-3">
                <div class="flex flex-col xs:flex-row items-start xs:items-center justify-between gap-2 xs:gap-3">
                    <div class="min-w-0 w-full xs:w-auto">
                        <div class="font-medium truncate text-zinc-900 dark:text-white">
                            {{ $item['name'] }}
                        </div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ number_format($item['price'], 2) }} × {{ $item['quantity'] }}
                            = <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                {{ number_format($item['price'] * $item['quantity'], 2) }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0 self-end xs:self-auto">
                        <flux:button size="sm" icon="minus" variant="subtle" wire:click="decrement({{ $item['id'] }})" />
                        <span class="w-5 text-center text-sm font-medium text-zinc-900 dark:text-white">
                            {{ $item['quantity'] }}
                        </span>
                        <flux:button size="sm" icon="plus" variant="subtle" wire:click="increment({{ $item['id'] }})" />
                        <flux:button size="sm" icon="trash" variant="danger" wire:click="remove({{ $item['id'] }})" />
                    </div>
                </div>
            </flux:card>
        @empty
            <div class="text-center text-zinc-400 dark:text-zinc-500 py-12">
                No items yet
            </div>
        @endforelse
    </div>

    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-4 space-y-1">
        <div class="flex justify-between text-sm text-zinc-500 dark:text-zinc-400">
            <span>Subtotal</span>
            <span class="text-zinc-700 dark:text-zinc-300">{{ number_format($this->subtotal, 2) }}</span>
        </div>
        <div class="flex justify-between text-sm text-zinc-500 dark:text-zinc-400">
            <span>Tax ({{ $taxRate * 100 }}%)</span>
            <span class="text-zinc-700 dark:text-zinc-300">{{ number_format($this->taxAmount, 2) }}</span>
        </div>
        <div class="flex justify-between font-semibold text-lg pt-2 border-t border-zinc-200 dark:border-zinc-700 mt-2 text-zinc-900 dark:text-white">
            <span>Total</span>
            <span>{{ number_format($this->total, 2) }}</span>
        </div>

        <flux:button variant="primary" class="w-full mt-3" :disabled="$this->itemsCount === 0">
            Checkout — {{ number_format($this->total, 2) }}
        </flux:button>
    </div>
</div>
