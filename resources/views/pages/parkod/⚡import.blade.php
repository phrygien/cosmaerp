<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use App\Models\Produit;
use App\Models\Categorie;
use App\Models\Marque;
use App\Models\Ligne;
use App\Models\Type;

new class extends Component {

    use WithFileUploads;

    #[Validate(['attachments.*' => 'file|mimes:txt,csv|max:51200'])]
    public array $attachments = [];

    public bool  $uploading   = false;
    public bool  $ready       = false;
    public array $preview     = [];
    public array $result      = [];

    public function updatedAttachments(): void
    {
        $this->validate();
        $this->ready   = true;
        $this->preview = [];
        $this->result  = [];
    }

    public function preview(): void
    {
        if (empty($this->attachments)) {
            return;
        }

        $status = [
            'S' => 0, 'C' => 0, 'P' => 0, 'M' => 0,
            'N' => 0, 'E' => 0, 'V' => 0, 'F' => 0,
        ];

        foreach ($this->attachments as $file) {
            $lines = explode("\n", file_get_contents($file->getRealPath()));

            foreach ($lines as $line) {
                if (trim($line) === '') continue;
                $row          = explode(';', $line);
                $code_winparf = strtoupper(trim($row[23] ?? ''));
                if (isset($status[$code_winparf])) {
                    $status[$code_winparf]++;
                }
            }
        }

        $this->preview = $status;
    }

    public function import(): void
    {
        if (empty($this->attachments)) {
            return;
        }

        $new    = 0;
        $update = 0;

        foreach ($this->attachments as $file) {
            $lines = explode("\n", file_get_contents($file->getRealPath()));

            foreach ($lines as $line) {
                if (trim($line) === '') continue;

                $row = explode(';', $line);

                $ean               = trim($row[1]  ?? '');
                $code_marque       = trim($row[2]  ?? '');
                $code_categorie    = trim($row[3]  ?? '');
                $code_produit      = trim($row[4]  ?? '');
                $code_ligne        = trim($row[5]  ?? '');
                $designation_1     = trim($row[6]  ?? '');
                $designation_2     = trim($row[7]  ?? '');
                $marque_name       = trim($row[9]  ?? '');
                $libelle_ligne     = trim($row[10] ?? '');
                $code_type_produit = trim($row[11] ?? '');
                $type_produit      = trim($row[12] ?? '');
                $ref_fabr_n_1      = trim($row[14] ?? '');
                $tva               = trim($row[16] ?? '');
                $pght_parkod       = trim($row[17] ?? '');
                $code_winparf      = trim($row[23] ?? '');
                $devise            = trim($row[24] ?? '');
                $libelle_court     = trim($row[25] ?? '');
                $HS_code           = trim($row[26] ?? '');

                // Marque
                if (!Marque::where('code', $code_marque)->exists()) {
                    Marque::create(['code' => $code_marque, 'name' => $marque_name, 'state' => 1]);
                }

                // Categorie
                if (!\App\Models\Category::where('code', $code_categorie)->where('marque_code', $code_marque)->exists()) {
                    \App\Models\Category::create(['code' => $code_categorie, 'marque_code' => $code_marque, 'name' => $marque_name . ' ' . $code_categorie, 'state' => 1]);
                }

                // Ligne
                if (!Ligne::where('code', $code_ligne)->where('categorie_code', $code_categorie)->where('marque_code', $code_marque)->exists()) {
                    Ligne::create(['code' => $code_ligne, 'marque_code' => $code_marque, 'categorie_code' => $code_categorie, 'name' => $libelle_ligne, 'state' => 1]);
                }

                // Type
                if (!Type::where('id', $code_type_produit)->exists()) {
                    Type::create(['id' => $code_type_produit, 'name' => $type_produit, 'state' => 1]);
                }

                // Produit
                $product = \App\Models\Product::where('EAN', $ean)->first();

                if ($product) {
                    $product->update([
                        'product_code'        => $code_produit,
                        'marque_code'         => $code_marque,
                        'categorie_code'      => $code_categorie,
                        'ligne_code'          => $code_ligne,
                        'type_id'             => $code_type_produit,
                        'designation'         => $designation_1,
                        'designation_variant' => $designation_2,
                        'article'             => $libelle_court,
                        'ref_fabri_n_1'       => $ref_fabr_n_1,
                        'EAN'                 => $ean,
                        'pght_parkod'         => $pght_parkod,
                        'tva'                 => $tva,
                        'devise'              => $devise,
                        'statut_parkod'       => $code_winparf,
                        'hs_code'             => $HS_code,
                    ]);
                    $update++;
                } else {
                    \App\Models\Product::create([
                        'product_code'        => $code_produit,
                        'marque_code'         => $code_marque,
                        'categorie_code'      => $code_categorie,
                        'ligne_code'          => $code_ligne,
                        'type_id'             => $code_type_produit,
                        'designation'         => $designation_1,
                        'designation_variant' => $designation_2,
                        'article'             => $libelle_court,
                        'ref_fabri_n_1'       => $ref_fabr_n_1,
                        'EAN'                 => $ean,
                        'pght_parkod'         => $pght_parkod,
                        'tva'                 => $tva,
                        'devise'              => $devise,
                        'statut_parkod'       => $code_winparf,
                        'hs_code'             => $HS_code,
                        'state'               => 1,
                    ]);
                    $new++;
                }
            }
        }

        $this->result = ['new' => $new, 'update' => $update];
        $this->reset(['attachments', 'ready', 'preview']);

        \Flux\Flux::toast(
            heading: 'Importation PARKOD',
            text: "{$new} produit(s) créé(s), {$update} mis à jour",
            variant: 'success'
        );
    }

    public function cancel(): void
    {
        $this->reset(['attachments', 'preview', 'result', 'ready']);
    }
};
?>

<div class="mt-5 space-y-6">

    <!-- Upload -->
    <flux:field>
        <flux:label>Fichiers PARKOD</flux:label>
        <input
            type="file"
            wire:model="attachments"
            multiple
            accept=".txt,.csv"
            class="block w-full text-sm text-zinc-700 dark:text-zinc-300
                   file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                   file:text-sm file:font-medium
                   file:bg-zinc-100 file:text-zinc-700
                   dark:file:bg-zinc-700 dark:file:text-zinc-300
                   hover:file:bg-zinc-200 dark:hover:file:bg-zinc-600
                   cursor-pointer"
        />
        <div wire:loading wire:target="attachments" class="flex items-center gap-2 text-sm text-zinc-500 mt-1">
            <flux:icon name="arrow-path" class="size-4 animate-spin" />
            Chargement des fichiers...
        </div>
        <flux:error name="attachments.*" />
    </flux:field>

    <!-- Fichiers sélectionnés -->
    @if($ready && !empty($attachments))
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-700">
            @foreach($attachments as $file)
                <div class="flex items-center gap-3 px-4 py-2.5">
                    <flux:icon name="document-text" class="size-4 text-zinc-400 shrink-0" />
                    <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate">
                        {{ $file->getClientOriginalName() }}
                    </span>
                    <span class="ml-auto text-xs text-zinc-400 shrink-0">
                        {{ number_format($file->getSize() / 1024, 1) }} Ko
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Boutons -->
    <div class="flex flex-row gap-2">
        <flux:button
            variant="primary"
            wire:click="preview"
            wire:loading.attr="disabled"
            wire:target="preview"
            :disabled="!$ready"
            class="flex-1 lg:flex-none"
        >
            <span wire:loading.remove wire:target="preview">Prévisualiser</span>
            <span wire:loading wire:target="preview">Analyse...</span>
        </flux:button>

        <flux:button
            variant="primary"
            wire:click="import"
            wire:loading.attr="disabled"
            wire:target="import"
            :disabled="!$ready"
            class="flex-1 lg:flex-none"
        >
            <span wire:loading.remove wire:target="import">Importer</span>
            <span wire:loading wire:target="import">Importation...</span>
        </flux:button>

        <flux:button
            variant="danger"
            wire:click="cancel"
            class="flex-1 lg:flex-none"
        >
            Annuler
        </flux:button>
    </div>

    <!-- Prévisualisation -->
    @if(!empty($preview))
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-4 py-3 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm">Résumé de l'analyse</flux:heading>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 divide-zinc-200 dark:divide-zinc-700"
                 style="border-collapse: collapse;">
                @foreach([
                    'N' => ['label' => 'Nouveaux',  'color' => 'text-green-600 dark:text-green-400',  'bg' => 'bg-green-50  dark:bg-green-900/10'],
                    'M' => ['label' => 'Modifiés',  'color' => 'text-blue-600  dark:text-blue-400',   'bg' => 'bg-blue-50   dark:bg-blue-900/10'],
                    'S' => ['label' => 'Supprimés', 'color' => 'text-red-600   dark:text-red-400',    'bg' => 'bg-red-50    dark:bg-red-900/10'],
                    'C' => ['label' => 'Catalogue', 'color' => 'text-zinc-600  dark:text-zinc-400',   'bg' => ''],
                    'P' => ['label' => 'P',         'color' => 'text-zinc-600  dark:text-zinc-400',   'bg' => ''],
                    'E' => ['label' => 'E',         'color' => 'text-zinc-600  dark:text-zinc-400',   'bg' => ''],
                    'V' => ['label' => 'V',         'color' => 'text-zinc-600  dark:text-zinc-400',   'bg' => ''],
                    'F' => ['label' => 'F',         'color' => 'text-zinc-600  dark:text-zinc-400',   'bg' => ''],
                ] as $key => $info)
                    <div class="flex flex-col items-center justify-center px-4 py-5 gap-1 border border-zinc-200 dark:border-zinc-700 {{ $info['bg'] }}">
                        <span class="text-2xl font-bold {{ $info['color'] }}">
                            {{ $preview[$key] ?? 0 }}
                        </span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $info['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Résultat import -->
    @if(!empty($result))
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.heading>Importation terminée</flux:callout.heading>
            <flux:callout.text>
                {{ $result['new'] }} produit(s) créé(s) &bull; {{ $result['update'] }} produit(s) mis à jour
            </flux:callout.text>
        </flux:callout>
    @endif

</div>
