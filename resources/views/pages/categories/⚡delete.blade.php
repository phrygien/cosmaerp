<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Category;

new class extends Component
{
    public ?int    $categoryCode = null;
    public ?string $categoryName = null;

    #[On('delete-category')]
    public function loadCategory(int $code): void
    {
        $category = Category::findOrFail($code);

        $this->categoryCode = $category->code;
        $this->categoryName = $category->name;

        $this->modal('delete-category')->show();
    }

    public function delete(): void
    {
        $category = Category::findOrFail($this->categoryCode);
        $category->delete();

        $this->reset(['categoryCode', 'categoryName']);

        $this->dispatch('category-deleted');
        $this->modal('delete-category')->close();

        \Flux\Flux::toast(
            heading: 'Suppression de la catégorie',
            text: 'Catégorie supprimée avec succès',
            variant: 'success'
        );
    }
};
?>

<div>
    <flux:modal name="delete-category" class="min-w-[22rem]" :dismissible="false">
        <div class="space-y-6">

            <div>
                <flux:heading size="lg">Supprimer la catégorie ?</flux:heading>
                <flux:text class="mt-2">
                    Vous êtes sur le point de supprimer la catégorie
                    <strong>{{ $categoryName }}</strong>.<br>
                    Cette action est irréversible.
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Annuler</flux:button>
                </flux:modal.close>
                <flux:button
                    variant="danger"
                    wire:click="delete"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="delete">Supprimer</span>
                    <span wire:loading wire:target="delete">Suppression...</span>
                </flux:button>
            </div>

        </div>
    </flux:modal>
</div>
