<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        .header {
            display: table;
            width: 100%;
            border: 2px solid #000;
        }
        .logo {
            display: table-cell;
            width: 250px;
            vertical-align: middle;
            padding: 8px;
            border-right: 2px solid #000;
        }
        .logo img {
            max-height: 70px;
        }
        .title {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            padding: 10px;
        }
        .info-right {
            display: table-cell;
            width: 220px;
            vertical-align: top;
            border-left: 2px solid #000;
            padding: 8px;
            font-size: 11px;
        }
        .info-right td {
            padding: 2px 0;
        }

        .main-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0 10px 0;
        }

        .field-row {
            margin-bottom: 8px;
        }
        .field-label {
            font-weight: bold;
            width: 140px;
            display: inline-block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            border: 2px solid #000;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: center;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .designation { text-align: left; }

        .footer {
            display: table;
            width: 100%;
            margin-top: 30px;
        }
        .footer div {
            display: table-cell;
            width: 50%;
            text-align: center;
            border: 2px solid #000;
            padding: 10px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>

<!-- En-tête -->
<div class="header">
    <div class="logo">
        <!-- Remplace par ton logo réel -->
        <strong style="font-size:20px; color:#b91c1c;">Cosma</strong><br>
        <strong style="font-size:14px;">Parfumeries</strong>
    </div>
    <div class="title">BON DE COMMANDE</div>
    <div class="info-right">
        <table style="width:100%; border:none;">
            <tr><td><strong>Code</strong></td><td>FR.A-02</td></tr>
            <tr><td><strong>Version</strong></td><td>01</td></tr>
            <tr><td><strong>Date</strong></td><td>24/07/2018</td></tr>
        </table>
    </div>
</div>

<div class="main-title">BON DE COMMANDE :</div>

<!-- Informations -->
<div class="field-row">
    <span class="field-label">N° :</span>
    <strong>{{ $bonCommande->numero ?? '..........................' }}</strong>
</div>
<div class="field-row">
    <span class="field-label">DATE :</span>
    <strong>{{ $bonCommande->date_commande ? \Carbon\Carbon::parse($bonCommande->date_commande)->format('d/m/Y') : '..........................' }}</strong>
</div>

<div class="field-row">
    <span class="field-label">FOURNISSEUR :</span>
    <strong>{{ $commande->fournisseur?->name ?? '........................................................................' }}</strong>
</div>

<div class="field-row">
    <span class="field-label">V/ REF :</span>
    <strong>{{ $bonCommande->reference_fournisseur ?? '........................................................................' }}</strong>
</div>

<div class="field-row">
    <span class="field-label">IMPUTATION :</span>
    <strong>{{ $bonCommande->imputation ?? '........................................................................' }}</strong>
</div>

<!-- Tableau des articles -->
<table>
    <thead>
    <tr>
        <th width="10%">Quantité</th>
        <th width="55%">Désignations</th>
        <th width="20%">EAN</th>
        <th width="15%">P.U H.T</th>
        <th width="15%">% de Remise</th>
        <th width="20%">P.T H.T</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($commande->details as $detail)
        <tr>
            <td>{{ $detail->quantite }}</td>
            <td class="designation">{{ $detail->product?->designation ?? '—' }}</td>
            <td class="designation">{{ $detail->product?->EAN ?? '—' }}</td>
            <td>{{ number_format($detail->pu_achat_HT, 2, ',', ' ') }}</td>
            <td>{{ number_format($detail->taux_remise, 2, ',', ' ') }}</td>
            <td>{{ number_format($detail->pu_achat_net * $detail->quantite, 2, ',', ' ') }}</td>
        </tr>
    @empty
        <tr><td colspan="4" style="height:300px;"></td></tr>
    @endforelse
    </tbody>
</table>

<!-- Bas de page -->
<div class="footer">
    <div>Visa Service Achat</div>
    <div>Visa Direction</div>
</div>

</body>
</html>
