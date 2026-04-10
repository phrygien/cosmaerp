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

    #[Validate(['attachments.*' => 'file|mimes:txt|max:51200'])]
    public array $attachments = [];

    public bool  $uploading   = false;
    public bool  $ready       = false;
    public array $preview     = [];
    public array $result      = [];

    private function isValidRow(array $row): bool
    {
        if (count($row) < 27) {
            return false;
        }

        if (trim($row[0]) !== '01') {
            return false;
        }

        if (trim($row[1]) === '') {
            return false;
        }

        $validStatuts = ['S', 'C', 'P', 'M', 'N', 'E', 'V', 'F'];
        if (!in_array(strtoupper(trim($row[23])), $validStatuts, true)) {
            return false;
        }

        return true;
    }

    public function updatedAttachments(): void
    {
        $this->validate();
        $this->ready   = true;
        $this->preview = [];
        $this->result  = [];
    }

    public function generatePreview(): void
    {
        if (empty($this->attachments)) {
            return;
        }

        $status = [
            'S' => 0, 'C' => 0, 'P' => 0, 'M' => 0,
            'N' => 0, 'E' => 0, 'V' => 0, 'F' => 0,
        ];
        $errors = 0;

        foreach ($this->attachments as $file) {
            $lines = explode("\n", file_get_contents($file->getRealPath()));

            foreach ($lines as $line) {
                if (trim($line) === '') continue;

                $row = explode(';', $line);

                if (!$this->isValidRow($row)) {
                    $errors++;
                    continue;
                }

                $code_winparf = strtoupper(trim($row[23]));
                if (isset($status[$code_winparf])) {
                    $status[$code_winparf]++;
                }
            }
        }

        $this->preview = array_merge($status, ['errors' => $errors]);
    }

    public function import(): void
    {
        if (empty($this->attachments)) {
            return;
        }

        $new    = 0;
        $update = 0;
        $errors = 0;

        foreach ($this->attachments as $file) {
            $lines = explode("\n", file_get_contents($file->getRealPath()));

            foreach ($lines as $line) {
                if (trim($line) === '') continue;

                $row = explode(';', $line);

                if (!$this->isValidRow($row)) {
                    $errors++;
                    continue;
                }

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

        $this->result = ['new' => $new, 'update' => $update, 'errors' => $errors];
        $this->reset(['attachments', 'ready', 'preview']);
        $this->dispatch('parkod-imported');

        \Flux\Flux::toast(
            heading: 'Importation PARKOD',
            text: "{$new} produit(s) créé(s), {$update} mis à jour, {$errors} ligne(s) ignorée(s)",
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
            accept=".txt"
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
            wire:click="generatePreview"
            wire:loading.attr="disabled"
            wire:target="generatePreview"
            :disabled="!$ready"
            class="flex-1 lg:flex-none"
        >
            <span wire:loading.remove wire:target="generatePreview">Prévisualiser</span>
            <span wire:loading wire:target="generatePreview">Analyse...</span>
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

    @if(!empty($preview))
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm">Résumé de l'analyse</flux:heading>
            </div>

            <div class="p-4">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Code</flux:table.column>
                        <flux:table.column>Libellé</flux:table.column>
                        <flux:table.column>Nombre de lignes</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach([
                            'N'      => ['label' => 'Nouveaux',  'color' => 'green'],
                            'M'      => ['label' => 'Modifiés',  'color' => 'blue'],
                            'S'      => ['label' => 'Supprimés', 'color' => 'red'],
                            'C'      => ['label' => 'Catalogue', 'color' => 'zinc'],
                            'P'      => ['label' => 'P',         'color' => 'zinc'],
                            'E'      => ['label' => 'E',         'color' => 'zinc'],
                            'V'      => ['label' => 'V',         'color' => 'zinc'],
                            'F'      => ['label' => 'F',         'color' => 'zinc'],
                            'errors' => ['label' => 'Erreurs',   'color' => 'orange'],
                        ] as $key => $info)
                            <flux:table.row>
                                <flux:table.cell class="py-3 px-4">
                                    <flux:badge color="{{ $info['color'] }}" size="sm" inset="top bottom">
                                        {{ $key }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="py-3 px-4">{{ $info['label'] }}</flux:table.cell>
                                <flux:table.cell class="py-3 px-4" variant="strong">{{ $preview[$key] ?? 0 }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    @endif
</div>
