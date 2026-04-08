<?php
use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

new class extends Component
{
    public string $name          = '';
    public string $email         = '';
    public string $password      = '';
    public string $status        = 'enable';
    public string $searchRole    = '';
    public array  $selectedRoles = [];

    public function generatePassword(): void
    {
        $this->password = Str::password(12);
    }

    public function save(): void
    {
        $this->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'status'   => 'required|in:enable,disabled',
        ]);

        $user = User::create([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => Hash::make($this->password),
            'status'   => $this->status,
        ]);

        if (count($this->selectedRoles) > 0) {
            $user->roles()->sync($this->selectedRoles);
        }

        $this->reset(['name', 'email', 'password', 'status', 'selectedRoles', 'searchRole']);

        $this->dispatch('user-created');
        $this->modal('create-user')->close();

        \Flux\Flux::toast(
            heading: 'Utilisateur créé',
            text: "Utilisateur créé avec succès",
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
    <flux:modal name="create-user" class="md:w-[600px]" :dismissible="false">
        <div class="space-y-5">

            <!-- Header -->
            <div>
                <flux:heading size="lg">Ajouter un utilisateur</flux:heading>
                <flux:text class="mt-1">Remplissez les informations du nouvel utilisateur.</flux:text>
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
                <flux:label class="mb-2">Mot de passe</flux:label>
                <div class="flex gap-2">
                    <flux:input
                        wire:model="password"
                        type="password"
                        placeholder="Minimum 8 caractères"
                        class="flex-1"
                        required
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
                    x-on:click="$flux.modal('create-user').close()"
                >
                    Annuler
                </flux:button>
                <flux:button
                    variant="primary"
                    wire:click="save"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="save">Créer</span>
                    <span wire:loading wire:target="save">Création...</span>
                </flux:button>
            </div>

        </div>
    </flux:modal>
</div>
