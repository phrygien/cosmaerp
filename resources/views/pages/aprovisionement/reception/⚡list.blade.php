<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\ReceptionCommande;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterState = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterState(): void { $this->resetPage(); }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[Computed]
    public function receptions()
    {
        return ReceptionCommande::query()
            ->with(['bon_commande', 'commande', 'detail_commande'])
            ->when($this->search, fn($q) =>
            $q->whereHas('bon_commande', fn($q) => $q->where('reference', 'like', "%{$this->search}%"))
                ->orWhereHas('commande', fn($q) => $q->where('reference', 'like', "%{$this->search}%"))
            )
            ->when($this->filterState, fn($q) => $q->where('state', $this->filterState))
            ->tap(fn($q) => $this->sortBy ? $q->orderBy($this->sortBy, $this->sortDirection) : $q)
            ->paginate(15);
    }
};
?>

<div class="mt-5">
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">{{ __('Réception des commandes') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Liste') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Réception des commandes') }}</flux:heading>
    </div>

    {{-- Filtres --}}
    <div class="flex flex-wrap gap-4 mb-5">
        <div class="flex-1 min-w-60">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Rechercher par référence...') }}"
                icon="magnifying-glass"
                clearable
            />
        </div>

        <flux:select wire:model.live="filterState" placeholder="{{ __('Tous les états') }}" class="w-48">
            <flux:select.option value="">{{ __('Tous les états') }}</flux:select.option>
            <flux:select.option value="pending">{{ __('En attente') }}</flux:select.option>
            <flux:select.option value="received">{{ __('Reçu') }}</flux:select.option>
            <flux:select.option value="partial">{{ __('Partiel') }}</flux:select.option>
            <flux:select.option value="rejected">{{ __('Rejeté') }}</flux:select.option>
        </flux:select>
    </div>

    {{-- Table --}}
    <flux:table :paginate="$this->receptions">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">#</flux:table.column>
            <flux:table.column>{{ __('Bon de commande') }}</flux:table.column>
            <flux:table.column>{{ __('Commande') }}</flux:table.column>
            <flux:table.column>{{ __('Détail commande') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'recu'" :direction="$sortDirection" wire:click="sort('recu')">{{ __('Reçu') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'invendable'" :direction="$sortDirection" wire:click="sort('invendable')">{{ __('Invendable') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'state'" :direction="$sortDirection" wire:click="sort('state')">{{ __('État') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">{{ __('Date') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->receptions as $reception)
                <flux:table.row :key="$reception->id">

                    <flux:table.cell class="text-zinc-500 text-sm">
                        #{{ $reception->id }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $reception->bon_commande?->reference ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $reception->commande?->reference ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $reception->detail_commande?->reference ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell variant="strong">
                        {{ $reception->recu ?? 0 }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($reception->invendable > 0)
                            <flux:badge size="sm" color="red" inset="top bottom">{{ $reception->invendable }}</flux:badge>
                        @else
                            <span class="text-zinc-400">0</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @php
                            $badge = match($reception->state) {
                                'received' => ['color' => 'green',  'label' => __('Reçu')],
                                'partial'  => ['color' => 'yellow', 'label' => __('Partiel')],
                                'rejected' => ['color' => 'red',    'label' => __('Rejeté')],
                                default    => ['color' => 'zinc',   'label' => __('En attente')],
                            };
                        @endphp
                        <flux:badge size="sm" :color="$badge['color']" inset="top bottom">
                            {{ $badge['label'] }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-500 text-sm whitespace-nowrap">
                        {{ $reception->created_at->format('d/m/Y H:i') }}
                    </flux:table.cell>

                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
