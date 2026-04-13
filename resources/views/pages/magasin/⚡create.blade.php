<?php
use Livewire\Component;
use App\Models\Magasin;

new class extends Component
{
    public string $name      = '';
    public string $type      = 'online';
    public string $store_url = '';
    public string $adress    = '';
    public string $telephone = '';
    public string $email     = '';
    public int    $state     = 1;

    public function save(): void
    {
        $this->validate([
            'name'      => 'required|string|max:255',
            'type'      => 'required|in:online,physic',
            'store_url' => 'nullable|url|max:255',
            'adress'    => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:255',
            'state'     => 'required|boolean',
        ]);

        Magasin::create([
            'name'      => $this->name,
            'type'      => $this->type,
            'store_url' => $this->store_url ?: null,
            'adress'    => $this->adress ?: null,
            'telephone' => $this->telephone ?: null,
            'email'     => $this->email ?: null,
            'state'     => $this->state,
        ]);

        $this->reset(['name', 'store_url', 'adress', 'telephone', 'email']);
        $this->type  = 'online';
        $this->state = 1;

        $this->dispatch('magasin-created');
        $this->modal('create-magasin')->close();

        \Flux\Flux::toast(
            heading: 'Magasin créé',
            text: 'Magasin créé avec succès',
            variant: 'success'
        );
    }
};
?>

<div>
    <flux:modal name="create-magasin" class="md:w-lg" :dismissible="false">
        <div class="space-y-5">

            <!-- Header -->
            <div>
                <flux:heading size="lg">Ajouter un magasin</flux:heading>
                <flux:text class="mt-1">Remplissez les informations du nouveau magasin.</flux:text>
            </div>

            <!-- Nom -->
            <flux:input
                wire:model="name"
                label="Nom"
                placeholder="Ex: Magasin Paris"
                required
            />

            <!-- Type -->
            <flux:radio.group
                wire:model="type"
                label="Type"
                variant="cards"
                class="max-sm:flex-col"
            >
                <flux:radio
                    value="online"
                    label="En ligne"
                    description="Boutique e-commerce"
                    icon="globe-alt"
                />
                <flux:radio
                    value="physic"
                    label="Physique"
                    description="Magasin en point de vente"
                    icon="building-storefront"
                />
            </flux:radio.group>

            <!-- URL (visible si online) -->
            @if ($type === 'online')
                <flux:input
                    wire:model="store_url"
                    label="URL du magasin"
                    type="url"
                    placeholder="Ex: https://magasin.com"
                    icon="globe-alt"
                />
            @endif

            <!-- Adresse (visible si physic) -->
            @if ($type === 'physic')
                <flux:input
                    wire:model="adress"
                    label="Adresse"
                    placeholder="Ex: 12 rue de la Paix, Paris"
                    icon="map-pin"
                />
            @endif

            <!-- Email + Téléphone -->
            <div class="grid grid-cols-2 gap-4">
                <flux:input
                    wire:model="email"
                    label="Email"
                    type="email"
                    placeholder="Ex: contact@magasin.com"
                    icon="envelope"
                />
                <flux:input
                    wire:model="telephone"
                    label="Téléphone"
                    placeholder="Ex: +33 1 23 45 67 89"
                    icon="phone"
                />
            </div>

            <!-- État -->
            <flux:radio.group
                wire:model="state"
                label="État"
                variant="segmented"
                size="sm"
            >
                <flux:radio label="Actif" value="1" />
                <flux:radio label="Inactif" value="0" />
            </flux:radio.group>

            <!-- Actions -->
            <div class="flex gap-2 pt-1">
                <flux:spacer />
                <flux:button
                    variant="ghost"
                    x-on:click="$flux.modal('create-magasin').close()"
                >
                    Annuler
                </flux:button>
                <flux:button
                    variant="primary"
                    wire:click="save"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="save">Créer</span>
                    <span wire:loading wire:target="save">Création...</span>
                </flux:button>
            </div>

        </div>
    </flux:modal>
</div>
