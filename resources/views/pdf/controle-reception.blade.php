<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Contrôle de réception</title>
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
           BLOC INFOS COMMANDE
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
           TABLEAU PRINCIPAL
           ══════════════════════════════════ */
        .main-table {
            width: 100%;
            border-collapse: collapse;
        }

        /* En-tête du tableau — fond gris foncé, texte blanc */
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

        /* Aligner la désignation à gauche dans l'en-tête */
        .main-table thead tr th.th-left {
            text-align: left;
            padding-left: 4px;
        }

        /* Lignes de données */
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

        /* Zébrage */
        .main-table tbody tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        /* Ligne de total */
        .main-table tfoot tr td {
            font-size: 8px;
            font-weight: bold;
            padding: 4px 3px;
            border-top: 1.2px solid #000;
        }

        .total-label {
            text-align: right;
            font-style: italic;
            padding-right: 6px;
        }

        .total-value {
            text-align: center;
            font-weight: bold;
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

        {{-- Titre centré + nom fournisseur --}}
        <td class="col-center">
            <div class="doc-title-box">Contrôle de réception</div>
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
     INFORMATIONS COMMANDE
     ══════════════════════════════════════════════════ --}}
<table class="info-table">
    <tr>
        {{-- Gauche : numéro commande, libellé, délais --}}
        <td class="info-left">
            <div>
                    <span class="info-bold">
                        N° de commande {{ $bon->numero_compte ?? sprintf('%07d', $commande?->id) }}
                        du {{ optional($commande?->created_at)->format('d/m/Y') ?? '__/__/____' }}
                    </span>
            </div>
            <div>
                <span class="info-italic">Libellé :</span>
                <span class="info-bold">{{ $commande?->libelle ?? '—' }}</span>
            </div>
            <div class="info-italic">Nombre de jours délais d'échéance</div>
            <div class="info-italic">Jour d'échéance</div>
        </td>

        {{-- Droite : magasin, chrono, dates --}}
        <td class="info-right">
            <div>
                    <span class="info-bold">
                        Magasin de livraison
                        N° {{ str_pad($magasin?->id ?? '', 4, '0', STR_PAD_LEFT) }}
                        {{ strtoupper($magasin?->name ?? '—') }}
                    </span>
            </div>
            <div class="info-italic">(N° Chrono :)</div>
            <div>
                <span class="info-italic">Date de livraison</span>
                &nbsp;
                <span class="info-bold">
                        {{ $bon->date_livraison_prevue
                            ? \Carbon\Carbon::parse($bon->date_livraison_prevue)->format('d/m/Y')
                            : '__/__/____' }}
                    </span>
            </div>
            <div>
                <span class="info-italic">Date de règlement</span>
                &nbsp;
                <span class="info-bold">__/__/____</span>
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
        <th style="width:9%">Réf. interne</th>
        <th class="th-left" style="width:22%">Désignation</th>
        <th style="width:7%">Article</th>
        <th style="width:9%">Réf. fourn.</th>
        <th style="width:14%">Gencod</th>
        <th style="width:5%">Géo.</th>
        <th style="width:7%">Qté cde</th>
        <th style="width:7%">Qté reç</th>
        <th style="width:7%">Cde grt</th>
        <th style="width:7%">Reçu grt</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($lignes as $ligne)
        <tr>
            <td>{{ $ligne['ref_interne'] }}</td>
            <td class="td-left">{{ $ligne['designation'] }}</td>
            <td>{{ $ligne['article'] }}</td>
            <td>{{ $ligne['ref_fournisseur'] }}</td>
            <td>{{ $ligne['gencode'] }}</td>
            <td>{{ $ligne['geo'] }}</td>
            <td>{{ $ligne['qte_commandee'] }}</td>
            <td>{{ $ligne['qte_recue'] }}</td>
            <td>{{ $ligne['cde_grt'] }}</td>
            <td>{{ $ligne['recu_grt'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="10"
                style="text-align:center;padding:10px;font-style:italic;color:#888;">
                Aucun produit dans cette commande.
            </td>
        </tr>
    @endforelse
    </tbody>
    <tfoot>
    <tr>
        <td colspan="6" class="total-label">
            Nombre total d'articles :
        </td>
        <td class="total-value">{{ $total_articles }}</td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    </tfoot>
</table>

{{-- ══════════════════════════════════════════════════
     PIED DE PAGE
     ══════════════════════════════════════════════════ --}}
<div class="footer">
    Document généré le {{ $date_impression }} à {{ $heure_impression }}
    &nbsp;|&nbsp;
    Bon N° {{ $bon->numero_compte ?? $bon->id }}
    &nbsp;|&nbsp;
    {{ strtoupper($fournisseur?->name ?? '') }}
</div>

</body>
</html>
