<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .subtitle { color: #666; margin-bottom: 16px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-blue  { background: #dbeafe; color: #1e40af; }
        .badge-yellow{ background: #fef9c3; color: #854d0e; }
        .badge-red   { background: #fee2e2; color: #991b1b; }
        .grid2 { display: table; width: 100%; margin-bottom: 12px; }
        .grid2 .col { display: table-cell; width: 50%; vertical-align: top; }
        .info-grid { display: table; width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .info-grid .cell { display: table-cell; width: 33%; padding: 4px 8px 4px 0; vertical-align: top; }
        .label { font-size: 10px; color: #888; margin-bottom: 2px; }
        .value { font-weight: bold; }
        hr { border: none; border-top: 1px solid #e5e7eb; margin: 12px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #f4f4f5; text-align: left; padding: 6px 8px; font-size: 11px; color: #555; border-bottom: 2px solid #e5e7eb; }
        td { padding: 6px 8px; border-bottom: 1px solid #f1f1f1; font-size: 11px; }
        .text-right { text-align: right; }
        .total-section { margin-top: 16px; text-align: right; }
        .total-section .total-label { color: #888; font-size: 11px; }
        .total-section .total-amount { font-size: 20px; font-weight: bold; }
    </style>
</head>
<body>

<h1>Bon de commande</h1>
<p class="subtitle">{{ $commande->libelle ?? '—' }}</p>

@php
    $statusColor = match($commande->status) {
        -1 => 'red', 1 => 'blue', 2 => 'yellow', 3 => 'green', default => 'zinc',
    };
    $statusLabel = match($commande->status) {
        -1 => 'Annulée', 1 => 'Créée', 2 => 'Facturée', 3 => 'Clôturée', default => '—',
    };
@endphp

<span class="badge badge-{{ $statusColor }}">{{ $statusLabel }}</span>
<hr>

@if ($bonCommande)
    <div class="info-grid">
        <div class="cell">
            <div class="label">Code fournisseur</div>
            <div class="value">{{ $bonCommande->code_fournisseur ?? '—' }}</div>
        </div>
        <div class="cell">
            <div class="label">N° compte</div>
            <div class="value">{{ $bonCommande->numero_compte ?? '—' }}</div>
        </div>
        <div class="cell">
            <div class="label">Date commande</div>
            <div class="value">
                {{ $bonCommande->date_commande
                    ? \Carbon\Carbon::parse($bonCommande->date_commande)->translatedFormat('d F Y')
                    : '—' }}
            </div>
        </div>
        <div class="cell">
            <div class="label">Livraison prévue</div>
            <div class="value">
                {{ $bonCommande->date_livraison_prevue
                    ? \Carbon\Carbon::parse($bonCommande->date_livraison_prevue)->translatedFormat('d F Y')
                    : '—' }}
            </div>
        </div>
        <div class="cell">
            <div class="label">Magasin facturation</div>
            <div class="value">{{ $bonCommande->magasinFacturation?->name ?? '—' }}</div>
        </div>
        <div class="cell">
            <div class="label">Magasin livraison</div>
            <div class="value">{{ $bonCommande->magasinLivraison?->name ?? '—' }}</div>
        </div>
    </div>
@endif

<div class="grid2">
    <div class="col">
        <div class="label">Fournisseur</div>
        <div class="value">{{ $commande->fournisseur?->name ?? '—' }}</div>
    </div>
    <div class="col">
        <div class="label">Magasin livraison</div>
        <div class="value">{{ $commande->magasinLivraison?->name ?? '—' }}</div>
    </div>
</div>

<hr>

<table>
    <thead>
    <tr>
        <th>Produit</th>
        <th>Qté</th>
        <th>PU HT</th>
        <th>Remise</th>
        <th>PU net</th>
        <th class="text-right">Total HT</th>
        <th>Destinations</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($commande->details as $detail)
        <tr>
            <td>{{ $detail->product?->name ?? '—' }}</td>
            <td>{{ $detail->quantite }}</td>
            <td>{{ number_format($detail->pu_achat_HT, 2, ',', ' ') }} €</td>
            <td>{{ $detail->taux_remise ? $detail->taux_remise . ' %' : '—' }}</td>
            <td>{{ number_format($detail->pu_achat_net, 2, ',', ' ') }} €</td>
            <td class="text-right">{{ number_format($detail->pu_achat_net * $detail->quantite, 2, ',', ' ') }} €</td>
            <td>
                @foreach ($detail->destinations as $dest)
                    {{ $dest->magasin?->name ?? '—' }} ({{ $dest->quantite }})@if (!$loop->last), @endif
                @endforeach
            </td>
        </tr>
    @empty
        <tr><td colspan="7" style="text-align:center; color:#aaa;">Aucune ligne</td></tr>
    @endforelse
    </tbody>
</table>

<div class="total-section">
    <div class="total-label">Montant total</div>
    <div class="total-amount">{{ number_format($commande->montant_total, 2, ',', ' ') }} €</div>
    @if ($bonCommande?->montant_commande_net)
        <div class="total-label">
            Net : <strong>{{ number_format($bonCommande->montant_commande_net, 2, ',', ' ') }} €</strong>
        </div>
    @endif
</div>

</body>
</html>
