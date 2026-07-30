<?php
use Livewire\Component;
use App\Models\Marque;

new class extends Component
{
    public int    $code  = 0;
    public string $name  = '';
    public int    $state = 1;

    public function save(): void
    {
        $this->validate([
            'code'  => 'required|integer|unique:marque,code',
            'name'  => 'required|string|max:255',
            'state' => 'required|boolean',
        ]);

        Marque::create([
            'code'  => $this->code,
            'name'  => $this->name,
            'state' => $this->state,
        ]);

        \Flux\Flux::toast(
            heading: 'Création marque',
            text: "Marque créée avec succès",
            variant: 'success'
        );

        $this->redirectRoute('catalogue.marques', navigate: true);
    }

    public function cancel(): void
    {
        $this->redirectRoute('catalogue.marques', navigate: true);
    }
};
?>

<div class="max-w-4xl mx-auto">
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('catalogue.marques') }}" wire:navigate>Marque</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Ajouter</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1" class="text-[#1a1a1a] dark:text-[#f5f0e6]">
            Ajouter une marque
        </flux:heading>

        <flux:button href="{{ route('catalogue.marques') }}" wire:navigate variant="ghost" icon="arrow-left">
            Retour
        </flux:button>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-12">

            <!-- Section : Informations générales -->
            <div>
                <flux:heading size="lg">Informations générales</flux:heading>
                <flux:text class="mt-1 max-w-2xl">
                    Ces informations identifient la marque dans le système et seront visibles dans le catalogue produits.
                </flux:text>

                <div class="mt-10 space-y-8 border-b border-gray-900/10 dark:border-white/10 pb-12 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 dark:sm:divide-white/10 sm:border-t sm:pb-0">

                    <!-- Code -->
                    <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                        <label class="block text-sm/6 font-medium text-gray-900 dark:text-white sm:pt-1.5">
                            Code
                        </label>
                        <div class="mt-2 sm:col-span-2 sm:mt-0">
                            <flux:input
                                wire:model="code"
                                name="code"
                                type="number"
                                placeholder="Ex: 1001"
                                min="0"
                                description="Code numérique unique de la marque."
                                class="sm:max-w-xs"
                                :invalid="$errors->has('code')"
                            />
                            @error('code')
                            <flux:text size="sm" class="mt-1.5 text-red-600 dark:text-red-400">
                                {{ $message }}
                            </flux:text>
                            @enderror
                        </div>
                    </div>

                    <!-- Nom -->
                    <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                        <label class="block text-sm/6 font-medium text-gray-900 dark:text-white sm:pt-1.5">
                            Nom
                        </label>
                        <div class="mt-2 sm:col-span-2 sm:mt-0">
                            <flux:input
                                wire:model="name"
                                name="name"
                                placeholder="Ex: Nike"
                                class="sm:max-w-md"
                                :invalid="$errors->has('name')"
                            />
                            @error('name')
                            <flux:text size="sm" class="mt-1.5 text-red-600 dark:text-red-400">
                                {{ $message }}
                            </flux:text>
                            @enderror
                        </div>
                    </div>

                    <!-- État -->
                    <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                        <label class="block text-sm/6 font-medium text-gray-900 dark:text-white sm:pt-1.5">
                            État
                        </label>
                        <div class="mt-2 sm:col-span-2 sm:mt-0">
                            <flux:radio.group
                                wire:model="state"
                                name="state"
                                variant="segmented"
                                size="sm"
                            >
                                <flux:radio label="Actif" value="1" />
                                <flux:radio label="Inactif" value="0" />
                            </flux:radio.group>
                            @error('state')
                            <flux:text size="sm" class="mt-1.5 text-red-600 dark:text-red-400">
                                {{ $message }}
                            </flux:text>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-x-4">
                <flux:button
                    type="button"
                    variant="ghost"
                    wire:click="cancel"
                >
                    Annuler
                </flux:button>
                <flux:button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="bg-[#1a1a1a] hover:bg-[#000] text-[#f5f0e6]"
                >
                    <span wire:loading.remove wire:target="save">Créer</span>
                    <span wire:loading wire:target="save">Création...</span>
                </flux:button>
            </div>

        </form>
    </flux:card>
</div>
