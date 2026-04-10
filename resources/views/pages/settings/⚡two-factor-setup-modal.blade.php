<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showVerificationStep = false;

    public bool $setupComplete = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    /**
     * Monter le composant.
     */
    public function mount(bool $requiresConfirmation): void
    {
        $this->requiresConfirmation = $requiresConfirmation;
    }

    #[On('start-two-factor-setup')]
    public function startTwoFactorSetup(): void
    {
        $enableTwoFactorAuthentication = app(EnableTwoFactorAuthentication::class);
        $enableTwoFactorAuthentication(auth()->user());

        $this->loadSetupData();
    }

    /**
     * Charger les données de configuration de l'authentification à deux facteurs pour l'utilisateur.
     */
    private function loadSetupData(): void
    {
        $user = auth()->user()?->fresh();

        try {
            if (! $user || ! $user->two_factor_secret) {
                throw new Exception('La clé secrète de configuration à deux facteurs n\'est pas disponible.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Échec de la récupération des données de configuration.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    /**
     * Afficher l'étape de vérification à deux facteurs si nécessaire.
     */
    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
        $this->dispatch('two-factor-enabled');
    }

    /**
     * Confirmer l'authentification à deux facteurs pour l'utilisateur.
     */
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->setupComplete = true;

        $this->closeModal();

        $this->dispatch('two-factor-enabled');
    }

    /**
     * Réinitialiser l'état de vérification à deux facteurs.
     */
    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    /**
     * Fermer la modale d'authentification à deux facteurs.
     */
    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showVerificationStep',
            'setupComplete',
        );

        $this->resetErrorBag();
    }

    /**
     * Obtenir l'état actuel de la configuration de la modale.
     */
    public function getModalConfigProperty(): array
    {
        if ($this->setupComplete) {
            return [
                'title' => __('Authentification à deux facteurs activée'),
                'description' => __('L\'authentification à deux facteurs est maintenant activée. Scannez le code QR ou saisissez la clé de configuration dans votre application d\'authentification.'),
                'buttonText' => __('Fermer'),
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => __('Vérifier le code d\'authentification'),
                'description' => __('Entrez le code à 6 chiffres de votre application d\'authentification.'),
                'buttonText' => __('Continuer'),
            ];
        }

        return [
            'title' => __('Activer l\'authentification à deux facteurs'),
            'description' => __('Pour finaliser l\'activation de l\'authentification à deux facteurs, scannez le code QR ou saisissez la clé de configuration dans votre application d\'authentification.'),
            'buttonText' => __('Continuer'),
        ];
    }
}; ?>

<flux:modal
    name="two-factor-setup-modal"
    class="max-w-md md:min-w-md"
    @close="closeModal"
>
    <div class="space-y-6">
        <div class="flex flex-col items-center space-y-4">
            <div class="p-0.5 w-auto rounded-full border border-stone-100 dark:border-stone-600 bg-white dark:bg-stone-800 shadow-sm">
                <div class="p-2.5 rounded-full border border-stone-200 dark:border-stone-600 overflow-hidden bg-stone-100 dark:bg-stone-200 relative">
                    <div class="flex items-stretch absolute inset-0 w-full h-full divide-x [&>div]:flex-1 divide-stone-200 dark:divide-stone-300 justify-around opacity-50">
                        @for ($i = 1; $i <= 5; $i++)
                            <div></div>
                        @endfor
                    </div>

                    <div class="flex flex-col items-stretch absolute w-full h-full divide-y [&>div]:flex-1 inset-0 divide-stone-200 dark:divide-stone-300 justify-around opacity-50">
                        @for ($i = 1; $i <= 5; $i++)
                            <div></div>
                        @endfor
                    </div>

                    <flux:icon.qr-code class="relative z-20 dark:text-accent-foreground"/>
                </div>
            </div>

            <div class="space-y-2 text-center">
                <flux:heading size="lg">{{ $this->modalConfig['title'] }}</flux:heading>
                <flux:text>{{ $this->modalConfig['description'] }}</flux:text>
            </div>
        </div>

        @if ($showVerificationStep)
            <div class="space-y-6">
                <div class="flex flex-col items-center space-y-3 justify-center">
                    <flux:otp
                        name="code"
                        wire:model="code"
                        length="6"
                        label="Code OTP"
                        label:sr-only
                        class="mx-auto"
                    />
                </div>

                <div class="flex items-center space-x-3">
                    <flux:button
                        variant="outline"
                        class="flex-1"
                        wire:click="resetVerification"
                    >
                        {{ __('Retour') }}
                    </flux:button>

                    <flux:button
                        variant="primary"
                        class="flex-1"
                        wire:click="confirmTwoFactor"
                        x-bind:disabled="$wire.code.length < 6"
                    >
                        {{ __('Confirmer') }}
                    </flux:button>
                </div>
            </div>
        @else
            @error('setupData')
            <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}"/>
            @enderror

            <div class="flex justify-center">
                <div class="relative w-64 overflow-hidden border rounded-lg border-stone-200 dark:border-stone-700 aspect-square">
                    @empty($qrCodeSvg)
                        <div class="absolute inset-0 flex items-center justify-center bg-white dark:bg-stone-700 animate-pulse">
                            <flux:icon.loading/>
                        </div>
                    @else
                        <div x-data class="flex items-center justify-center h-full p-4">
                            <div
                                class="bg-white p-3 rounded"
                                :style="($flux.appearance === 'dark' || ($flux.appearance === 'system' && $flux.dark)) ? 'filter: invert(1) brightness(1.5)' : ''"
                            >
                                {!! $qrCodeSvg !!}
                            </div>
                        </div>
                    @endempty
                </div>
            </div>

            <div>
                <flux:button
                    :disabled="$errors->has('setupData')"
                    variant="primary"
                    class="w-full"
                    wire:click="showVerificationIfNecessary"
                >
                    {{ $this->modalConfig['buttonText'] }}
                </flux:button>
            </div>

            <div class="space-y-4">
                <div class="relative flex items-center justify-center w-full">
                    <div class="absolute inset-0 w-full h-px top-1/2 bg-stone-200 dark:bg-stone-600"></div>
                    <span class="relative px-2 text-sm bg-white dark:bg-stone-800 text-stone-600 dark:text-stone-400">
                            {{ __('ou, saisissez le code manuellement') }}
                        </span>
                </div>

                <div
                    class="flex items-center space-x-2"
                    x-data="{
                            copied: false,
                            async copy() {
                                try {
                                    await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 1500);
                                } catch (e) {
                                    console.warn('Impossible de copier dans le presse-papiers');
                                }
                            }
                        }"
                >
                    <div class="flex items-stretch w-full border rounded-xl dark:border-stone-700">
                        @empty($manualSetupKey)
                            <div class="flex items-center justify-center w-full p-3 bg-stone-100 dark:bg-stone-700">
                                <flux:icon.loading variant="mini"/>
                            </div>
                        @else
                            <input
                                type="text"
                                readonly
                                value="{{ $manualSetupKey }}"
                                class="w-full p-3 bg-transparent outline-none text-stone-900 dark:text-stone-100"
                            />

                            <button
                                @click="copy()"
                                class="px-3 transition-colors border-l cursor-pointer border-stone-200 dark:border-stone-600"
                            >
                                <flux:icon.document-duplicate x-show="!copied" variant="outline"></flux:icon>
                                <flux:icon.check
                                    x-show="copied"
                                    variant="solid"
                                    class="text-green-500"
                                ></flux:icon>
                            </button>
                        @endempty
                    </div>
                </div>
            </div>
        @endif
    </div>
</flux:modal>
