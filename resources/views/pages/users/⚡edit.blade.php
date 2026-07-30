<?php
use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

new class extends Component
{
    public User $user;

    public string $name          = '';
    public string $email         = '';
    public string $password      = '';
    public string $status        = 'enable';
    public string $searchRole    = '';
    public array  $selectedRoles = [];

    public function mount(User $user): void
    {
        $user->load('roles');

        $this->user           = $user;
        $this->name            = $user->name;
        $this->email           = $user->email;
        $this->status          = $user->status;
        $this->selectedRoles   = $user->roles->pluck('id')->toArray();
    }

    public function generatePassword(): void
    {
        $this->password = Str::password(12);
    }

    public function update(): void
    {
        $this->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $this->user->id,
            'password' => 'nullable|string|min:8',
            'status'   => 'required|in:enable,disabled',
        ]);

        $data = [
            'name'   => $this->name,
            'email'  => $this->email,
            'status' => $this->status,
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $this->user->update($data);
        $this->user->roles()->sync($this->selectedRoles);

        $this->dispatch('user-updated');

        \Flux\Flux::toast(
            heading: 'Utilisateur mis à jour',
            text: "Utilisateur mis à jour avec succès",
            variant: 'success'
        );

        $this->redirect(route('users'), navigate: true);
    }

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->when($this->searchRole, fn($query) =>
            $query->where(function($q) {
                $q->where('name', 'like', "%{$this->searchRole}%")
                    ->orWhereIn('id', $this->selectedRoles);
            })
            )
            ->orderBy('name')
            ->get()
            ->sortByDesc(fn($role) => in_array($role->id, $this->selectedRoles))
            ->values();
    }
};
?>

<div class="max-w-4xl mx-auto">
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('users') }}" wire:navigate>Utilisateurs</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Modifier</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Modifier l'utilisateur</flux:heading>
            <flux:text class="mt-1">Modifiez les informations de l'utilisateur.</flux:text>
        </div>
    </div>

    <div class="space-y-6">

        <!-- Informations générales -->
        <flux:card class="p-6">
            <flux:heading size="lg">Informations générales</flux:heading>
            <flux:text class="mt-1 mb-2">Identité et accès de l'utilisateur.</flux:text>

            <div class="divide-y divide-zinc-900/10 dark:divide-white/10 border-t border-zinc-900/10 dark:border-white/10">

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 py-6">
                    <flux:label class="sm:pt-2">Nom complet</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0">
                        <flux:input
                            wire:model="name"
                            placeholder="Ex: John Doe"
                            :invalid="$errors->has('name')"
                            required
                        />
                        <flux:error name="name" />
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 py-6">
                    <flux:label class="sm:pt-2">Adresse email</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0">
                        <flux:input
                            wire:model="email"
                            type="email"
                            placeholder="Ex: john@exemple.com"
                            :invalid="$errors->has('email')"
                            required
                        />
                        <flux:error name="email" />
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 py-6">
                    <flux:label class="sm:pt-2">
                        Mot de passe
                        <span class="text-zinc-500 font-normal text-xs block mt-0.5">Laisser vide pour ne pas modifier</span>
                    </flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0">
                        <div class="flex gap-2">
                            <flux:input
                                wire:model="password"
                                type="password"
                                placeholder="Nouveau mot de passe"
                                class="flex-1"
                                :invalid="$errors->has('password')"
                            />
                            <flux:button
                                variant="ghost"
                                icon="arrow-path"
                                wire:click="generatePassword"
                                wire:loading.attr="disabled"
                                x-tooltip="Générer un mot de passe"
                            />
                        </div>
                        <flux:error name="password" />
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 py-6">
                    <flux:label class="sm:pt-2">Statut</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0">
                        <flux:radio.group wire:model="status" variant="segmented" size="sm">
                            <flux:radio label="Activé" value="enable" />
                            <flux:radio label="Désactivé" value="disabled" />
                        </flux:radio.group>
                    </div>
                </div>

            </div>
        </flux:card>

        <!-- Rôles -->
        <flux:card class="p-6">
            <flux:heading size="lg">Rôles</flux:heading>
            <flux:text class="mt-1 mb-4">Assignez un ou plusieurs rôles à l'utilisateur.</flux:text>

            <flux:input
                wire:model.live.debounce="searchRole"
                placeholder="Rechercher un rôle..."
                icon="magnifying-glass"
                class="mb-4"
            />

            <flux:checkbox.group wire:model="selectedRoles" variant="cards" class="flex-col">
                @forelse ($this->roles as $role)

                    @if ($loop->first && count($selectedRoles) > 0)
                        <p class="text-xs text-zinc-500 mb-1">
                            Sélectionnés ({{ count($selectedRoles) }})
                        </p>
                    @endif

                    @if (!$loop->first && in_array($this->roles[$loop->index - 1]->id, $selectedRoles) && !in_array($role->id, $selectedRoles))
                        <p class="text-xs text-zinc-500 mt-2 mb-1">Autres rôles</p>
                    @endif

                    <flux:checkbox
                        value="{{ $role->id }}"
                        label="{{ $role->name }}"
                        description="{{ $role->description ?? $role->slug }}"
                    />

                @empty
                    @if ($searchRole)
                        <div class="flex flex-col items-center justify-center py-6 text-center">
                            <p class="text-sm text-zinc-400">
                                Aucun rôle trouvé pour "{{ $searchRole }}"
                            </p>
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="$set('searchRole', '')"
                                class="mt-2"
                            >
                                Réinitialiser
                            </flux:button>
                        </div>
                    @endif
                @endforelse
            </flux:checkbox.group>
        </flux:card>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-2">
            <flux:button
                variant="danger"
                href="{{ route('users') }}"
                wire:navigate
            >
                Annuler
            </flux:button>
            <flux:button
                variant="primary"
                wire:click="update"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="update">Enregistrer</span>
                <span wire:loading wire:target="update">Enregistrement...</span>
            </flux:button>
        </div>

    </div>
</div>
