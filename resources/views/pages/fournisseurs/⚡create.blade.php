<?php
use Livewire\Component;
use App\Models\Fournisseur;

new class extends Component
{
    public int $currentStep = 1;
    public int $totalSteps  = 3;

    // Infos générales
    public string $code          = '';
    public string $name          = '';
    public string $raison_social = '';
    public string $date_creation = '';
    public int    $state         = 1;

    // Contact
    public string $telephone     = '';
    public string $fax           = '';
    public string $mail          = '';

    // Adresse siège
    public string $adresse_siege = '';
    public string $code_postal   = '';
    public string $ville         = '';

    // Adresse retour
    public string $adresse_retour        = '';
    public string $code_postal_retour    = '';
    public string $ville_retour          = '';

    public function nextStep(): void
    {
        $this->validateStep();
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

    public function validateStep(): void
    {
        match ($this->currentStep) {
            1 => $this->validate([
                'code'          => 'required|string|max:50|unique:fournisseur,code',
                'name'          => 'required|string|max:255',
                'raison_social' => 'nullable|string|max:255',
                'date_creation' => 'nullable|date',
                'state'         => 'required|boolean',
            ]),
            2 => $this->validate([
                'telephone' => 'nullable|string|max:20',
                'fax'       => 'nullable|string|max:20',
                'mail'      => 'nullable|email|max:255',
            ]),
            3 => $this->validate([
                'adresse_siege'       => 'nullable|string|max:255',
                'code_postal'         => 'nullable|string|max:10',
                'ville'               => 'nullable|string|max:100',
                'adresse_retour'      => 'nullable|string|max:255',
                'code_postal_retour'  => 'nullable|string|max:10',
                'ville_retour'        => 'nullable|string|max:100',
            ]),
            default => null,
        };
    }

    public function save(): void
    {
        $this->validateStep();

        Fournisseur::create([
            'code'               => $this->code,
            'name'               => $this->name,
            'raison_social'      => $this->raison_social,
            'date_creation'      => $this->date_creation ?: null,
            'state'              => $this->state,
            'telephone'          => $this->telephone,
            'fax'                => $this->fax,
            'mail'               => $this->mail,
            'adresse_siege'      => $this->adresse_siege,
            'code_postal'        => $this->code_postal,
            'ville'              => $this->ville,
            'adresse_retour'     => $this->adresse_retour,
            'code_postal_retour' => $this->code_postal_retour,
            'ville_retour'       => $this->ville_retour,
        ]);

        $this->reset();
        $this->state       = 1;
        $this->currentStep = 1;

        $this->dispatch('fournisseur-created');
        $this->modal('create-fournisseur')->close();

        \Flux\Flux::toast(
            heading: 'Création du fournisseur',
            text: "Fournisseur créé avec succès",
            variant: 'success'
        );
    }

    public function getStepsProperty(): array
    {
        return [
            1 => ['label' => 'Infos générales', 'description' => 'Code, nom, état'],
            2 => ['label' => 'Contact',          'description' => 'Email, téléphone, fax'],
            3 => ['label' => 'Adresse',          'description' => 'Siège et retour'],
        ];
    }
};
?>

<div>
    <flux:modal name="create-fournisseur" class="md:w-2xl" :dismissible="false">
        <div class="space-y-5">

            <!-- Header -->
            <div>
                <flux:heading size="lg">Ajouter un fournisseur</flux:heading>
                <flux:text class="mt-1">Remplissez les informations du nouveau fournisseur.</flux:text>
            </div>

            <!-- Steps -->
            <nav aria-label="Progress">
                <ol role="list" class="flex overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    @foreach ($this->steps as $step => $info)
                        <li class="relative flex-1 overflow-hidden">
                            <div class="
                    flex items-center gap-3 px-4 py-3
                    @if ($step <= $currentStep)
                        bg-white dark:bg-zinc-800
                    @else
                        bg-zinc-50 dark:bg-zinc-900
                    @endif
                    @if (!$loop->last) border-r border-zinc-200 dark:border-zinc-700 @endif
                ">
                                <!-- Icône / Numéro -->
                                <div class="shrink-0">
                                    @if ($step < $currentStep)
                                        <span class="flex size-8 items-center justify-center rounded-full bg-blue-600">
                                <flux:icon name="check" class="text-white" style="width:16px;height:16px;" />
                            </span>
                                    @elseif ($step === $currentStep)
                                        <span class="flex size-8 items-center justify-center rounded-full border-2 border-blue-500">
                                <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                    {{ str_pad($step, 2, '0', STR_PAD_LEFT) }}
                                </span>
                            </span>
                                    @else
                                        <span class="flex size-8 items-center justify-center rounded-full border-2 border-zinc-300 dark:border-zinc-600">
                                <span class="text-sm font-medium text-zinc-400 dark:text-zinc-500">
                                    {{ str_pad($step, 2, '0', STR_PAD_LEFT) }}
                                </span>
                            </span>
                                    @endif
                                </div>

                                <!-- Label -->
                                <div class="min-w-0 hidden sm:block">
                                    <p class="text-xs font-medium
                            @if ($step < $currentStep)
                                text-zinc-500 dark:text-zinc-400
                            @elseif ($step === $currentStep)
                                text-zinc-900 dark:text-zinc-100
                            @else
                                text-zinc-400 dark:text-zinc-500
                            @endif
                        ">
                                        {{ $info['label'] }}
                                    </p>
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500 truncate">
                                        {{ $info['description'] }}
                                    </p>
                                </div>
                            </div>

                            <!-- Barre de progression bottom -->
                            <div class="absolute bottom-0 left-0 h-0.5 w-full
                    @if ($step <= $currentStep)
                        bg-blue-500
                    @else
                        bg-zinc-200 dark:bg-zinc-700
                    @endif
                "></div>
                        </li>
                    @endforeach
                </ol>
            </nav>
            <!-- Contenu step -->
            <div class="min-h-52">

                <!-- Step 1 : Infos générales -->
                @if ($currentStep === 1)
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 gap-4">
                            <flux:input
                                wire:model="code"
                                label="Code"
                                placeholder="Ex: F001"
                                required
                            />
                        </div>

                        <flux:input
                            wire:model="name"
                            label="Nom"
                            placeholder="Ex: Fournisseur SARL"
                            required
                        />

                        <flux:input
                            wire:model="raison_social"
                            label="Raison sociale"
                            placeholder="Ex: Fournisseur SARL"
                        />

                        <flux:radio.group
                            wire:model="state"
                            label="État"
                            variant="segmented"
                            size="sm"
                        >
                            <flux:radio label="Actif" value="1" />
                            <flux:radio label="Inactif" value="0" />
                        </flux:radio.group>
                    </div>
                @endif

                <!-- Step 2 : Contact -->
                @if ($currentStep === 2)
                    <div class="space-y-4">
                        <flux:input
                            wire:model="mail"
                            label="Email"
                            type="email"
                            placeholder="Ex: contact@fournisseur.com"
                            icon="envelope"
                        />
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input
                                wire:model="telephone"
                                label="Téléphone"
                                placeholder="Ex: +33 1 23 45 67 89"
                                icon="phone"
                            />
                            <flux:input
                                wire:model="fax"
                                label="Fax"
                                placeholder="Ex: +33 1 23 45 67 90"
                                icon="printer"
                            />
                        </div>
                    </div>
                @endif

                <!-- Step 3 : Adresse -->
                @if ($currentStep === 3)
                    <div class="space-y-5">
                        <div>
                            <p class="text-sm font-medium text-zinc-300 mb-3">Adresse siège</p>
                            <div class="space-y-3">
                                <flux:input wire:model="adresse_siege" label="Adresse" placeholder="Ex: 12 rue de la Paix" />
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:input wire:model="code_postal" label="Code postal" placeholder="Ex: 75001" />
                                    <flux:input wire:model="ville" label="Ville" placeholder="Ex: Paris" />
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-zinc-700 pt-4">
                            <p class="text-sm font-medium text-zinc-300 mb-3">Adresse retour</p>
                            <div class="space-y-3">
                                <flux:input wire:model="adresse_retour" label="Adresse" placeholder="Ex: 12 rue de la Paix" />
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:input wire:model="code_postal_retour" label="Code postal" placeholder="Ex: 75001" />
                                    <flux:input wire:model="ville_retour" label="Ville" placeholder="Ex: Paris" />
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 pt-1">
                <flux:button
                    variant="ghost"
                    x-on:click="$flux.modal('create-fournisseur').close()"
                >
                    Annuler
                </flux:button>

                <flux:spacer />

                @if ($currentStep > 1)
                    <flux:button variant="ghost" icon="arrow-left" wire:click="prevStep">
                        Précédent
                    </flux:button>
                @endif

                @if ($currentStep < $totalSteps)
                    <flux:button variant="primary" icon-trailing="arrow-right" wire:click="nextStep">
                        Suivant
                    </flux:button>
                @else
                    <flux:button
                        variant="primary"
                        wire:click="save"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="save">Créer</span>
                        <span wire:loading wire:target="save">Création...</span>
                    </flux:button>
                @endif
            </div>

        </div>
    </flux:modal>
</div>
