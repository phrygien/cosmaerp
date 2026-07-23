<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\Role;
use Flux\Flux;

new class extends Component
{
    public ?int $roleId = null;

    #[On('view-role')]
    public function show(int $id): void
    {
        $this->roleId = $id;

        Flux::modal('view-role')->show();
    }

    public function editFromView(): void
    {
        Flux::modal('view-role')->close();

        $this->dispatch('edit-role', id: $this->roleId);
    }

    #[Computed]
    public function role(): ?Role
    {
        if (! $this->roleId) {
            return null;
        }

        return Role::query()
            ->withCount(['permissions', 'users'])
            ->with(['permissions', 'users'])
            ->find($this->roleId);
    }
};
?>

<div>
    <flux:modal name="view-role" class="md:w-[42rem] lg:w-[52rem]" @close="roleId = null">
        @if ($this->role)
            <div class="flex flex-col gap-6">

                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading size="lg">{{ $this->role->name }}</flux:heading>
                        <flux:badge size="sm" color="zinc" inset="top bottom" class="mt-1">
                            {{ $this->role->slug }}
                        </flux:badge>
                    </div>
                </div>

                @if ($this->role->description)
                    <flux:text class="text-zinc-500">
                        {{ $this->role->description }}
                    </flux:text>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <flux:card class="p-4">
                        <p class="text-xs text-zinc-500">Utilisateurs</p>
                        <p class="text-2xl font-bold mt-1">{{ $this->role->users_count }}</p>
                    </flux:card>
                    <flux:card class="p-4">
                        <p class="text-xs text-zinc-500">Permissions</p>
                        <p class="text-2xl font-bold mt-1">{{ $this->role->permissions_count }}</p>
                    </flux:card>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <flux:heading size="sm" class="mb-2">Permissions</flux:heading>

                        @if ($this->role->permissions->isEmpty())
                            <p class="text-sm text-zinc-400 italic">Aucune permission assignée</p>
                        @else
                            <ul class="space-y-2 max-h-64 overflow-y-auto pr-1">
                                @foreach ($this->role->permissions as $permission)
                                    <li class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                        {{ $permission->name }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div>
                        <flux:heading size="sm" class="mb-2">Utilisateurs</flux:heading>

                        @if ($this->role->users->isEmpty())
                            <p class="text-sm text-zinc-400 italic">Aucun utilisateur avec ce rôle</p>
                        @else
                            <ul class="space-y-2 max-h-64 overflow-y-auto pr-1">
                                @foreach ($this->role->users->take(10) as $user)
                                    <li class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                        <flux:avatar size="xs" name="{{ $user->name }}" />
                                        {{ $user->name }}
                                    </li>
                                @endforeach

                                @if ($this->role->users_count > 10)
                                    <li class="text-sm text-zinc-400 italic">
                                        et {{ $this->role->users_count - 10 }} de plus...
                                    </li>
                                @endif
                            </ul>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:modal.close>
                        <flux:button variant="ghost">Fermer</flux:button>
                    </flux:modal.close>

                    <flux:button variant="primary" wire:click="editFromView">
                        Modifier
                    </flux:button>
                </div>
            </div>
        @else
            <div class="flex items-center justify-center py-10">
                <flux:text class="text-zinc-400">Chargement...</flux:text>
            </div>
        @endif
    </flux:modal>
</div>
