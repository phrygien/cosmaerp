<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\Role;

new class extends Component
{
    use WithPagination;

    public Role $role;

    public string $permSortBy        = 'name';
    public string $permSortDirection = 'asc';

    public string $userSortBy        = 'name';
    public string $userSortDirection = 'asc';

    public function mount(Role $role): void
    {
        $this->role = $role->loadCount(['permissions', 'users']);
    }

    public function sortPermissions(string $column): void
    {
        if ($this->permSortBy === $column) {
            $this->permSortDirection = $this->permSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->permSortBy        = $column;
            $this->permSortDirection = 'asc';
        }
    }

    public function sortUsers(string $column): void
    {
        if ($this->userSortBy === $column) {
            $this->userSortDirection = $this->userSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->userSortBy        = $column;
            $this->userSortDirection = 'asc';
        }
    }

    #[Computed]
    public function permissions()
    {
        return $this->role->permissions()
            ->orderBy($this->permSortBy, $this->permSortDirection)
            ->paginate(5, pageName: 'permissionsPage');
    }

    #[Computed]
    public function users()
    {
        return $this->role->users()
            ->orderBy($this->userSortBy, $this->userSortDirection)
            ->paginate(5, pageName: 'usersPage');
    }
};
?>

<div class="max-w-4xl mx-auto">
    <flux:button
        variant="ghost"
        size="sm"
        icon="chevron-left"
        href="{{ route('roles') }}"
        wire:navigate
        class="mb-4 -ml-2"
    >
        Rôles
    </flux:button>

    <!-- En-tête -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <flux:heading size="xl" level="1">{{ $role->name }}</flux:heading>
            <flux:badge size="sm" color="lime">{{ $role->slug }}</flux:badge>
        </div>

        <div class="flex items-center gap-2">
            <flux:button
                variant="ghost"
                href="{{ route('roles.edit', $role) }}"
                wire:navigate
            >
                Modifier
            </flux:button>
        </div>
    </div>

    <!-- Ligne d'infos rapides -->
    <div class="flex items-center gap-6 mb-8 text-sm text-zinc-600 dark:text-zinc-300">
        <div class="flex items-center gap-2">
            <flux:icon name="shield-check" class="text-zinc-400 size-4" />
            {{ $role->permissions_count }} permission{{ $role->permissions_count > 1 ? 's' : '' }}
        </div>
        <div class="flex items-center gap-2">
            <flux:icon name="users" class="text-zinc-400 size-4" />
            {{ $role->users_count }} utilisateur{{ $role->users_count > 1 ? 's' : '' }}
        </div>
        <div class="flex items-center gap-2">
            <flux:icon name="calendar" class="text-zinc-400 size-4" />
            {{ $role->created_at->translatedFormat('d M Y') }}
        </div>
    </div>

    <!-- Summary -->
    <flux:heading size="sm" class="mb-2">Résumé</flux:heading>
    <div class="border-t border-zinc-200 dark:border-zinc-700">
        <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
            <span class="text-sm text-zinc-500">Nom</span>
            <span class="text-sm">{{ $role->name }}</span>
        </div>
        <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
            <span class="text-sm text-zinc-500">Slug</span>
            <span class="text-sm font-mono">{{ $role->slug }}</span>
        </div>
        <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
            <span class="text-sm text-zinc-500">Description</span>
            <span class="text-sm">{{ $role->description ?: '—' }}</span>
        </div>
        <div class="flex items-center justify-between py-3 border-b border-zinc-200 dark:border-zinc-700">
            <span class="text-sm text-zinc-500">Permissions</span>
            <span class="text-sm">{{ $role->permissions_count }}</span>
        </div>
        <div class="flex items-center justify-between py-3">
            <span class="text-sm text-zinc-500">Utilisateurs</span>
            <span class="text-sm">{{ $role->users_count }}</span>
        </div>
    </div>

    <!-- Permissions -->
    <div class="mt-8">
        <flux:heading size="sm" class="mb-3">Permissions</flux:heading>

        @if ($role->permissions_count === 0)
            <p class="text-sm text-zinc-400 italic">Aucune permission assignée</p>
        @else
            <flux:card>
                <flux:table :paginate="$this->permissions">
                    <flux:table.columns>
                        <flux:table.column
                            sortable
                            :sorted="$permSortBy === 'name'"
                            :direction="$permSortDirection"
                            wire:click="sortPermissions('name')"
                        >
                            Nom
                        </flux:table.column>
                        <flux:table.column
                            sortable
                            :sorted="$permSortBy === 'group'"
                            :direction="$permSortDirection"
                            wire:click="sortPermissions('group')"
                        >
                            Groupe
                        </flux:table.column>
                        <flux:table.column
                            sortable
                            :sorted="$permSortBy === 'slug'"
                            :direction="$permSortDirection"
                            wire:click="sortPermissions('slug')"
                        >
                            Slug
                        </flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->permissions as $permission)
                            <flux:table.row :key="$permission->id">
                                <flux:table.cell variant="strong">{{ $permission->name }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" color="zinc">{{ $permission->group }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="font-mono text-xs whitespace-nowrap">
                                    {{ $permission->slug }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        @endif
    </div>

    <!-- Utilisateurs -->
    <div class="mt-8">
        <flux:heading size="sm" class="mb-3">Utilisateurs</flux:heading>

        @if ($role->users_count === 0)
            <p class="text-sm text-zinc-400 italic">Aucun utilisateur avec ce rôle</p>
        @else
            <flux:card>
                <flux:table :paginate="$this->users">
                    <flux:table.columns>
                        <flux:table.column
                            sortable
                            :sorted="$userSortBy === 'name'"
                            :direction="$userSortDirection"
                            wire:click="sortUsers('name')"
                        >
                            Utilisateur
                        </flux:table.column>
                        <flux:table.column
                            sortable
                            :sorted="$userSortBy === 'email'"
                            :direction="$userSortDirection"
                            wire:click="sortUsers('email')"
                        >
                            Email
                        </flux:table.column>
                        <flux:table.column
                            sortable
                            :sorted="$userSortBy === 'created_at'"
                            :direction="$userSortDirection"
                            wire:click="sortUsers('created_at')"
                        >
                            Inscrit le
                        </flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->users as $user)
                            <flux:table.row :key="$user->id">
                                <flux:table.cell class="flex items-center gap-3">
                                    <flux:avatar size="xs" name="{{ $user->name }}" />
                                    {{ $user->name }}
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $user->email }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">
                                    {{ $user->created_at->translatedFormat('d M Y') }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        @endif
    </div>
</div>
