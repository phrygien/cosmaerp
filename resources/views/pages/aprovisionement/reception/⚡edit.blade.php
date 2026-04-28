<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\ReceptionCommande;
use App\Models\Commande;
use App\Models\BonCommande;
use App\Models\StockMagasin;
use App\Enums\CommandeStatus;
use Flux\Flux;

new class extends Component
{
    #[Url(as: 'step', keep: true)]
    public int $currentStep = 1;

    public BonCommande $bon;

    // Step 1 fields
    public ?int    $commande_id     = null;
    public ?int    $bon_commande_id = null;
    public ?string $date_reception  = null;
    public ?string $note            = null;

    public bool $showCancelModal = false;

    public function mount(BonCommande $bon): void
    {
        $bon->load([
            'commande.fournisseur',
            'commande.magasinLivraison',
            'receptions',
        ]);

        $this->bon = $bon;

        $commande = $bon->commande;

        if (!$commande) {
            $this->redirect(route('reception_commande.list'), navigate: true);
            return;
        }

        $this->commande_id     = $commande->id;
        $this->bon_commande_id = $bon->id;

        // Date depuis la première réception ou aujourd'hui
        $premiereReception = $bon->receptions->first();
        $this->date_reception = $premiereReception?->created_at
            ? $premiereReception->created_at->format('Y-m-d')
            : now()->format('Y-m-d');

        $this->note = $premiereReception?->note ?? null;
    }

    #[Computed]
    public function selectedCommande()
    {
        if (!$this->commande_id) return null;
        return Commande::with(['fournisseur', 'magasinLivraison'])->find($this->commande_id);
    }

    #[Computed]
    public function bonCommande()
    {
        return $this->bon;
    }

    protected function rules(): array
    {
        return [
            'commande_id'    => 'required|integer|exists:commande,id',
            'date_reception' => 'required|date',
            'note'           => 'nullable|string|max:1000',
        ];
    }

    public function nextStep(): void
    {
        if ($this->currentStep === 1) {
            $this->validate();
        }

        if ($this->currentStep < 2) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function confirmAnnuler(): void
    {
        $this->showCancelModal = true;
    }

    public function annuler(): void
    {
        $this->redirect(route('reception_commande.list'), navigate: true);
    }

    public function confirmer(): void
    {
        $this->validate();

        if (!$this->commande_id) {
            Flux::toast(heading: 'Erreur', text: 'Aucune commande sélectionnée.', variant: 'danger');
            return;
        }

        $details = \App\Models\DetailCommande::where('commande_id', $this->commande_id)->get();

        if ($details->isEmpty()) {
            Flux::toast(heading: 'Erreur', text: 'Aucun produit dans cette commande.', variant: 'danger');
            return;
        }

        $receptions = ReceptionCommande::where('bon_commande_id', $this->bon_commande_id)->get();

        if ($receptions->isEmpty()) {
            Flux::toast(
                heading: 'Réception vide',
                text: 'Veuillez renseigner la réception d\'au moins un produit.',
                variant: 'warning'
            );
            return;
        }

        try {
            DB::transaction(function () use ($details) {
                // Réinitialisation du stock lié à cette commande
                StockMagasin::whereIn('detail_commande_id', function ($query) {
                    $query->select('id')
                        ->from('detail_commande')
                        ->where('commande_id', $this->commande_id);
                })->delete();

                // Recalcul du stock magasin via les destinations
                $details->load('destinations');

                foreach ($details as $detail) {
                    foreach ($detail->destinations as $destination) {
                        StockMagasin::updateOrCreate(
                            [
                                'magasin_id'         => $destination->magasin_id,
                                'product_id'         => $detail->product_id,
                                'detail_commande_id' => $detail->id,
                            ],
                            [
                                'gen_code'      => StockMagasin::generateGenCode(
                                    $destination->magasin_id,
                                    $detail->product_id
                                ),
                                'nb_item'       => $destination->quantite,
                                'deposite_date' => now(),
                            ]
                        );
                    }
                }

                // Garder la commande à l'état Recue
                Commande::find($this->commande_id)?->update([
                    'status'         => CommandeStatus::Recue,
                    'date_reception' => $this->date_reception,
                ]);
            });

            Flux::toast(
                heading: 'Réception mise à jour',
                text: 'La réception de commande a été modifiée avec succès.',
                variant: 'success'
            );

            $this->redirect(route('reception_commande.list'), navigate: true);

        } catch (\Throwable $e) {
            Flux::toast(
                heading: 'Erreur',
                text: 'Une erreur est survenue lors de la modification : ' . $e->getMessage(),
                variant: 'danger'
            );
        }
    }
};
?>

<div class="max-w-7xl mx-auto">
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('reception_commande.list') }}" wire:navigate>
            Réceptions
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>
            Modifier — {{ $bon->numero_compte ? '№ '.$bon->numero_compte : '#'.$bon->id }}
        </flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Modifier la réception de commande') }}</flux:heading>

        <flux:button wire:click="confirmAnnuler" variant="danger" icon="x-circle">
            Annuler
        </flux:button>
    </div>

    {{-- Modal confirmation annulation --}}
    <flux:modal wire:model="showCancelModal" name="cancel-modal">
        <div class="space-y-4 p-2">
            <flux:heading size="lg">Annuler la modification ?</flux:heading>
            <flux:text>
                Êtes-vous sûr de vouloir annuler ? Les modifications non enregistrées seront perdues.
            </flux:text>
            <div class="flex justify-end gap-3 pt-2">
                <flux:button wire:click="$set('showCancelModal', false)" variant="ghost">
                    Continuer
                </flux:button>
                <flux:button wire:click="annuler" variant="danger">
                    Oui, annuler
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Step Indicator --}}
    <nav aria-label="Progress">
        <ol role="list"
            class="divide-y divide-gray-200 dark:divide-zinc-700
                   rounded-lg border border-gray-200 dark:border-zinc-700
                   md:flex md:divide-y-0">

            @php
                $steps = [
                    1 => 'Informations',
                    2 => 'Produits reçus',
                ];
            @endphp

            @foreach($steps as $step => $label)
                @php
                    $done   = $currentStep > $step;
                    $active = $currentStep === $step;
                    $last   = $step === count($steps);
                @endphp

                <li class="relative md:flex md:flex-1">

                    @if($done)
                        <a href="#"
                           wire:click.prevent="$set('currentStep', {{ $step }})"
                           class="group flex w-full items-center">
                            <span class="flex items-center px-6 py-4 gap-3 text-sm font-medium">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full
                                             bg-rose-600 group-hover:bg-rose-700 transition-colors duration-150">
                                    <svg class="size-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                        <path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 0 1 .208 1.04l-9 13.5a.75.75 0 0 1-1.154.114l-6-6a.75.75 0 0 1 1.06-1.06l5.353 5.353 8.493-12.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-gray-900 dark:text-zinc-100">
                                    {{ $label }}
                                </span>
                            </span>
                        </a>

                    @elseif($active)
                        <span class="flex items-center px-6 py-4 gap-3 text-sm font-medium" aria-current="step">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full
                                         border-2 border-rose-600 dark:border-rose-500">
                                <span class="text-sm font-semibold text-rose-600 dark:text-rose-400">
                                    {{ str_pad($step, 2, '0', STR_PAD_LEFT) }}
                                </span>
                            </span>
                            <span class="text-sm font-medium text-rose-600 dark:text-rose-400">
                                {{ $label }}
                            </span>
                        </span>

                    @else
                        <span class="flex items-center px-6 py-4 gap-3 text-sm font-medium">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full
                                         border-2 border-gray-300 dark:border-zinc-600">
                                <span class="text-sm font-medium text-gray-400 dark:text-zinc-500">
                                    {{ str_pad($step, 2, '0', STR_PAD_LEFT) }}
                                </span>
                            </span>
                            <span class="text-sm font-medium text-gray-400 dark:text-zinc-500">
                                {{ $label }}
                            </span>
                        </span>
                    @endif

                    @unless($last)
                        <div class="absolute top-0 right-0 hidden h-full w-5 md:block" aria-hidden="true">
                            <svg class="size-full text-gray-200 dark:text-zinc-700"
                                 viewBox="0 0 22 80" fill="none" preserveAspectRatio="none">
                                <path d="M0 -2L20 40L0 82"
                                      vector-effect="non-scaling-stroke"
                                      stroke="currentColor"
                                      stroke-linejoin="round"/>
                            </svg>
                        </div>
                    @endunless

                </li>
            @endforeach
        </ol>
    </nav>

    {{-- Step Content --}}
    <flux:card class="mt-5">

        {{-- STEP 1 : Informations --}}
        @if($currentStep === 1)
            <div class="space-y-6">
                <flux:heading size="lg">Informations de la réception</flux:heading>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

                    {{-- Commande liée (lecture seule) --}}
                    <flux:field class="sm:col-span-2">
                        <flux:label>Commande</flux:label>
                        <div class="flex items-center gap-2 px-3 py-2 rounded-lg
                                    bg-zinc-100 dark:bg-zinc-800
                                    border border-zinc-200 dark:border-zinc-700
                                    text-sm text-gray-700 dark:text-zinc-300">
                            <flux:icon.lock-closed class="size-4 text-gray-400 dark:text-zinc-500 shrink-0"/>
                            <span>
                                #{{ $this->selectedCommande?->id }}
                                — {{ $this->selectedCommande?->libelle }}
                                ({{ $this->selectedCommande?->fournisseur?->name ?? '—' }})
                            </span>
                        </div>
                        <flux:description>La commande associée ne peut pas être modifiée.</flux:description>
                    </flux:field>

                    {{-- Aperçu commande --}}
                    @if($this->selectedCommande)
                        <div class="sm:col-span-2 rounded-lg bg-zinc-50 dark:bg-zinc-900
                                    border border-zinc-200 dark:border-zinc-700
                                    p-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Fournisseur</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $this->selectedCommande->fournisseur?->name ?? '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Magasin</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $this->selectedCommande->magasinLivraison?->name ?? '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Montant total</p>
                                <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                                    {{ number_format($this->selectedCommande->montant_total, 2) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Bon de commande</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $bon->numero_compte ? '№ '.$bon->numero_compte : '#'.$bon->id }}
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- Date de réception --}}
                    <flux:field>
                        <flux:label for="date_reception">Date de réception</flux:label>
                        <flux:input
                            wire:model="date_reception"
                            id="date_reception"
                            type="date"
                        />
                        <flux:error name="date_reception"/>
                    </flux:field>

                    {{-- Note --}}
                    <flux:field>
                        <flux:label for="note">Note (optionnel)</flux:label>
                        <flux:input
                            wire:model="note"
                            id="note"
                            placeholder="Observations sur la réception..."
                        />
                        <flux:error name="note"/>
                    </flux:field>

                </div>

                <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                    <flux:button wire:click="nextStep" variant="primary">
                        Suivant &rarr;
                    </flux:button>
                </div>
            </div>
        @endif

        {{-- STEP 2 : Produits reçus --}}
        @if($currentStep === 2 && $commande_id)
            @if($this->selectedCommande)
                <div class="mb-5 rounded-lg bg-zinc-50 dark:bg-zinc-900
                            border border-zinc-200 dark:border-zinc-700
                            p-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Fournisseur</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $this->selectedCommande->fournisseur?->name ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Magasin</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $this->selectedCommande->magasinLivraison?->name ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Montant total</p>
                        <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                            {{ number_format($this->selectedCommande->montant_total, 2) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Bon de commande</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $bon->numero_compte ? '№ '.$bon->numero_compte : '#'.$bon->id }}
                        </p>
                    </div>
                </div>
            @endif

            @livewire('pages::aprovisionement.reception.step2', [
                'commande_id'     => $commande_id,
                'bon_commande_id' => $bon_commande_id,
                'date_reception'  => $date_reception,
                'note'            => $note,
            ], key('reception-step2-'.$commande_id))

            <div class="flex justify-between pt-4 border-t border-gray-200 dark:border-gray-700 mt-6">
                <flux:button wire:click="previousStep" variant="ghost">
                    &larr; Précédent
                </flux:button>
                <flux:button wire:click="confirmer" variant="primary">
                    Enregistrer et terminer la réception
                </flux:button>
            </div>
        @endif

    </flux:card>
</div>
