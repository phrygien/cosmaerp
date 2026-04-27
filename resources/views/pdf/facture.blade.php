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
            font-size: 9px;
            color: #111;
            background: #fff;
        }

        /* ══════════════════════════════════
           BANDEAU TITRE (bordeaux)
           ══════════════════════════════════ */
        .header-bar {
            background-color: #811844;
            padding: 22px 28px 20px;
            width: 100%;
        }

        .header-inner {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left {
            vertical-align: top;
            width: 55%;
        }

        .header-right {
            vertical-align: top;
            text-align: right;
            width: 45%;
        }

        .header-title {
            font-size: 22px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .header-subtitle {
            font-size: 8px;
            color: #f9a8d4;
            margin-top: 4px;
        }

        .header-num-label {
            font-size: 7px;
            color: #f9a8d4;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 3px;
        }

        .header-num-value {
            font-size: 16px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 1px;
        }

        /* ══════════════════════════════════
           CORPS
           ══════════════════════════════════ */
        .body-wrap {
            padding: 20px 28px;
        }

        /* ── Labels de section ── */
        .section-label {
            display: inline-block;
            background-color: #811844;
            color: #ffffff;
            font-size: 6.5px;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 2px 8px;
            margin-bottom: 8px;
        }

        /* ── Blocs info ── */
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-col {
            width: 50%;
            vertical-align: top;
            padding-right: 16px;
        }

        .info-col-right {
            width: 50%;
            vertical-align: top;
            padding-left: 16px;
        }

        .info-box {
            border: 1px solid #d1d5db;
            padding: 10px 12px;
        }

        .fournisseur-name {
            font-size: 12px;
            font-weight: bold;
            color: #811844;
        }

        /* Tableau infos détails */
        .detail-info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-info-table tr td {
            padding: 3.5px 0;
            font-size: 8.5px;
            border: none;
        }

        .detail-info-table tr td.di-label {
            color: #6b7280;
            width: 50%;
        }

        .detail-info-table tr td.di-value {
            text-align: right;
            font-weight: 600;
            color: #111;
        }

        .badge-remise {
            display: inline-block;
            background-color: #fce7f3;
            color: #9f1239;
            font-size: 7px;
            font-weight: bold;
            padding: 1px 6px;
        }

        .badge-status {
            display: inline-block;
            background-color: #fce7f3;
            color: #9f1239;
            font-size: 7px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 2px 7px;
        }

        /* ══════════════════════════════════
           SÉPARATEUR
           ══════════════════════════════════ */
        .divider {
            border: none;
            border-top: 1.5px solid #fce7f3;
            margin: 16px 0;
        }

        /* ══════════════════════════════════
           TABLEAU LIGNES
           ══════════════════════════════════ */
        .lines-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .lines-table thead tr {
            border-bottom: 2px solid #811844;
        }

        .lines-table thead th {
            padding: 7px 6px;
            font-size: 6.5px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #811844;
            background-color: #fff5f7;
            text-align: right;
        }

        .lines-table thead th.th-left {
            text-align: left;
        }

        .lines-table tbody tr {
            border-bottom: 1px solid #f3f4f6;
        }

        .lines-table tbody td {
            padding: 8px 6px;
            font-size: 8px;
            vertical-align: top;
            text-align: right;
        }

        .lines-table tbody td.td-left {
            text-align: left;
        }

        .product-name {
            font-weight: 600;
            color: #111;
            font-size: 8.5px;
        }

        .product-tva {
            font-size: 7px;
            color: #9ca3af;
            margin-top: 1px;
        }

        .ttc-value {
            font-weight: bold;
            color: #811844;
        }

        .remise-amount {
            font-weight: 600;
            color: #811844;
        }

        .no-remise {
            color: #d1d5db;
        }

        /* ══════════════════════════════════
           TOTAUX
           ══════════════════════════════════ */
        .totaux-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .totaux-spacer {
            width: 50%;
            vertical-align: top;
        }

        .totaux-right {
            width: 50%;
            vertical-align: top;
        }

        .totaux-rows {
            width: 100%;
            border-collapse: collapse;
        }

        .totaux-rows tr td {
            padding: 4px 0;
            font-size: 8.5px;
            border-bottom: 1px solid #f3f4f6;
        }

        .t-label {
            color: #6b7280;
            text-align: left;
        }

        .t-value {
            text-align: right;
            font-weight: 600;
            color: #111;
        }

        .remise-value {
            text-align: right;
            font-weight: 600;
            color: #811844;
        }

        /* Ligne TTC */
        .ttc-row {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            background-color: #811844;
        }

        .ttc-row td {
            padding: 10px 14px;
            color: #ffffff;
        }

        .ttc-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #f9a8d4;
        }

        .ttc-amount {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            color: #ffffff;
        }

        /* Économie */
        .economie-row {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            background-color: #fce7f3;
        }

        .economie-row td {
            padding: 6px 14px;
            font-size: 8px;
            font-weight: bold;
            color: #9f1239;
        }

        .economie-value {
            text-align: right;
        }

        /* ══════════════════════════════════
           PIED DE PAGE
           ══════════════════════════════════ */
        .footer {
            border-top: 1px solid #f3f4f6;
            background-color: #fafafa;
            padding: 14px 28px;
            margin-top: 20px;
        }

        .footer-label {
            font-size: 6.5px;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #811844;
            margin-bottom: 3px;
        }

        .footer-value {
            font-size: 8.5px;
            font-weight: 600;
            color: #374151;
        }
    </style>
</head>
<body>

{{-- ══════════════════════════════════════════════════
     BANDEAU TITRE BORDEAUX
     ══════════════════════════════════════════════════ --}}
<div class="header-bar">
    <table class="header-inner">
        <tr>
            <td class="header-left">
                <div class="header-title">Facture</div>
                <div class="header-subtitle">
                    Commande : {{ $commande?->libelle ?? '—' }}
                </div>
            </td>
            <td class="header-right">
                <div class="header-num-label">N° Facture</div>
                <div class="header-num-value">{{ $facture->numero ?? 'FAC-'.$facture->id }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="body-wrap">

    {{-- ══════════════════════════════════════════════════
         FOURNISSEUR + DÉTAILS
         ══════════════════════════════════════════════════ --}}
    <table class="info-grid">
        <tr>
            {{-- Fournisseur --}}
            <td class="info-col">
                <div class="section-label">Fournisseur</div>
                <div class="info-box">
                    <div class="fournisseur-name">
                        {{ strtoupper($fournisseur?->name ?? '—') }}
                    </div>
                    @if($fournisseur?->adresse_siege)
                        <div style="font-size:7.5px;color:#6b7280;margin-top:4px;">
                            {{ $fournisseur->adresse_siege }}
                            @if($fournisseur->code_postal || $fournisseur->ville)
                                <br>{{ $fournisseur->code_postal }} {{ $fournisseur->ville }}
                            @endif
                        </div>
                    @endif
                    @if($fournisseur?->telephone)
                        <div style="font-size:7.5px;color:#6b7280;margin-top:2px;">
                            Tél : {{ $fournisseur->telephone }}
                        </div>
                    @endif
                    @if($fournisseur?->mail)
                        <div style="font-size:7.5px;color:#6b7280;margin-top:2px;">
                            {{ $fournisseur->mail }}
                        </div>
                    @endif
                </div>
            </td>

            {{-- Détails --}}
            <td class="info-col-right">
                <div class="section-label">Détails</div>
                <div class="info-box">
                    <table class="detail-info-table">
                        <tr>
                            <td class="di-label">Date commande</td>
                            <td class="di-value">
                                {{ $facture->date_commande
                                    ? \Carbon\Carbon::parse($facture->date_commande)->translatedFormat('d F Y')
                                    : ($commande?->created_at?->translatedFormat('d F Y') ?? '—') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="di-label">Date réception</td>
                            <td class="di-value">
                                {{ $facture->date_reception
                                    ? \Carbon\Carbon::parse($facture->date_reception)->translatedFormat('d F Y')
                                    : '—' }}
                            </td>
                        </tr>
                        @if($magasin)
                            <tr>
                                <td class="di-label">Livraison</td>
                                <td class="di-value">{{ strtoupper($magasin->name) }}</td>
                            </tr>
                        @endif
                        @if($commande?->remise_facture > 0)
                            <tr>
                                <td class="di-label">Remise commande</td>
                                <td class="di-value">
                                    <span class="badge-remise">{{ $commande->remise_facture }}%</span>
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td class="di-label">Statut</td>
                            <td class="di-value">
                                    <span class="badge-status">
                                        {{ $commande?->status?->label() ?? '—' }}
                                    </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <hr class="divider">

    {{-- ══════════════════════════════════════════════════
         TABLEAU DES LIGNES
         ══════════════════════════════════════════════════ --}}
    <div class="section-label">Lignes de facturation</div>

    <table class="lines-table">
        <thead>
        <tr>
            <th class="th-left" style="width:28%">Produit</th>
            <th style="width:7%">Qté</th>
            <th style="width:11%">PU HT</th>
            <th style="width:12%">Montant HT</th>
            <th style="width:10%">Remise</th>
            <th style="width:12%">HT Net</th>
            <th style="width:12%">Net TTC</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($lignes as $ligne)
            <tr>
                <td class="td-left">
                    <div class="product-name">{{ $ligne['designation'] }}</div>
                    @if($ligne['tva'] > 0)
                        <div class="product-tva">TVA : {{ number_format($ligne['tva'], 2) }}%</div>
                    @endif
                </td>
                <td>{{ $ligne['qte'] }}</td>
                <td>{{ number_format($ligne['pu_ht'], 2, ',', ' ') }} EUR</td>
                <td>{{ number_format($ligne['montant_ht'], 2, ',', ' ') }} EUR</td>
                <td>
                    @if($ligne['montant_remise'] > 0)
                        <div class="remise-amount">
                            - {{ number_format($ligne['montant_remise'], 2, ',', ' ') }} EUR
                        </div>
                        @if($ligne['taux_remise'] > 0)
                            <div><span class="badge-remise">{{ $ligne['taux_remise'] }}%</span></div>
                        @endif
                    @else
                        <span class="no-remise">—</span>
                    @endif
                </td>
                <td>{{ number_format($ligne['montant_net_ht'], 2, ',', ' ') }} EUR</td>
                <td class="ttc-value">
                    {{ number_format($ligne['montant_ttc'], 2, ',', ' ') }} EUR
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align:center;padding:14px;color:#9ca3af;font-style:italic;">
                    Aucune ligne de facturation.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <hr class="divider">

    {{-- ══════════════════════════════════════════════════
         RÉCAPITULATIF
         ══════════════════════════════════════════════════ --}}
    <div class="section-label">Récapitulatif</div>

    <table class="totaux-wrap">
        <tr>
            {{-- Espace gauche vide --}}
            <td class="totaux-spacer"></td>

            {{-- Bloc totaux --}}
            <td class="totaux-right">
                <table class="totaux-rows">
                    <tr>
                        <td class="t-label">Total HT brut</td>
                        <td class="t-value">{{ number_format($total_ht, 2, ',', ' ') }} EUR</td>
                    </tr>
                    @if($total_remise > 0)
                        <tr>
                            <td class="t-label">Remises lignes</td>
                            <td class="remise-value">- {{ number_format($total_remise, 2, ',', ' ') }} EUR</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="t-label">Total HT net</td>
                        <td class="t-value">{{ number_format($total_net_ht, 2, ',', ' ') }} EUR</td>
                    </tr>
                    @if($total_tva > 0)
                        <tr>
                            <td class="t-label">
                                TVA{{ $facture->tax ? ' ('.$facture->tax.'%)' : '' }}
                            </td>
                            <td class="t-value">{{ number_format($total_tva, 2, ',', ' ') }} EUR</td>
                        </tr>
                    @endif
                </table>

                {{-- Ligne TOTAL TTC --}}
                <table class="ttc-row">
                    <tr>
                        <td class="ttc-label">Total TTC</td>
                        <td class="ttc-amount">{{ number_format($total_ttc, 2, ',', ' ') }} EUR</td>
                    </tr>
                </table>

                {{-- Économie --}}
                @if($total_remise > 0)
                    <table class="economie-row">
                        <tr>
                            <td>💰 Économie réalisée</td>
                            <td class="economie-value">{{ number_format($total_remise, 2, ',', ' ') }} EUR</td>
                        </tr>
                    </table>
                @endif
            </td>
        </tr>
    </table>

</div>

{{-- ══════════════════════════════════════════════════
     PIED DE PAGE
     ══════════════════════════════════════════════════ --}}
@if($commande?->date_cloture || $commande?->date_reception)
    <div class="footer">
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                @if($commande?->date_cloture)
                    <td style="width:50%;vertical-align:top;">
                        <div class="footer-label">Date de clôture</div>
                        <div class="footer-value">
                            {{ $commande->date_cloture->translatedFormat('d F Y') }}
                        </div>
                    </td>
                @endif
                @if($commande?->date_reception)
                    <td style="width:50%;vertical-align:top;">
                        <div class="footer-label">Date de réception</div>
                        <div class="footer-value">
                            {{ $commande->date_reception->translatedFormat('d F Y') }}
                        </div>
                    </td>
                @endif
            </tr>
        </table>
    </div>
@endif

</body>
</html>
