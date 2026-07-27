<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\Product;
use App\Models\Marque;
use App\Models\Category;
use App\Models\Type;
use App\Models\Ligne;
use Illuminate\Support\Facades\DB;
use Flux\Flux;

new class extends Component
{
    public ?int $productId = null;

    // ── Identification ──────────────────────────────────────────────
    public string $product_code       = '';
    public string $article            = '';
    public string $designation        = '';
    public string $designation_variant = '';
    public string $ref_fabri_n_1      = '';
    public string $EAN                = '';

    // ── Classification ──────────────────────────────────────────────
    public string $marque_code    = '';
    public string $categorie_code = '';
    public string $ligne_code     = '';
    public ?int   $type_id        = null;

    // ── Tarification & douane ───────────────────────────────────────
    public ?float $pght_parkod   = null;
    public ?float $tva           = null;
    public string $devise        = '';
    public string $hs_code       = '';
    public string $statut_parkod = '';

    // ── État ─────────────────────────────────────────────────────────
    public bool $state = true;

    #[On('edit-product')]
    public function open($id): void
    {
        $this->resetValidation();

        $product = Product::findOrFail($id);

        $this->productId           = $product->id;
        $this->product_code        = $product->product_code ?? '';
        $this->article              = $product->article ?? '';
        $this->designation          = $product->designation ?? '';
        $this->designation_variant  = $product->designation_variant ?? '';
        $this->ref_fabri_n_1        = $product->ref_fabri_n_1 ?? '';
        $this->EAN                  = $product->EAN ?? '';

        $this->marque_code    = $product->marque_code ?? '';
        $this->categorie_code = $product->categorie_code ?? '';
        $this->ligne_code     = $product->ligne_code ?? '';
        $this->type_id        = $product->type_id;

        $this->pght_parkod   = $product->pght_parkod !== null ? (float) $product->pght_parkod : null;
        $this->tva           = $product->tva !== null ? (float) $product->tva : null;
        $this->devise        = $product->devise ?? '';
        $this->hs_code       = $product->hs_code ?? '';
        $this->statut_parkod = $product->statut_parkod ?? '';

        $this->state = $product->state == 1;

        Flux::modal('edit-product')->show();
    }

    public function updatedMarqueCode(): void
    {
        unset($this->categoriesList);

        // Si la catégorie sélectionnée n'appartient plus à la nouvelle marque, on la réinitialise
        if ($this->categorie_code !== '') {
            $belongsToMarque = Category::query()
                ->where('code', $this->categorie_code)
                ->where('marque_code', $this->marque_code)
                ->exists();

            if (! $belongsToMarque) {
                $this->categorie_code = '';
            }
        }
    }

    protected function rules(): array
    {
        return [
            'product_code'        => ['nullable', 'string', 'max:100'],
            'article'             => ['nullable', 'string', 'max:100'],
            'designation'         => ['required', 'string', 'max:255'],
            'designation_variant' => ['nullable', 'string', 'max:255'],
            'ref_fabri_n_1'       => ['nullable', 'string', 'max:100'],
            'EAN'                 => ['nullable', 'string', 'max:20'],

            'marque_code'    => ['nullable', 'string', 'exists:marque,code'],
            'categorie_code' => ['nullable', 'string', 'exists:categorie,code'],
            'ligne_code'     => ['nullable', 'string'],
            'type_id'        => ['nullable', 'integer', 'exists:type,id'],

            'pght_parkod'   => ['nullable', 'numeric', 'min:0'],
            'tva'           => ['nullable', 'numeric', 'min:0'],
            'devise'        => ['nullable', 'string', 'max:10'],
            'hs_code'       => ['nullable', 'string', 'max:50'],
            'statut_parkod' => ['nullable', 'string', 'max:50'],

            'state' => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'product_code'        => 'code produit',
            'article'             => 'article',
            'designation'         => 'désignation',
            'designation_variant' => 'désignation variante',
            'ref_fabri_n_1'       => 'référence fabricant',
            'EAN'                 => 'EAN',
            'marque_code'         => 'marque',
            'categorie_code'      => 'catégorie',
            'ligne_code'          => 'ligne',
            'type_id'             => 'type',
            'pght_parkod'         => 'prix PARKOD',
            'tva'                 => 'TVA',
            'devise'              => 'devise',
            'hs_code'             => 'code HS',
            'statut_parkod'       => 'statut PARKOD',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($this->productId);

            $product->update([
                'product_code'        => $validated['product_code'] ?: null,
                'article'              => $validated['article'] ?: null,
                'designation'          => $validated['designation'],
                'designation_variant'  => $validated['designation_variant'] ?: null,
                'ref_fabri_n_1'        => $validated['ref_fabri_n_1'] ?: null,
                'EAN'                  => $validated['EAN'] ?: null,
                'marque_code'          => $validated['marque_code'] ?: null,
                'categorie_code'       => $validated['categorie_code'] ?: null,
                'ligne_code'           => $validated['ligne_code'] ?: null,
                'type_id'              => $validated['type_id'] ?: null,
                'pght_parkod'          => $validated['pght_parkod'],
                'tva'                  => $validated['tva'],
                'devise'               => $validated['devise'] ?: null,
                'hs_code'              => $validated['hs_code'] ?: null,
                'statut_parkod'        => $validated['statut_parkod'] ?: null,
                'state'                => $this->state ? 1 : 0,
            ]);

            DB::commit();

            Flux::modal('edit-product')->close();

            $this->dispatch('product-updated', id: $product->id);

            Flux::toast(
                heading: 'Produit modifié',
                text: "Le produit \"{$product->designation}\" a été mis à jour avec succès",
                variant: 'success'
            );

            $this->reset();
        } catch (\Exception $e) {
            dd($e);
            DB::rollBack();

            Flux::toast(
                heading: 'Erreur',
                text: "Impossible de modifier le produit : " . $e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function close(): void
    {
        Flux::modal('edit-product')->close();
        $this->resetValidation();
    }

    #[Computed]
    public function marquesList()
    {
        return Marque::query()->orderBy('name')->get();
    }

    #[Computed]
    public function categoriesList()
    {
        return Category::query()
            ->when($this->marque_code !== '', fn($q) => $q->where('marque_code', $this->marque_code))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function typesList()
    {
        return Type::query()->orderBy('name')->get();
    }

    #[Computed]
    public function lignesList()
    {
        return Ligne::query()
            ->when($this->marque_code !== '', fn($q) => $q->where('marque_code', $this->marque_code))
            ->orderBy('name')
            ->get();
    }
};
?>

<div>
    <flux:modal name="edit-product" class="max-w-2xl w-full" @close="close">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Modifier le produit</flux:heading>
                <flux:subheading>Mettez à jour les informations du produit ci-dessous</flux:subheading>
            </div>

            <form wire:submit="save" class="space-y-6">

                <!-- Identification -->
                <div class="space-y-4">
                    <flux:heading size="sm">Identification</flux:heading>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:input
                            wire:model="product_code"
                            label="Code produit"
                            placeholder="Ex: PRD-0001"
                        />
                        <flux:input
                            wire:model="article"
                            label="Article"
                            placeholder="Ex: ART-0001"
                        />
                    </div>

                    <flux:input
                        wire:model="designation"
                        label="Désignation"
                        placeholder="Nom du produit"
                        required
                    />

                    <flux:input
                        wire:model="designation_variant"
                        label="Désignation variante"
                        placeholder="Variante (optionnel)"
                    />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:input
                            wire:model="ref_fabri_n_1"
                            label="Référence fabricant"
                            placeholder="Ex: REF-123"
                        />
                        <flux:input
                            wire:model="EAN"
                            label="EAN"
                            placeholder="Code-barres (8 ou 13 chiffres)"
                        />
                    </div>
                </div>

                <!-- Classification -->
                <div class="space-y-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm">Classification</flux:heading>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:select
                            wire:model.live="marque_code"
                            label="Marque"
                            placeholder="Sélectionner une marque"
                        >
                            <flux:select.option value="">— Aucune —</flux:select.option>
                            @foreach ($this->marquesList as $marque)
                                <flux:select.option value="{{ $marque->code }}">
                                    {{ $marque->name }} ({{ $marque->code }})
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select
                            wire:model="categorie_code"
                            label="Catégorie"
                            placeholder="Sélectionner une catégorie"
                        >
                            <flux:select.option value="">— Aucune —</flux:select.option>
                            @foreach ($this->categoriesList as $categorie)
                                <flux:select.option value="{{ $categorie->code }}">
                                    {{ $categorie->name }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:select
                            wire:model="ligne_code"
                            label="Ligne"
                            placeholder="Sélectionner une ligne"
                        >
                            <flux:select.option value="">— Aucune —</flux:select.option>
                            @foreach ($this->lignesList as $ligne)
                                <flux:select.option value="{{ $ligne->code }}">
                                    {{ $ligne->name }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select
                            wire:model="type_id"
                            label="Type"
                            placeholder="Sélectionner un type"
                        >
                            <flux:select.option value="">— Aucun —</flux:select.option>
                            @foreach ($this->typesList as $type)
                                <flux:select.option value="{{ $type->id }}">
                                    {{ $type->name }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <!-- Tarification & douane -->
                <div class="space-y-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm">Tarification &amp; douane</flux:heading>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <flux:input
                            wire:model="pght_parkod"
                            type="number"
                            step="0.01"
                            min="0"
                            label="Prix PARKOD"
                            placeholder="0.00"
                        />
                        <flux:input
                            wire:model="tva"
                            type="number"
                            step="0.01"
                            min="0"
                            label="TVA (%)"
                            placeholder="0.00"
                        />
                        <flux:input
                            wire:model="devise"
                            label="Devise"
                            placeholder="Ex: EUR"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:input
                            wire:model="hs_code"
                            label="Code HS"
                            placeholder="Code de nomenclature douanière"
                        />
                        <flux:input
                            wire:model="statut_parkod"
                            label="Statut PARKOD"
                            placeholder="Ex: Validé"
                        />
                    </div>
                </div>

                <!-- État -->
                <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="sm">État du produit</flux:heading>
                            <p class="text-xs text-zinc-500 mt-0.5">
                                {{ $state ? 'Le produit sera actif et visible dans le catalogue' : 'Le produit sera désactivé' }}
                            </p>
                        </div>
                        <button
                            wire:click.prevent="$set('state', {{ $state ? 'false' : 'true' }})"
                            type="button"
                            role="switch"
                            aria-checked="{{ $state ? 'true' : 'false' }}"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shrink-0"
                            style="background-color: {{ $state ? '#22c55e' : '#d1d5db' }}"
                        >
                            <span
                                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                style="transform: translateX({{ $state ? '24px' : '4px' }})"
                            />
                        </button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button type="button" variant="ghost" wire:click="close">
                        Annuler
                    </flux:button>
                    <flux:button type="submit" variant="primary" icon="check">
                        Enregistrer
                    </flux:button>
                </div>

            </form>
        </div>
    </flux:modal>
</div>
