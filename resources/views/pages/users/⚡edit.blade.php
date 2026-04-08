<?php
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Str;

new class extends Component
{
    public ?int   $userId        = null;
    public string $name          = '';
    public string $email         = '';
    public string $password      = '';
    public string $status        = 'enable';
    public string $searchRole    = '';
    public array  $selectedRoles = [];

    #[On('edit-user')]
    public function loadUser(int $id): void
    {
        $user = User::with('roles')->findOrFail($id);

        $this->userId        = $user->id;
        $this->name          = $user->name;
        $this->email         = $user->email;
        $this->status        = $user->status;
        $this->selectedRoles = $user->roles->pluck('id')->toArray();
        $this->password      = '';

        $this->modal('edit-user')->show();
    }

    public function generatePassword(): void
    {
        $this->password = Str::password(12);
    }

    public function update(): void
    {
        $this->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $this->userId,
            'password' => 'nullable|string|min:8',
            'status'   => 'required|in:enable,disabled',
        ]);

        $user = User::findOrFail($this->userId);

        $data = [
            'name'   => $this->name,
            'email'  => $this->email,
            'status' => $this->status,
        ];

        if (!empty($this->password)) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($this->password);
        }

        $user->update($data);
        $user->roles()->sync($this->selectedRoles);

        $this->reset(['userId', 'name', 'email', 'password', 'status', 'selectedRoles', 'searchRole']);

        $this->dispatch('user-updated');
        $this->modal('edit-user')->close();

        \Flux\Flux::toast(
            heading: 'Utilisateur mis à jour',
            text: "Utilisateur mis à jour avec succès",
            variant: 'success'
        );
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

<div>
    <flux:modal name="edit-user" class="md:w-[600px]" :dismissible="false">
        <div class="space-y-5">

            <!-- Header -->
            <div>
                <flux:heading size="lg">Modifier l'utilisateur</flux:heading>
                <flux:text class="mt-1">Modifiez les informations de l'utilisateur.</flux:text>
            </div>

            <!-- Nom -->
            <flux:input
                wire:model="name"
                label="Nom complet"
                placeholder="Ex: John Doe"
                required
            />

            <!-- Email -->
            <flux:input
                wire:model="email"
                label="Adresse email"
                type="email"
                placeholder="Ex: john@exemple.com"
                required
            />

            <!-- Mot de passe -->
            <div>
                <flux:label class="mb-2">
                    Mot de passe
                    <span class="text-zinc-500 font-normal text-xs ml-1">(laisser vide pour ne pas modifier)</span>
                </flux:label>
                <div class="flex gap-2">
                    <flux:input
                        wire:model="password"
                        type="password"
                        placeholder="Nouveau mot de passe"
                        class="flex-1"
                    />
                    <flux:button
                        variant="ghost"
                        icon="arrow-path"
                        wire:click="generatePassword"
                        wire:loading.attr="disabled"
                        x-tooltip="Générer un mot de passe"
                    />
                </div>
            </div>

            <!-- Statut -->
            <flux:radio.group
                wire:model="status"
                label="Statut"
                variant="segmented"
                size="sm"
            >
                <flux:radio label="Activé" value="enable" />
                <flux:radio label="Désactivé" value="disabled" />
            </flux:radio.group>

            <!-- Rôles -->
            <div>
                <flux:label class="mb-2">Rôles</flux:label>

                <flux:input
                    wire:model.live.debounce="searchRole"
                    placeholder="Rechercher un rôle..."
                    icon="magnifying-glass"
                    class="mb-3"
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
            </div>

            <!-- Actions -->
            <div class="flex gap-2 pt-1">
                <flux:spacer />
                <flux:button
                    variant="ghost"
                    x-on:click="$flux.modal('edit-user').close()"
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
    </flux:modal>
</div>
