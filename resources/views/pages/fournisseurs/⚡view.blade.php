<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Fournisseur;

new class extends Component
{
    public ?Fournisseur $fournisseur = null;

    #[On('view-fournisseur')]
    public function loadFournisseur(int $id): void
    {
        $this->fournisseur = Fournisseur::findOrFail($id);
        $this->modal('view-fournisseur')->show();
    }
};
?>

<div>
    <flux:modal name="view-fournisseur" flyout variant="floating" class="md:w-lg">
        @if ($fournisseur)
            <div class="space-y-6">

                <!-- Header -->
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <flux:heading size="lg">{{ $fournisseur->name }}</flux:heading>
                        @if ($fournisseur->state == 1)
                            <flux:badge color="green">Actif</flux:badge>
                        @else
                            <flux:badge color="red">Inactif</flux:badge>
                        @endif
                    </div>
                    <flux:subheading>Détails du fournisseur</flux:subheading>
                </div>
                <!-- Infos générales -->
                <flux:card class="space-y-4">
                    <div class="flex items-center gap-2">
                        <flux:icon name="building-office" class="text-zinc-400" style="width:16px;height:16px;" />
                        <p class="text-sm font-medium">Infos générales</p>
                    </div>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div>
                            <p class="text-xs text-zinc-400 italic mb-0.5">Code</p>
                            <flux:badge size="sm" color="zinc" inset="top bottom">{{ $fournisseur->code }}</flux:badge>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400 italic mb-0.5">Raison sociale</p>
                            <p>{{ $fournisseur->raison_social ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400 italic mb-0.5">Date création</p>
                            <p>{{ $fournisseur->date_creation
                            ? \Carbon\Carbon::parse($fournisseur->date_creation)->format('d/m/Y')
                            : '—' }}
                            </p>
                        </div>
                    </div>
                </flux:card>

                <!-- Contact -->
                <flux:card class="space-y-4">
                    <div class="flex items-center gap-2">
                        <flux:icon name="phone" class="text-zinc-400" style="width:16px;height:16px;" />
                        <p class="text-sm font-medium">Contact</p>
                    </div>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div>
                            <p class="text-xs text-zinc-400 italic mb-0.5">Email</p>
                            <p class="truncate">{{ $fournisseur->mail ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400 italic mb-0.5">Téléphone</p>
                            <p>{{ $fournisseur->telephone ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400 italic mb-0.5">Fax</p>
                            <p>{{ $fournisseur->fax ?? '—' }}</p>
                        </div>
                    </div>
                </flux:card>

                <!-- Adresses -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <!-- Siège -->
                    <flux:card class="space-y-3">
                        <div class="flex items-center gap-2">
                            <flux:icon name="map-pin" class="text-zinc-400" style="width:16px;height:16px;" />
                            <p class="text-sm font-medium">Adresse siège</p>
                        </div>
                        <div class="text-sm space-y-1">
                            <p>{{ $fournisseur->adresse_siege ?? '—' }}</p>
                            @if ($fournisseur->code_postal || $fournisseur->ville)
                                <p class="text-zinc-400">
                                    {{ trim(($fournisseur->code_postal ?? '') . ' ' . ($fournisseur->ville ?? '')) }}
                                </p>
                            @endif
                        </div>
                    </flux:card>

                    <!-- Retour -->
                    <flux:card class="space-y-3">
                        <div class="flex items-center gap-2">
                            <flux:icon name="arrow-uturn-left" class="text-zinc-400" style="width:16px;height:16px;" />
                            <p class="text-sm font-medium">Adresse retour</p>
                        </div>
                        <div class="text-sm space-y-1">
                            @if ($fournisseur->adresse_retour)
                                <p>{{ $fournisseur->adresse_retour }}</p>
                                @if ($fournisseur->code_postal_retour || $fournisseur->ville_retour)
                                    <p class="text-zinc-400">
                                        {{ trim(($fournisseur->code_postal_retour ?? '') . ' ' . ($fournisseur->ville_retour ?? '')) }}
                                    </p>
                                @endif
                            @else
                                <p class="text-zinc-400">—</p>
                            @endif
                        </div>
                    </flux:card>

                </div>

            </div>

            <x-slot name="footer" class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Fermer</flux:button>
                </flux:modal.close>
            </x-slot>

        @endif
    </flux:modal>
</div>
