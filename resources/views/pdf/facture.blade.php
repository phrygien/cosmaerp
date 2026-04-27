<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture {{ $facture->numero ?? $facture->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8.5px;
            color: #000;
            background: #fff;
            padding: 14px 18px;
        }

        /* ══════════════════════════════════
           EN-TÊTE — 3 colonnes
           ══════════════════════════════════ */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }

        .col-left {
            width: 28%;
            vertical-align: top;
        }

        .col-center {
            width: 42%;
            text-align: center;
            vertical-align: middle;
            padding: 0 6px;
        }

        .col-right {
            width: 30%;
            text-align: right;
            vertical-align: top;
            font-size: 8px;
            white-space: nowrap;
        }

        .company-name {
            font-size: 10px;
            font-weight: bold;
            line-height: 1.3;
        }

        .company-city {
            font-size: 8.5px;
        }

        .doc-title-box {
            border: 1.5px solid #000;
            display: inline-block;
            padding: 3px 18px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .supplier-name {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        /* ══════════════════════════════════
           SÉPARATEUR
           ══════════════════════════════════ */
        .hr {
            border: none;
            border-top: 0.8px solid #555;
            margin: 5px 0;
        }

        /* ══════════════════════════════════
           BLOC INFOS FACTURE
           ══════════════════════════════════ */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .info-left {
            width: 50%;
            vertical-align: top;
            font-size: 8.5px;
            line-height: 2;
        }

        .info-right {
            width: 50%;
            vertical-align: top;
            font-size: 8.5px;
            line-height: 2;
            padding-left: 30px;
        }

        .info-italic {
            font-style: italic;
        }

        .info-bold {
            font-weight: bold;
        }

        /* ══════════════════════════════════
           BADGE TYPE FACTURE
           ══════════════════════════════════ */
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            background-color: #e8f4fd;
            border: 0.5px solid #3b82f6;
            color: #1d4ed8;
        }

        /* ══════════════════════════════════
           TABLEAU PRINCIPAL
           ══════════════════════════════════ */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .main-table thead tr th {
            background-color: #5a5a5a;
            color: #ffffff;
            font-size: 7.5px;
            font-weight: bold;
            text-align: center;
            padding: 4px 2px;
            border: 0.5px solid #333;
            white-space: nowrap;
        }

        .main-table thead tr th.th-left {
            text-align: left;
            padding-left: 4px;
        }

        .main-table tbody tr td {
            font-size: 7.5px;
            padding: 3px 3px;
            border: 0.5px solid #bbb;
            text-align: center;
            vertical-align: middle;
        }

        .main-table tbody tr td.td-left {
            text-align: left;
            padding-left: 4px;
        }

        .main-table tbody tr td.td-right {
            text-align: right;
            padding-right: 4px;
        }

        .main-table tbody tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        /* ══════════════════════════════════
           BLOC TOTAUX
           ══════════════════════════════════ */
        .totaux-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .totaux-table td {
            font-size: 8px;
            padding: 3px 4px;
            vertical-align: top;
        }

        .totaux-spacer {
            width: 55%;
        }

        .totaux-right {
            width: 45%;
        }

        .totaux-inner {
            width: 100%;
            border-collapse: collapse;
            border: 0.8px solid #aaa;
        }

        .totaux-inner tr td {
            padding: 3px 6px;
            font-size: 8px;
            border-bottom: 0.5px solid #ddd;
        }

        .totaux-inner tr td.t-label {
            text-align: left;
            font-style: italic;
            color: #333;
            width: 55%;
        }

        .totaux-inner tr td.t-value {
            text-align: right;
            font-weight: bold;
            width: 45%;
        }

        .totaux-inner tr.total-ttc td {
            background-color: #5a5a5a;
            color: #fff;
            font-weight: bold;
            font-size: 9px;
            border-bottom: none;
        }

        .totaux-inner tr.total-ttc td.t-label {
            color: #fff;
        }

        /* ══════════════════════════════════
           PIED DE PAGE
           ══════════════════════════════════ */
        .footer {
            margin-top: 12px;
            font-size: 7px;
            color: #666;
            text-align: center;
            border-top: 0.5px solid #bbb;
            padding-top: 4px;
        }

        .mention {
            margin-top: 8px;
            font-size: 7px;
            color: #555;
            font-style: italic;
        }
    </style>
</head>
<body>

{{-- ══════════════════════════════════════════════════
     EN-TÊTE
     ══════════════════════════════════════════════════ --}}
<table class="header-table">
    <tr>
        {{-- Société (statique) --}}
        <td class="col-left">
            <div class="company-name">COSMA PARFUMERIES</div>
            <div class="company-city">NANTERRE</div>
        </td>

        {{-- Titre + fournisseur --}}
        <td class="col-center">
            <div class="doc-title-box">Facture</div>
            <br>
            <div class="supplier-name">
                {{ strtoupper($fournisseur?->name ?? '—') }}
            </div>
        </td>

        {{-- Date / Heure / Page --}}
        <td class="col-right">
            Date {{ $date_impression }} &nbsp; Heure {{ $heure_impression }}<br>
            Page&nbsp;1
        </td>
    </tr>
</table>

<hr class="hr">

{{-- ══════════════════════════════════════════════════
     INFORMATIONS FACTURE
     ══════════════════════════════════════════════════ --}}
<table class="info-table">
    <tr>
        {{-- Colonne gauche --}}
        <td class="info-left">
            <div>
                <span class="info-italic">N° de facture :</span>
                <span class="info-bold">{{ $facture->numero ?? sprintf('%07d', $facture->id) }}</span>
                @if($facture->type)
                    &nbsp;<span class="badge">{{ strtoupper($facture->type) }}</span>
                @endif
            </div>
            <div>
                <span class="info-italic">Libellé :</span>
                <span class="info-bold">{{ $facture->libelle ?? '—' }}</span>
            </div>
            <div>
                <span class="info-italic">N° de commande :</span>
                <span class="info-bold">{{ $commande?->id ?? '—' }}</span>
            </div>
            <div>
                <span class="info-italic">Date de commande :</span>
                <span class="info-bold">
                        {{ $facture->date_commande
                            ? \Carbon\Carbon::parse($facture->date_commande)->format('d/m/Y')
                            : '__/__/____' }}
                    </span>
            </div>
        </td>

        {{-- Colonne droite --}}
        <td class="info-right">
            <div>
                    <span class="info-bold">
                        Magasin de livraison
                        N° {{ str_pad($magasin?->id ?? '', 4, '0', STR_PAD_LEFT) }}
                        {{ strtoupper($magasin?->name ?? '—') }}
                    </span>
            </div>
            <div>
                <span class="info-italic">Fournisseur :</span>
                <span class="info-bold">
                        {{ strtoupper($fournisseur?->raison_social ?? $fournisseur?->name ?? '—') }}
                    </span>
            </div>
            <div>
                <span class="info-italic">Date de réception :</span>
                <span class="info-bold">
                        {{ $facture->date_reception
                            ? \Carbon\Carbon::parse($facture->date_reception)->format('d/m/Y')
                            : '__/__/____' }}
                    </span>
            </div>
            <div>
                <span class="info-italic">Remise globale :</span>
                <span class="info-bold">
                        {{ $facture->remise ? number_format($facture->remise, 2).' %' : '—' }}
                    </span>
                &nbsp;&nbsp;
                <span class="info-italic">TVA :</span>
                <span class="info-bold">
                        {{ $facture->tax ? number_format($facture->tax, 2).' %' : '—' }}
                    </span>
            </div>
        </td>
    </tr>
</table>

{{-- ══════════════════════════════════════════════════
     TABLEAU DES LIGNES
     ══════════════════════════════════════════════════ --}}
<table class="main-table">
    <thead>
    <tr>
        <th style="width:8%">Réf. interne</th>
        <th class="th-left" style="width:20%">Désignation</th>
        <th style="width:6%">Article</th>
        <th style="width:8%">Réf. fourn.</th>
        <th style="width:12%">EAN</th>
        <th style="width:6%">Qté cde</th>
        <th style="width:8%">PU HT</th>
        <th style="width:6%">Remise %</th>
        <th style="width:8%">Montant HT</th>
        <th style="width:8%">Net HT</th>
        <th style="width:5%">TVA %</th>
        <th style="width:8%">Montant TTC</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($lignes as $ligne)
        <tr>
            <td>{{ $ligne['ref_interne'] }}</td>
            <td class="td-left">{{ $ligne['designation'] }}</td>
            <td>{{ $ligne['article'] }}</td>
            <td>{{ $ligne['ref_fournisseur'] }}</td>
            <td>{{ $ligne['ean'] }}</td>
            <td>{{ $ligne['qte_commandee'] }}</td>
            <td class="td-right">{{ number_format($ligne['pu_ht'], 2) }}</td>
            <td>{{ $ligne['taux_remise'] > 0 ? number_format($ligne['taux_remise'], 2).' %' : '—' }}</td>
            <td class="td-right">{{ number_format($ligne['montant_ht'], 2) }}</td>
            <td class="td-right">{{ number_format($ligne['montant_net_ht'], 2) }}</td>
            <td>{{ number_format($ligne['tva'], 2) }} %</td>
            <td class="td-right">{{ number_format($ligne['montant_ttc'], 2) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="12"
                style="text-align:center;padding:10px;font-style:italic;color:#888;">
                Aucune ligne de facture.
            </td>
        </tr>
    @endforelse
    </tbody>
</table>

{{-- ══════════════════════════════════════════════════
     TOTAUX
     ══════════════════════════════════════════════════ --}}
<table class="totaux-table">
    <tr>
        {{-- Espace gauche --}}
        <td class="totaux-spacer">
            <div class="mention">
                Nombre total d'articles : <strong>{{ $total_articles }}</strong>
            </div>
        </td>

        {{-- Bloc totaux à droite --}}
        <td class="totaux-right">
            <table class="totaux-inner">
                <tr>
                    <td class="t-label">Total brut HT</td>
                    <td class="t-value">{{ number_format($total_ht, 2) }}</td>
                </tr>
                <tr>
                    <td class="t-label">Total remise</td>
                    <td class="t-value">- {{ number_format($total_remise, 2) }}</td>
                </tr>
                <tr>
                    <td class="t-label">Total net HT</td>
                    <td class="t-value">{{ number_format($total_net_ht, 2) }}</td>
                </tr>
                <tr>
                    <td class="t-label">Total TVA</td>
                    <td class="t-value">{{ number_format($total_tva, 2) }}</td>
                </tr>
                <tr class="total-ttc">
                    <td class="t-label">Total TTC</td>
                    <td class="t-value">{{ number_format($total_ttc, 2) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ══════════════════════════════════════════════════
     PIED DE PAGE
     ══════════════════════════════════════════════════ --}}
<div class="footer">
    Document généré le {{ $date_impression }} à {{ $heure_impression }}
    &nbsp;|&nbsp;
    Facture N° {{ $facture->numero ?? $facture->id }}
    &nbsp;|&nbsp;
    {{ strtoupper($fournisseur?->name ?? '') }}
</div>

</body>
</html>
