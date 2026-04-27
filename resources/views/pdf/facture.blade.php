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
            font-size: 11px;
            color: #222;
            background: #f0f4f8;
        }

        /* ══════════════════════════════════
           PAGE WRAPPER centré avec bordure
           ══════════════════════════════════ */
        .page {
            border: 1px solid #b0c4d8;
            margin: 24px auto;
            padding: 32px 40px 28px;
            background: #fff;
            max-width: 720px;
        }

        /* ══════════════════════════════════
           EN-TÊTE : LOGO + TITRE FACTURE
           ══════════════════════════════════ */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .header-logo-cell {
            width: 35%;
            vertical-align: top;
        }

        .logo-box {
            border: 1px solid #ccc;
            width: 90px;
            height: 70px;
            display: table;
            text-align: center;
            vertical-align: middle;
            padding: 8px;
        }

        .logo-icon {
            font-size: 36px;
            color: #e8813a;
            line-height: 1;
        }

        .logo-name {
            font-size: 9px;
            color: #555;
            margin-top: 4px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-title-cell {
            width: 65%;
            vertical-align: top;
            text-align: right;
        }

        .doc-title {
            font-size: 28px;
            font-weight: bold;
            color: #1a3a5c;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        /* ══════════════════════════════════
           BLOC ÉMETTEUR + INFOS FACTURE
           ══════════════════════════════════ */
        .emetteur-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            border-top: 1px solid #b0c4d8;
            padding-top: 10px;
        }

        .emetteur-left {
            width: 55%;
            vertical-align: top;
            font-size: 10px;
            color: #333;
            line-height: 1.85;
            padding-top: 8px;
        }

        .emetteur-right {
            width: 45%;
            vertical-align: top;
            text-align: right;
            font-size: 10px;
            padding-top: 8px;
        }

        .emetteur-right table {
            width: 100%;
            border-collapse: collapse;
        }

        .emetteur-right table td {
            font-size: 10px;
            padding: 2px 0;
            color: #333;
        }

        .er-label {
            text-align: right;
            color: #555;
            padding-right: 4px;
        }

        .er-value {
            text-align: right;
            font-weight: 600;
            color: #1a3a5c;
            white-space: nowrap;
        }

        /* ══════════════════════════════════
           BLOCS ADRESSE FACTURATION / LIVRAISON
           ══════════════════════════════════ */
        .addr-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .addr-cell {
            width: 50%;
            vertical-align: top;
            border: 1px solid #b0c4d8;
            padding: 0;
        }

        .addr-cell-right {
            width: 50%;
            vertical-align: top;
            border: 1px solid #b0c4d8;
            border-left: none;
            padding: 0;
        }

        .addr-header {
            background-color: #dce8f4;
            color: #1a3a5c;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 12px;
            border-bottom: 1px solid #b0c4d8;
        }

        .addr-body {
            padding: 10px 12px;
            font-size: 10px;
            color: #333;
            line-height: 1.9;
        }

        /* ══════════════════════════════════
           TABLEAU DES LIGNES
           ══════════════════════════════════ */
        .lines-outer {
            border: 1px solid #b0c4d8;
            margin-bottom: 0;
        }

        .lines-table {
            width: 100%;
            border-collapse: collapse;
        }

        .lines-table thead tr th {
            background-color: #dce8f4;
            color: #1a3a5c;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 9px 10px;
            border-bottom: 1px solid #b0c4d8;
            border-right: 1px solid #b0c4d8;
            text-align: left;
        }

        .lines-table thead tr th.th-right {
            text-align: right;
        }

        .lines-table thead tr th.th-center {
            text-align: center;
        }

        .lines-table tbody tr td {
            font-size: 10px;
            padding: 9px 10px;
            vertical-align: top;
            border-bottom: 1px solid #e8eef5;
            border-right: 1px solid #e8eef5;
            color: #333;
        }

        .lines-table tbody tr td.td-right {
            text-align: right;
        }

        .lines-table tbody tr td.td-center {
            text-align: center;
        }

        /* ══════════════════════════════════
           TOTAUX (dans le tableau)
           ══════════════════════════════════ */
        .totaux-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid #b0c4d8;
        }

        .totaux-spacer {
            width: 55%;
            border-right: 1px solid #b0c4d8;
        }

        .totaux-right {
            width: 45%;
            vertical-align: top;
        }

        .totaux-inner {
            width: 100%;
            border-collapse: collapse;
        }

        .totaux-inner tr td {
            padding: 4px 8px;
            font-size: 8px;
            border-bottom: 1px solid #e8eef5;
        }

        .ti-label {
            font-weight: bold;
            text-align: right;
            color: #1a3a5c;
            padding-right: 12px;
            text-transform: uppercase;
            font-size: 7.5px;
            letter-spacing: 0.3px;
        }

        .ti-value {
            text-align: right;
            color: #333;
            white-space: nowrap;
        }

        /* Ligne TOTAL mise en évidence */
        .ti-total td {
            background-color: #dce8f4;
            font-weight: bold;
            color: #1a3a5c;
            font-size: 9px;
        }

        /* Ligne SOLDE finale */
        .ti-solde td {
            background-color: #1a3a5c;
            color: #fff;
            font-weight: bold;
            font-size: 9px;
        }

        /* ══════════════════════════════════
           PIED DE PAGE
           ══════════════════════════════════ */
        .footer-box {
            background-color: #f0f5fa;
            border: 1px solid #b0c4d8;
            border-top: none;
            padding: 10px 14px;
            text-align: center;
        }

        .footer-line {
            font-size: 8.5px;
            color: #444;
            line-height: 2;
        }

        .footer-bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ══════════════════════════════════════════════════
         EN-TÊTE : LOGO MAGASIN + TITRE "FACTURE"
         ══════════════════════════════════════════════════ --}}
    <table class="header-table">
        <tr>
            <td class="header-logo-cell">
                <div class="logo-box">
                    <div class="logo-icon">&#9632;</div>
                    <div class="logo-name">
                        {{ $magasinEmetteur?->name ?? 'Cosma' }}
                    </div>
                </div>
            </td>
            <td class="header-title-cell">
                <div class="doc-title">Facture</div>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════════
         ÉMETTEUR (magasin) + INFOS FACTURE
         ══════════════════════════════════════════════════ --}}
    <table class="emetteur-table">
        <tr>
            {{-- Coordonnées magasin émetteur --}}
            <td class="emetteur-left">
                <strong>{{ $magasinEmetteur?->name ?? 'COSMA PARFUMERIES' }}</strong><br>
                @if($magasinEmetteur?->adress)
                    {{ $magasinEmetteur->adress }}<br>
                @endif
                @if($magasinEmetteur?->telephone)
                    {{ $magasinEmetteur->telephone }}<br>
                @endif
                @if($magasinEmetteur?->email)
                    {{ $magasinEmetteur->email }}
                @endif
            </td>

            {{-- Infos facture : n°, commande, dates, solde --}}
            <td class="emetteur-right">
                <table>
                    <tr>
                        <td class="er-label">Numéro de facture :</td>
                        <td class="er-value">{{ $facture->numero ?? $facture->id }}</td>
                    </tr>
                    <tr>
                        <td class="er-label">Numéro de commande :</td>
                        <td class="er-value">{{ $commande?->id ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="er-label">Date de la facture :</td>
                        <td class="er-value">
                            {{ $facture->date_commande
                                ? \Carbon\Carbon::parse($facture->date_commande)->translatedFormat('d M Y')
                                : now()->translatedFormat('d M Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="er-label">Date d'échéance :</td>
                        <td class="er-value">
                            {{ $facture->date_reception
                                ? \Carbon\Carbon::parse($facture->date_reception)->translatedFormat('d M Y')
                                : '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="er-label">Solde dû :</td>
                        <td class="er-value" style="color:#c0392b;">
                            {{ number_format($total_ttc, 2, ',', ' ') }} EUR
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════════
         ADRESSE DE FACTURATION / LIVRAISON
         ══════════════════════════════════════════════════ --}}
    <table class="addr-table">
        <tr>
            {{-- Adresse de facturation = Fournisseur --}}
            <td class="addr-cell">
                <div class="addr-header">Adresse de Facturation</div>
                <div class="addr-body">
                    <strong>{{ $fournisseur?->name ?? '—' }}</strong><br>
                    @if($fournisseur?->adresse_siege)
                        {{ $fournisseur->adresse_siege }}<br>
                    @endif
                    @if($fournisseur?->code_postal || $fournisseur?->ville)
                        {{ $fournisseur->code_postal }} {{ $fournisseur->ville }}<br>
                    @endif
                    @if($fournisseur?->telephone)
                        Tél : {{ $fournisseur->telephone }}<br>
                    @endif
                    @if($fournisseur?->mail)
                        {{ $fournisseur->mail }}
                    @endif
                </div>
            </td>

            {{-- Adresse de livraison = Magasin livraison --}}
            <td class="addr-cell-right">
                <div class="addr-header">Adresse de Livraison</div>
                <div class="addr-body">
                    @if($magasin)
                        <strong>{{ $magasin->name }}</strong><br>
                        @if($magasin->adress)
                            {{ $magasin->adress }}<br>
                        @endif
                        @if($magasin->telephone)
                            Tél : {{ $magasin->telephone }}<br>
                        @endif
                        @if($magasin->email)
                            {{ $magasin->email }}
                        @endif
                    @else
                        <span style="color:#aaa;font-style:italic;">Non renseigné</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════════
         TABLEAU DES LIGNES
         ══════════════════════════════════════════════════ --}}
    <div class="lines-outer">
        <table class="lines-table">
            <thead>
            <tr>
                <th class="th-center" style="width:10%">Quantité</th>
                <th style="width:22%">Produit</th>
                <th style="width:28%">Description</th>
                <th class="th-right" style="width:15%">Prix unitaire</th>
                <th class="th-right" style="width:15%;border-right:none;">Total Ligne</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($lignes as $ligne)
                <tr>
                    <td class="td-center">{{ number_format($ligne['qte'], 2, ',', ' ') }}</td>
                    <td>{{ $ligne['designation'] }}</td>
                    <td>
                        {{ $ligne['designation'] }}
                        @if($ligne['article'])
                            <span style="color:#888;"> — {{ $ligne['article'] }}</span>
                        @endif
                        @if($ligne['tva'] > 0)
                            <div style="font-size:6.5px;color:#aaa;margin-top:1px;">
                                TVA {{ number_format($ligne['tva'], 2) }}%
                            </div>
                        @endif
                    </td>
                    <td class="td-right">
                        {{ number_format($ligne['pu_ht'], 2, ',', ' ') }} EUR
                    </td>
                    <td class="td-right" style="border-right:none;">
                        {{ number_format($ligne['montant_net_ht'], 2, ',', ' ') }} EUR
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5"
                        style="text-align:center;padding:18px;color:#aaa;font-style:italic;">
                        Aucune ligne de facturation.
                    </td>
                </tr>
            @endforelse

            {{-- Lignes vides pour remplissage visuel --}}
            @for ($i = 0; $i < max(0, 5 - count($lignes)); $i++)
                <tr>
                    <td style="height:22px;border-bottom:1px solid #e8eef5;" colspan="5"></td>
                </tr>
            @endfor
            </tbody>
        </table>

        {{-- Totaux --}}
        <table class="totaux-table">
            <tr>
                <td class="totaux-spacer" style="height:1px;"></td>
                <td class="totaux-right">
                    <table class="totaux-inner">
                        <tr>
                            <td class="ti-label">Sous-total</td>
                            <td class="ti-value">{{ number_format($total_net_ht, 2, ',', ' ') }} EUR</td>
                        </tr>
                        <tr>
                            <td class="ti-label">Impôt / TVA</td>
                            <td class="ti-value">{{ number_format($total_tva, 2, ',', ' ') }} EUR</td>
                        </tr>
                        <tr>
                            <td class="ti-label">Frais</td>
                            <td class="ti-value">0,00 EUR</td>
                        </tr>
                        <tr>
                            <td class="ti-label">Remise</td>
                            <td class="ti-value">
                                {{ $total_remise > 0
                                    ? '- '.number_format($total_remise, 2, ',', ' ').' EUR'
                                    : '0,00 EUR' }}
                            </td>
                        </tr>
                        <tr class="ti-total">
                            <td class="ti-label" style="color:#1a3a5c;">Total</td>
                            <td class="ti-value" style="color:#1a3a5c;font-weight:bold;">
                                {{ number_format($total_ttc, 2, ',', ' ') }} EUR
                            </td>
                        </tr>
                        <tr>
                            <td class="ti-label">Paiements</td>
                            <td class="ti-value">0,00 EUR</td>
                        </tr>
                        <tr class="ti-solde">
                            <td class="ti-label" style="color:#fff;">Solde</td>
                            <td class="ti-value" style="color:#fff;">
                                {{ number_format($total_ttc, 2, ',', ' ') }} EUR
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- ══════════════════════════════════════════════════
         PIED DE PAGE
         ══════════════════════════════════════════════════ --}}
    <div class="footer-box">
        <div class="footer-line">
            Tous les règlements à l'ordre de
            <span class="footer-bold">
                {{ $magasinEmetteur?->name ?? 'COSMA PARFUMERIES' }}
            </span>
        </div>
        <div class="footer-line footer-bold">
            Nous vous remercions pour votre confiance !
        </div>
    </div>

</div>
</body>
</html>
