<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\BonCommande;
use App\Models\ReceptionCommande;
use App\Enums\CommandeStatus;
use Flux\Flux;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'tri')]
    public string $sortBy = 'created_at';

    #[Url(as: 'ordre')]
    public string $sortDirection = 'desc';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'par_page', except: 15)]
    public int $perPage = 15;

    public ?int $deleteId        = null;
    public bool $showDeleteModal = false;

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy        = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch(): void  { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'perPage']);
        $this->resetPage();
        Flux::toast(heading: 'Filtres réinitialisés', text: 'Tous les filtres ont été réinitialisés.', variant: 'info');
    }

    public function openDetail(int $id): void
    {
        $this->dispatch('open-detail-reception', id: $id);
    }

    public function confirmDelete(int $bonCommandeId): void
    {
        $this->deleteId        = $bonCommandeId;
        $this->showDeleteModal = true;
    }

    public function supprimer(): void
    {
        $bon = BonCommande::with('commande')->find($this->deleteId);

        if (!$bon) {
            Flux::toast(heading: 'Erreur', text: 'Bon de commande introuvable.', variant: 'danger');
            $this->showDeleteModal = false;
            return;
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($bon) {
                $commande = $bon->commande;

                if ($commande) {
                    \App\Models\StockMagasin::whereIn(
                        'detail_commande_id',
                        \App\Models\DetailCommande::where('commande_id', $commande->id)->pluck('id')
                    )->delete();

                    $commande->update([
                        'status'         => CommandeStatus::Cloturee,
                        'date_reception' => null,
                    ]);
                }

                ReceptionCommande::where('bon_commande_id', $bon->id)->delete();
            });

            Flux::toast(
                heading: 'Réception supprimée',
                text: 'La réception et le stock associé ont été réinitialisés.',
                variant: 'success'
            );
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Erreur', text: 'Impossible de supprimer : ' . $e->getMessage(), variant: 'danger');
        }

        $this->showDeleteModal = false;
        $this->deleteId        = null;
        unset($this->bonCommandes);
    }

    #[Computed]
    public function bonCommandes()
    {
        return BonCommande::query()
            ->with([
                'commande.fournisseur',
                'commande.magasinLivraison',
                'magasinLivraison',
            ])
            ->withCount('receptions')
            ->withSum('receptions', 'recu')
            ->withSum('receptions', 'invendable')
            ->whereHas('receptions')
            ->whereHas('commande')
            ->when($this->search, fn($q) =>
            $q->whereHas('commande.fournisseur', fn($q) =>
            $q->where('name', 'like', "%{$this->search}%")
            )
                ->orWhereHas('commande', fn($q) =>
                $q->where('libelle', 'like', "%{$this->search}%")
                )
                ->orWhere('numero_compte', 'like', "%{$this->search}%")
                ->orWhere('code_fournisseur', 'like', "%{$this->search}%")
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">{{ __('Approvisionnement') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Réceptions') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Réceptions de commandes') }}</flux:heading>

        <flux:button
            href="{{ route('reception_commande.create') }}"
            wire:navigate
            variant="primary"
        >
            {{ __('Nouvelle réception') }}
        </flux:button>
    </div>

    {{-- Modal confirmation suppression --}}
    <flux:modal wire:model="showDeleteModal" name="delete-modal">
        <div class="space-y-4 p-2">
            <flux:heading size="lg">Supprimer la réception ?</flux:heading>
            <flux:text>
                Cette action est irréversible. Toutes les lignes de réception liées à ce bon de commande
                seront supprimées, le stock sera réinitialisé et la commande repassera à l'état
                <strong>Clôturée</strong>.
            </flux:text>
            <div class="flex justify-end gap-3 pt-2">
                <flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">
                    Annuler
                </flux:button>
                <flux:button wire:click="supprimer" variant="danger" icon="trash">
                    Oui, supprimer
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:card class="p-5 mt-5">

        {{-- Barre d'outils --}}
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <flux:input
                wire:model.live.debounce.400ms="search"
                placeholder="{{ __('Fournisseur, libellé, n° compte, code fournisseur...') }}"
                icon="magnifying-glass"
                class="w-full sm:w-80"
            />

            <flux:select wire:model.live="perPage" class="w-full sm:w-20">
                <flux:select.option value="10">10</flux:select.option>
                <flux:select.option value="15">15</flux:select.option>
                <flux:select.option value="25">25</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
            </flux:select>
        </div>

        {{-- Table --}}
        <flux:table :paginate="$this->bonCommandes" variant="bordered">
            <flux:table.columns>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'created_at'"
                    :direction="$sortDirection"
                    wire:click="sort('created_at')"
                >{{ __('Date') }}</flux:table.column>

                <flux:table.column>{{ __('Bon de commande') }}</flux:table.column>

                <flux:table.column>{{ __('Commande') }}</flux:table.column>

                <flux:table.column>{{ __('Fournisseur') }}</flux:table.column>

                <flux:table.column class="hidden sm:table-cell">
                    {{ __('Magasin livraison') }}
                </flux:table.column>

                <flux:table.column class="hidden md:table-cell text-center">
                    {{ __('Lignes reçues') }}
                </flux:table.column>

                <flux:table.column class="hidden md:table-cell text-center">
                    {{ __('Invendable') }}
                </flux:table.column>

                <flux:table.column class="text-right">
                    {{ __('Actions') }}
                </flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->bonCommandes as $bon)
                    @php
                        $commande = $bon->commande;
                        $magasin  = $commande?->magasinLivraison ?? $bon->magasinLivraison;
                    @endphp

                    <flux:table.row :key="$bon->id" wire:key="bon-{{ $bon->id }}">

                        {{-- Date --}}
                        <flux:table.cell class="text-sm whitespace-nowrap">
                            <div class="font-medium">{{ $bon->created_at->format('d/m/Y') }}</div>
                            <div class="text-zinc-400 text-xs">{{ $bon->created_at->format('H:i') }}</div>
                        </flux:table.cell>

                        {{-- Bon de commande --}}
                        <flux:table.cell class="text-sm">
                            <p class="font-semibold">
                                {{ $bon->numero_compte ? '№ '.$bon->numero_compte : '#'.$bon->id }}
                            </p>
                            @if($bon->code_fournisseur)
                                <p class="text-xs text-zinc-400 mt-0.5">{{ $bon->code_fournisseur }}</p>
                            @endif
                        </flux:table.cell>

                        {{-- Commande --}}
                        <flux:table.cell class="text-sm">
                            @if($commande)
                                <p class="font-medium">#{{ $commande->id }}</p>
                                @if($commande->libelle)
                                    <p class="text-xs text-zinc-400 mt-0.5 truncate max-w-[160px]"
                                       title="{{ $commande->libelle }}">
                                        {{ Str::limit($commande->libelle, 30) }}
                                    </p>
                                @endif
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Fournisseur --}}
                        <flux:table.cell class="text-sm">
                            {{ $commande?->fournisseur?->name ?? '—' }}
                        </flux:table.cell>

                        {{-- Magasin --}}
                        <flux:table.cell class="hidden sm:table-cell text-sm">
                            {{ $magasin?->name ?? '—' }}
                        </flux:table.cell>

                        {{-- Lignes reçues --}}
                        <flux:table.cell class="hidden md:table-cell text-center">
                            <flux:badge color="zinc" size="sm" inset="top bottom">
                                {{ $bon->receptions_count }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Invendable --}}
                        <flux:table.cell class="hidden md:table-cell text-center">
                            @if($bon->receptions_sum_invendable > 0)
                                <flux:badge color="red" size="sm" inset="top bottom">
                                    {{ $bon->receptions_sum_invendable }}
                                </flux:badge>
                            @else
                                <span class="text-zinc-400 text-sm">0</span>
                            @endif
                        </flux:table.cell>

                        {{-- Actions --}}
                        <flux:table.cell class="text-right">
                            <div class="flex items-center justify-end gap-1">

                                {{-- Bouton Détails --}}
                                <flux:button
                                    wire:click.stop="openDetail({{ $bon->id }})"
                                    title="{{ __('Détails') }}"
                                >
                                    Details
                                </flux:button>

                                {{-- Bouton Modifier --}}
                                @if($commande)
                                    <flux:button
                                        href="{{ route('reception_commande.edit', ['reception' => $bon->receptions->first()?->id]) }}"
                                        wire:navigate
                                        variant="primary"
                                        title="{{ __('Modifier') }}"
                                    >
                                        Modifier
                                    </flux:button>
                                @else
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="pencil-square"
                                        disabled
                                        title="{{ __('Commande introuvable') }}"
                                    />
                                @endif

                                <flux:button
                                    href="{{ route('reception_commande.pdf', $bon->id) }}"
                                    target="_blank"
                                    variant="filled"
                                    title="{{ __('Télécharger PDF') }}"
                                >
                                    PDF
                                </flux:button>

                                {{-- Bouton Supprimer --}}
                                <flux:button
                                    wire:click.stop="confirmDelete({{ $bon->id }})"
                                    variant="danger"
                                    icon="trash"
                                    title="{{ __('Supprimer') }}"
                                />
                            </div>
                        </flux:table.cell>

                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <flux:icon name="inbox" class="text-zinc-400 mb-3" style="width:40px;height:40px;"/>
                                <p class="text-zinc-400 font-medium text-sm">
                                    @if($search)
                                        {{ __('Aucune réception trouvée pour cette recherche') }}
                                    @else
                                        {{ __('Aucune réception enregistrée') }}
                                    @endif
                                </p>
                                @if($search)
                                    <flux:button variant="ghost" size="sm" wire:click="resetFilters" class="mt-3">
                                        {{ __('Effacer la recherche') }}
                                    </flux:button>
                                @else
                                    <flux:button
                                        href="{{ route('reception_commande.create') }}"
                                        wire:navigate
                                        variant="primary"
                                        size="sm"
                                        icon="plus"
                                        class="mt-3"
                                    >
                                        {{ __('Créer la première réception') }}
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

    </flux:card>

    {{-- Modal détail réception --}}
    <livewire:pages::aprovisionement.reception.detail-reception />
</div>
