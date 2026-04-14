<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\Magasin;
use Flux\Flux;

new class extends Component
{
    #[Url(as: 'step', keep: true)]
    public int $currentStep = 1;

    public int $commande_id;

    public string  $libelle              = '';
    public ?int    $fournisseur_id       = null;
    public ?int    $magasin_livraison_id = null;
    public ?float  $remise_facture       = null;
    public ?float  $montant_minimum      = null;
    public ?int    $nombre_jour          = null;

    public bool $showCancelModal = false;

    public function mount(int $commande_id): void
    {
        $commande = Commande::findOrFail($commande_id);

        $this->commande_id           = $commande->id;
        $this->libelle               = $commande->libelle;
        $this->fournisseur_id        = $commande->fournisseur_id;
        $this->magasin_livraison_id  = $commande->magasin_livraison_id;
        $this->remise_facture        = $commande->remise_facture;
        $this->montant_minimum       = $commande->montant_minimum;
        $this->nombre_jour           = $commande->nombre_jour;
    }

    #[Computed]
    public function commande()
    {
        return Commande::findOrFail($this->commande_id);
    }

    #[Computed]
    public function fournisseurs()
    {
        return Fournisseur::where('state', 1)->orderBy('name')->get();
    }

    #[Computed]
    public function magasins()
    {
        return Magasin::where('state', 1)->orderBy('name')->get();
    }

    protected function rules(): array
    {
        return [
            'libelle'              => 'required|string|max:255',
            'fournisseur_id'       => 'required|integer|exists:fournisseur,id',
            'magasin_livraison_id' => 'required|integer|exists:magasin,id',
            'remise_facture'       => 'nullable|numeric|min:0|max:100',
            'montant_minimum'      => 'nullable|numeric|min:0',
            'nombre_jour'          => 'nullable|integer|min:0',
        ];
    }

    public function nextStep(): void
    {
        if ($this->currentStep === 1) {
            $this->validate();

            Commande::findOrFail($this->commande_id)->update([
                'libelle'              => $this->libelle,
                'fournisseur_id'       => $this->fournisseur_id,
                'magasin_livraison_id' => $this->magasin_livraison_id,
                'remise_facture'       => $this->remise_facture ?? 0,
                'montant_minimum'      => $this->montant_minimum ?? 0,
                'nombre_jour'          => $this->nombre_jour ?? 0,
            ]);

            unset($this->commande);
        }

        if ($this->currentStep < 3) {
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
        $this->redirect(route('orders.list'), navigate: true);
    }

    public function confirmer(): void
    {
        $commande = Commande::find($this->commande_id);

        if (!$commande) {
            Flux::toast(heading: 'Erreur', text: 'Commande introuvable.', variant: 'danger');
            return;
        }

        $details = \App\Models\DetailCommande::where('commande_id', $this->commande_id)->get();

        if ($details->isEmpty()) {
            Flux::toast(
                heading: 'Commande vide',
                text: 'Veuillez ajouter au moins un produit avant de confirmer.',
                variant: 'warning'
            );
            return;
        }

        $totalNet = $details->sum(fn($d) => $d->pu_achat_net * $d->quantite);
        $totalTax = $details->sum(fn($d) => ($d->pu_achat_net * $d->tax / 100) * $d->quantite);
        $totalTTC = $totalNet + $totalTax;

        if ($commande->remise_facture > 0) {
            $totalTTC -= $totalNet * $commande->remise_facture / 100;
        }

        $commande->update([
            'etat'          => 'commande',
            'state'         => 1,
            'montant_total' => round($totalTTC, 2),
        ]);

        \App\Models\BonCommande::create([
            'commande_id'            => $commande->id,
            'code_fournisseur'       => $commande->fournisseur?->code ?? null,
            'numero_compte'          => $commande->fournisseur?->numero_compte ?? null,
            'date_commande'          => now(),
            'date_livraison_prevue'  => $commande->nombre_jour > 0
                ? now()->addDays($commande->nombre_jour)
                : null,
            'magasin_facturation_id' => $commande->magasin_livraison_id,
            'magasin_livraison_id'   => $commande->magasin_livraison_id,
            'montant_commande_net'   => round($totalNet, 2),
            'state'                  => 1,
        ]);

        Flux::toast(
            heading: 'Commande confirmée',
            text: 'La commande et le bon de commande ont été enregistrés avec succès.',
            variant: 'success'
        );

        $this->redirect(route('orders.list'), navigate: true);
    }
};
?>

<div class="max-w-8xl mx-auto">
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('orders.list') }}">Commande</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Commande №/ {{ $commande_id }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Modifier la commande') }}</flux:heading>
            <flux:text class="mt-1 text-gray-500">{{ $this->commande->libelle }}</flux:text>
        </div>
        <div class="flex items-center gap-3">
            <flux:badge color="{{ match($this->commande->etat ?? '') {
                'en_cours'     => 'amber',
                'commande'     => 'green',
                'pre_commande' => 'blue',
                'annulee'      => 'red',
                default        => 'zinc',
            } }}">
                {{ ucfirst(str_replace('_', ' ', $this->commande->etat ?? 'draft')) }}
            </flux:badge>
            <flux:button wire:click="confirmAnnuler" variant="danger" icon="x-circle">
                Annuler
            </flux:button>
        </div>
    </div>

    {{-- Modal confirmation annulation --}}
    <flux:modal wire:model="showCancelModal" name="cancel-modal">
        <div class="space-y-4 p-2">
            <flux:heading size="lg">Annuler les modifications ?</flux:heading>
            <flux:text>
                Êtes-vous sûr de vouloir quitter ? Les modifications non enregistrées seront perdues.
            </flux:text>
            <div class="flex justify-end gap-3 pt-2">
                <flux:button wire:click="$set('showCancelModal', false)" variant="ghost">
                    Continuer l'édition
                </flux:button>
                <flux:button wire:click="annuler" variant="danger">
                    Oui, quitter
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
                    2 => 'Produits',
                    3 => 'Aperçu',
                ];
            @endphp

            @foreach($steps as $step => $label)
                @php
                    $done = $currentStep > $step;
                    $active = $currentStep === $step;
                    $last = $step === count($steps);
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

        {{-- STEP 1 --}}
        @if($currentStep === 1)
            <div class="space-y-6">
                <flux:heading size="lg">Informations de la commande</flux:heading>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="libelle">Libellé</flux:label>
                        <flux:input wire:model="libelle" id="libelle" placeholder="Ex: Commande Avril 2025"/>
                        <flux:error name="libelle"/>
                    </flux:field>

                    <flux:field>
                        <flux:label for="fournisseur_id">Fournisseur</flux:label>
                        <flux:select wire:model="fournisseur_id" id="fournisseur_id">
                            <flux:select.option value="">-- Choisir un fournisseur --</flux:select.option>
                            @foreach($this->fournisseurs as $f)
                                <flux:select.option value="{{ $f->id }}">{{ $f->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="fournisseur_id"/>
                    </flux:field>

                    <flux:field>
                        <flux:label for="magasin_livraison_id">Magasin de livraison</flux:label>
                        <flux:select wire:model="magasin_livraison_id" id="magasin_livraison_id">
                            <flux:select.option value="">-- Choisir un magasin --</flux:select.option>
                            @foreach($this->magasins as $m)
                                <flux:select.option value="{{ $m->id }}">{{ $m->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="magasin_livraison_id"/>
                    </flux:field>

                    <flux:field>
                        <flux:label for="remise_facture">Remise facture (%)</flux:label>
                        <flux:input wire:model="remise_facture" id="remise_facture" type="number" step="0.01" min="0" max="100" placeholder="0.00"/>
                        <flux:error name="remise_facture"/>
                    </flux:field>

                    <flux:field>
                        <flux:label for="montant_minimum">Montant minimum</flux:label>
                        <flux:input wire:model="montant_minimum" id="montant_minimum" type="number" step="0.01" min="0" placeholder="0.00"/>
                        <flux:error name="montant_minimum"/>
                    </flux:field>

                    <flux:field>
                        <flux:label for="nombre_jour">Délai (jours)</flux:label>
                        <flux:input wire:model="nombre_jour" id="nombre_jour" type="number" min="0" placeholder="0"/>
                        <flux:error name="nombre_jour"/>
                    </flux:field>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                    <flux:button wire:click="nextStep" variant="primary">
                        Enregistrer & Suivant &rarr;
                    </flux:button>
                </div>
            </div>
        @endif

        {{-- STEP 2 --}}
        @if($currentStep === 2)
            @livewire('pages::orders.step2', ['commande_id' => $commande_id], key('edit-step2-'.$commande_id))

            <div class="flex justify-between pt-4 border-t border-gray-200 dark:border-gray-700 mt-6">
                <flux:button wire:click="previousStep" variant="ghost">&larr; Précédent</flux:button>
                <flux:button wire:click="nextStep" variant="primary">Suivant &rarr;</flux:button>
            </div>
        @endif

        {{-- STEP 3 --}}
        @if($currentStep === 3)
            @livewire('pages::orders.preview', ['commande_id' => $commande_id], key('edit-preview-'.$commande_id))

            <div class="flex justify-between pt-4 border-t border-gray-200 dark:border-gray-700 mt-6">
                <flux:button wire:click="previousStep" variant="ghost">&larr; Précédent</flux:button>
                <flux:button wire:click="confirmer" variant="primary">
                    Confirmer les modifications
                </flux:button>
            </div>
        @endif

    </flux:card>
</div>
