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
    <flux:modal name="view-fournisseur" class="md:w-2xl">
        @if ($fournisseur)
            <div class="space-y-5">

                <!-- Header -->
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading size="lg">{{ $fournisseur->name }}</flux:heading>
                        <flux:text class="mt-1">Détails du fournisseur</flux:text>
                    </div>
                    @if ($fournisseur->state == 1)
                        <flux:badge color="green">Actif</flux:badge>
                    @else
                        <flux:badge color="red">Inactif</flux:badge>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                    <!-- Infos générales -->
                    <flux:card class="space-y-3">
                        <div class="flex items-center gap-2">
                            <flux:icon name="building-office" class="text-zinc-400" style="width:16px;height:16px;" />
                            <p class="text-sm font-medium">Infos générales</p>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between items-center gap-2">
                                <span class="text-zinc-400 italic shrink-0">Code</span>
                                <flux:badge size="sm" color="zinc" inset="top bottom">
                                    {{ $fournisseur->code }}
                                </flux:badge>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-zinc-400 italic shrink-0">Raison sociale</span>
                                <span class="text-right truncate">{{ $fournisseur->raison_social ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-zinc-400 italic shrink-0">Date création</span>
                                <span>
                                {{ $fournisseur->date_creation
                                    ? \Carbon\Carbon::parse($fournisseur->date_creation)->format('d/m/Y')
                                    : '—' }}
                            </span>
                            </div>
                        </div>
                    </flux:card>

                    <!-- Contact -->
                    <flux:card class="space-y-3">
                        <div class="flex items-center gap-2">
                            <flux:icon name="phone" class="text-zinc-400" style="width:16px;height:16px;" />
                            <p class="text-sm font-medium">Contact</p>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between gap-2">
                                <span class="text-zinc-400 italic shrink-0">Email</span>
                                <span class="truncate text-right text-xs">{{ $fournisseur->mail ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-zinc-400 italic shrink-0">Téléphone</span>
                                <span>{{ $fournisseur->telephone ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-zinc-400 italic shrink-0">Fax</span>
                                <span>{{ $fournisseur->fax ?? '—' }}</span>
                            </div>
                        </div>
                    </flux:card>

                    <!-- Adresse -->
                    <flux:card class="space-y-3">
                        <div class="flex items-center gap-2">
                            <flux:icon name="map-pin" class="text-zinc-400" style="width:16px;height:16px;" />
                            <p class="text-sm font-medium">Adresse</p>
                        </div>
                        <div class="space-y-1 text-sm">
                            <p class="text-xs text-zinc-400 uppercase tracking-wide italic">Siège</p>
                            <p>{{ $fournisseur->adresse_siege ?? '—' }}</p>
                            <p class="text-zinc-400 text-xs">
                                {{ trim(($fournisseur->code_postal ?? '') . ' ' . ($fournisseur->ville ?? '')) ?: '—' }}
                            </p>

                            @if ($fournisseur->adresse_retour)
                                <p class="text-xs text-zinc-400 uppercase tracking-wide italic mt-2">Retour</p>
                                <p>{{ $fournisseur->adresse_retour }}</p>
                                <p class="text-zinc-400 text-xs">
                                    {{ trim(($fournisseur->code_postal_retour ?? '') . ' ' . ($fournisseur->ville_retour ?? '')) }}
                                </p>
                            @endif
                        </div>
                    </flux:card>

                </div>

                <!-- Actions -->
                <div class="flex gap-2 pt-1">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Fermer</flux:button>
                    </flux:modal.close>
                </div>

            </div>
        @endif
    </flux:modal>
</div>
