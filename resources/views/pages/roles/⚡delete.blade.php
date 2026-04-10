<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Role;

new class extends Component
{
    public ?int    $roleId   = null;
    public ?string $roleName = null;

    #[On('delete-role')]
    public function loadRole(int $id): void
    {
        $role = Role::findOrFail($id);

        $this->roleId   = $role->id;
        $this->roleName = $role->name;

        $this->modal('delete-role')->show();
    }

    public function delete(): void
    {
        $role = Role::findOrFail($this->roleId);
        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        $this->reset(['roleId', 'roleName']);

        $this->dispatch('role-deleted');
        $this->modal('delete-role')->close();

        \Flux\Flux::toast(
            text: "Rôle supprimé avec succès",
            variant: 'success'
        );
    }
};
?>

<div>
    <flux:modal name="delete-role" class="min-w-[22rem]" :dismissible="false">
        <div class="space-y-6">

            <div>
                <flux:heading size="lg">Supprimer le rôle ?</flux:heading>
                <flux:text class="mt-2">
                    Vous êtes sur le point de supprimer le rôle
                    <strong>{{ $roleName }}</strong>.<br>
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
