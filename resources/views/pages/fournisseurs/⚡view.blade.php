<?php
use Livewire\Component;
use App\Models\Fournisseur;

new class extends Component
{
    public Fournisseur $fournisseur;

    public function mount(Fournisseur $fournisseur): void
    {
        $this->fournisseur = $fournisseur;
    }
};
?>

<div class="w-full mx-auto">

    <!-- Header -->
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ $fournisseur->name }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">
            {{ __('Consultez les informations et les détails du fournisseur.') }}
        </flux:subheading>
    </div>

    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#" separator="slash">{{ __('Home') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('fournisseurs') }}" wire:navigate separator="slash">{{ __('Liste fournisseur') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item separator="slash"><span class="text-pink-500">{{ $fournisseur->name }}</span></flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <!-- Infos fournisseur -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        <!-- Infos générales -->
        <flux:card class="space-y-3">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="building-office" class="text-zinc-400" style="width:18px;height:18px;" />
                <p class="text-sm font-medium">Infos générales</p>
            </div>
            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-400 italic">Code</span>
                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ $fournisseur->code }}</flux:badge>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-400 italic">Raison sociale</span>
                    <span>{{ $fournisseur->raison_social ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-400 italic">Date création</span>
                    <span>{{ $fournisseur->date_creation ? \Carbon\Carbon::parse($fournisseur->date_creation)->format('d/m/Y') : '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-400 italic">État</span>
                    @if ($fournisseur->state == 1)
                        <flux:badge size="sm" color="green" inset="top bottom">Actif</flux:badge>
                    @else
                        <flux:badge size="sm" color="red" inset="top bottom">Inactif</flux:badge>
                    @endif
                </div>
            </div>
        </flux:card>

        <!-- Contact -->
        <flux:card class="space-y-3">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="phone" class="text-zinc-400" style="width:18px;height:18px;" />
                <p class="text-sm font-medium">Contact</p>
            </div>
            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between gap-4">
                    <span class="text-zinc-400 shrink-0 italic">Email</span>
                    <span class="truncate text-right">{{ $fournisseur->mail ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-400 italic">Téléphone</span>
                    <span>{{ $fournisseur->telephone ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-400 italic">Fax</span>
                    <span>{{ $fournisseur->fax ?? '—' }}</span>
                </div>
            </div>
        </flux:card>

        <!-- Adresse -->
        <flux:card class="space-y-3">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="map-pin" class="text-zinc-400" style="width:18px;height:18px;" />
                <p class="text-sm font-medium">Adresse</p>
            </div>
            <div class="space-y-1.5 text-sm">
                <p class="text-zinc-400 text-xs uppercase tracking-wide italic">Siège</p>
                <p>{{ $fournisseur->adresse_siege ?? '—' }}</p>
                <p>{{ $fournisseur->code_postal ?? '' }} {{ $fournisseur->ville ?? '' ?: '—' }}</p>
                @if ($fournisseur->adresse_retour)
                    <p class="text-zinc-400 text-xs uppercase tracking-wide mt-2 italic">Retour</p>
                    <p>{{ $fournisseur->adresse_retour }}</p>
                    <p>{{ $fournisseur->code_postal_retour ?? '' }} {{ $fournisseur->ville_retour ?? '' }}</p>
                @endif
            </div>
        </flux:card>

    </div>

    <!-- Produits fournisseur -->
    <div>
        <div class="mb-4">
            <flux:heading size="lg">Produits associés</flux:heading>
            <flux:subheading>Liste des produits liés à ce fournisseur.</flux:subheading>
        </div>

        <flux:card>
            <livewire:pages::fournisseurs.product-fournisseur :fournisseur-id="$fournisseur->id" />
        </flux:card>
    </div>

</div>
