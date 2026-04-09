<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\User;

new class extends Component
{
    public ?int    $userId   = null;
    public ?string $userName = null;

    #[On('delete-user')]
    public function loadUser(int $id): void
    {
        $user = User::findOrFail($id);

        $this->userId   = $user->id;
        $this->userName = $user->name;

        $this->modal('delete-user')->show();
    }

    public function delete(): void
    {
        $user = User::findOrFail($this->userId);
        $user->roles()->detach();
        $user->delete();

        $this->reset(['userId', 'userName']);

        $this->dispatch('user-deleted');
        $this->modal('delete-user')->close();
    }
};
?>

<div>
    <flux:modal name="delete-user" class="min-w-[22rem]" :dismissible="false">
        <div class="space-y-6">

            <div>
                <flux:heading size="lg">Supprimer l'utilisateur ?</flux:heading>
                <flux:text class="mt-2">
                    Vous êtes sur le point de supprimer l'utilisateur
                    <strong>{{ $userName }}</strong>.<br>
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
