<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Permission;

new class extends Component
{
    public ?int    $permissionId   = null;
    public ?string $permissionName = null;

    #[On('delete-permission')]
    public function loadPermission(int $id): void
    {
        $permission = Permission::findOrFail($id);

        $this->permissionId   = $permission->id;
        $this->permissionName = $permission->name;

        $this->modal('delete-permission')->show();
    }

    public function delete(): void
    {
        $permission = Permission::findOrFail($this->permissionId);
        $permission->roles()->detach();
        $permission->delete();

        $this->reset(['permissionId', 'permissionName']);

        $this->dispatch('permission-deleted');
        $this->modal('delete-permission')->close();

        \Flux\Flux::toast(
            text: "Permissions supprimées avec succès",
            variant: 'success'
        );
    }
};
?>

<div>
    <flux:modal name="delete-permission" class="min-w-[22rem]" :dismissible="false">
        <div class="space-y-6">

            <div>
                <flux:heading size="lg">Supprimer la permission ?</flux:heading>
                <flux:text class="mt-2">
                    Vous êtes sur le point de supprimer la permission
                    <strong>{{ $permissionName }}</strong>.<br>
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
