<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture {{ $facture->numero ?? $facture->id }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        @font-face {
            font-family: 'Roboto Condensed';
            font-style: normal;
            font-weight: 400;
            src: url('{{ public_path('fonts/roboto-condensed/RobotoCondensed-Regular.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Roboto Condensed';
            font-style: normal;
            font-weight: 700;
            src: url('{{ public_path('fonts/roboto-condensed/RobotoCondensed-Bold.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Roboto Condensed';
            font-style: italic;
            font-weight: 400;
            src: url('{{ public_path('fonts/roboto-condensed/RobotoCondensed-Italic.ttf') }}') format('truetype');
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto Condensed', Arial, Helvetica, sans-serif;
            font-size: 14px;
            color: #323232;
            background: #ffffff;
            line-height: 1.5;
        }

        /* ══════════════════════════════════════
           PAGE WRAPPER  (carte avec ombre)
        ══════════════════════════════════════ */
        .page {
            padding: 20px 24px 24px 24px;
            background: #fff;
            box-shadow: 0 0 10px #ddd;
            border-radius: 10px;
        }

        /* ══════════════════════════════════════
           TITRE CENTRÉ
        ══════════════════════════════════════ */
        .invoice-title {
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* ══════════════════════════════════════
           EN-TÊTE : émetteur (gauche) | client (droite)
           Encadré par une bordure légère
        ══════════════════════════════════════ */
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: 1px solid #ddd;
            padding: 10px 14px;
            vertical-align: top;
        }

        /* Bloc émetteur — centré comme Deerika */
        .emitter-cell {
            width: 50%;
            text-align: center;
            line-height: 1.6;
            color: #4a4a4a;
            font-size: 8.5px;
        }

        .company-name {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .company-details {
            font-size: 8px;
            color: #555;
            line-height: 1.7;
        }

        .company-details strong {
            color: #222;
        }

        .company-details a {
            color: #00bb07;
            text-decoration: none;
        }

        /* Bloc client */
        .client-cell {
            width: 50%;
            text-align: right;
            font-size: 8.5px;
            color: #323232;
            line-height: 1.6;
        }

        .client-cell h4 {
            font-size: 10px;
            margin-bottom: 5px;
            color: #1a1a1a;
        }

        /* ══════════════════════════════════════
           SHIPMENT / BARRE DE RÉFÉRENCES
        ══════════════════════════════════════ */
        .ref-section {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .ref-section td,
        .ref-section th {
            border: 1px solid #ddd;
            padding: 8px 10px;
            font-size: 8px;
            vertical-align: top;
        }

        .ref-banner {
            background: #fcbd021f;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .ref-banner h3 {
            font-size: 11px;
            color: #1a1a1a;
            margin-bottom: 2px;
        }

        .ref-banner h3 span {
            font-weight: 300;
            font-size: 9px;
            color: #626262;
            margin-left: 6px;
        }

        .ref-banner p {
            font-weight: 300;
            font-size: 8px;
            color: #626262;
            margin-top: 3px;
        }

        .ref-banner p span.green {
            color: #00bb07;
        }

        .ref-meta {
            font-size: 8px;
            color: #444;
            line-height: 1.7;
        }

        .ref-seller {
            font-size: 8px;
            color: #444;
            line-height: 1.6;
        }

        .ref-seller h4 {
            font-size: 9px;
            color: #1a1a1a;
            margin-bottom: 3px;
        }

        /* ══════════════════════════════════════
           TABLEAU DES LIGNES
        ══════════════════════════════════════ */
        .lines-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            font-size: 8px;
        }

        .lines-table thead th {
            border: 1px solid #ddd;
            padding: 8px 6px;
            font-size: 7.5px;
            font-weight: 700;
            text-align: left;
            color: #1a1a1a;
            background: #fff;
        }

        .lines-table thead th h4 {
            font-size: 7.5px;
            font-weight: 700;
            margin: 0;
        }

        .lines-table thead th.center { text-align: center; }
        .lines-table thead th.right  { text-align: right; }

        .lines-table tbody td {
            border: 1px solid #ddd;
            padding: 8px 6px;
            font-size: 8px;
            vertical-align: middle;
            color: #323232;
        }

        .lines-table tbody td.center { text-align: center; }
        .lines-table tbody td.right  { text-align: right; }

        .designation-main { font-weight: 600; color: #1a1a1a; }
        .designation-sub  { font-size: 7px; color: #888; margin-top: 1px; }

        /* ══════════════════════════════════════
           TOTAUX INTERMÉDIAIRES (style hm-p)
        ══════════════════════════════════════ */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #ddd;
            padding: 5px 8px;
            font-size: 8.5px;
            text-align: left;
            vertical-align: top;
        }

        .summary-table th {
            width: 55%;
            font-weight: 600;
            color: #444;
        }

        .summary-table td {
            color: #000;
            font-weight: 700;
        }

        .summary-table th span.hint {
            font-size: 7.5px;
            font-weight: 300;
            color: rgb(87, 87, 87);
        }

        .summary-table .row-green td {
            color: #00bb07;
        }

        .summary-table .row-red td {
            color: #c0392b;
        }

        /* ══════════════════════════════════════
           BLOC PAIEMENT + TOTAL FINAL
        ══════════════════════════════════════ */
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .payment-table th,
        .payment-table td {
            border: 1px solid #ddd;
            padding: 5px 8px;
            font-size: 8.5px;
        }

        .payment-table th { font-weight: 600; color: #444; }
        .payment-table td { color: #000; }

        .row-total-final {
            background: #fcbd02;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .row-total-final th,
        .row-total-final td {
            font-weight: 700;
            font-size: 9.5px;
            color: #1a1a1a !important;
        }

        /* ══════════════════════════════════════
           RÉCAP TVA (gauche) + TOTAUX HT (droite)
        ══════════════════════════════════════ */
        .totaux-wrapper {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .totaux-left  { width: 50%; vertical-align: top; padding-right: 10px; }
        .totaux-right { width: 50%; vertical-align: top; padding-left: 10px; }

        .tva-recap-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .tva-recap-table thead th {
            border: 1px solid #ddd;
            padding: 6px;
            font-size: 7.5px;
            font-weight: 700;
            text-align: center;
            color: #444;
            background: #fcbd021f;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .tva-recap-table tbody td {
            border: 1px solid #ddd;
            padding: 5px 6px;
            text-align: center;
            color: #333;
        }

        /* ══════════════════════════════════════
           SECTION BANCAIRE
        ══════════════════════════════════════ */
        .bank-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 8px;
        }

        .bank-table th,
        .bank-table td {
            border: 1px solid #ddd;
            padding: 5px 8px;
        }

        .bank-header-row th {
            background: #fcbd021f;
            font-weight: 700;
            font-size: 8.5px;
            color: #1a1a1a;
            text-align: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .bank-table td { color: #333; font-weight: 600; }
        .bank-table th.label { color: #888; font-weight: 700; font-size: 7.5px; }

        /* ══════════════════════════════════════
           ARRÊTÉ EN LETTRES
        ══════════════════════════════════════ */
        .arrete {
            margin-top: 14px;
            border-left: 3px solid #fcbd02;
            padding: 6px 10px;
            background: #fffdf0;
            font-size: 8px;
        }

        .arrete .label {
            font-size: 7px;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 2px;
        }

        .arrete .text {
            font-style: italic;
            font-weight: 600;
            color: #1a1a1a;
        }

        /* ══════════════════════════════════════
           FOOTER
        ══════════════════════════════════════ */
        .footer-note {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .footer-note td {
            font-size: 7.5px;
            color: #555;
            padding: 4px 0;
            vertical-align: top;
        }

        .footer-note td.right {
            text-align: right;
        }

        .footer-note h4 {
            font-size: 8px;
            margin-bottom: 3px;
            color: #1a1a1a;
        }

        /* Signature box */
        .signature-box {
            border: 1px solid #ccc;
            padding: 8px 12px;
            min-height: 50px;
            font-size: 7px;
            color: #999;
            text-align: center;
        }

        .signature-label {
            font-size: 7px;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
        }

        /* ── WATERMARK STATUT ── */
        .status-stamp {
            position: absolute;
            top: 120px;
            right: 40px;
            font-size: 32px;
            font-weight: 700;
            color: rgba(252, 189, 2, 0.15);
            border: 4px solid rgba(252, 189, 2, 0.15);
            padding: 4px 10px;
            transform: rotate(-20deg);
            letter-spacing: 3px;
            text-transform: uppercase;
            pointer-events: none;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ══════════════════════════════════════════════
         TITRE
    ══════════════════════════════════════════════ --}}
    <div class="invoice-title">Facture</div>

    {{-- ══════════════════════════════════════════════
         EN-TÊTE : Émetteur (gauche) | Client (droite)
    ══════════════════════════════════════════════ --}}
    <table class="header-table">
        <tr>
            {{-- Émetteur --}}
            <td class="emitter-cell">
                <div class="company-name">
                    {{ $magasinEmetteur?->nom ?? config('app.name', 'Mon Entreprise') }}
                </div>
                <div class="company-details">
                    @if($magasinEmetteur?->adresse)
                        {{ $magasinEmetteur->adresse }}<br>
                    @endif
                    @if($magasinEmetteur?->code_postal || $magasinEmetteur?->ville)
                        {{ $magasinEmetteur?->code_postal }} {{ $magasinEmetteur?->ville }}<br>
                    @endif
                    @if($magasinEmetteur?->siret)
                        <strong>SIRET :</strong> {{ $magasinEmetteur->siret }}<br>
                    @endif
                    @if($magasinEmetteur?->num_tva)
                        <strong>TVA :</strong> {{ $magasinEmetteur->num_tva }}<br>
                    @endif
                    @if($magasinEmetteur?->telephone)
                        Tél : <a href="tel:{{ $magasinEmetteur->telephone }}">{{ $magasinEmetteur->telephone }}</a><br>
                    @endif
                    @if($magasinEmetteur?->email)
                        <a href="mailto:{{ $magasinEmetteur->email }}">{{ $magasinEmetteur->email }}</a>
                    @endif
                </div>
            </td>

            {{-- Client --}}
            <td class="client-cell">
                <h4>Facturé à / Livré à</h4>
                <p style="font-size:8.5px;">
                    <strong>{{ $fournisseur?->name ?? $fournisseur?->raison_social ?? '—' }}</strong><br>
                    @if($fournisseur?->adresse_siege)
                        {{ $fournisseur->adresse_siege }}<br>
                    @endif
                    @if($fournisseur?->code_postal || $fournisseur?->ville)
                        {{ $fournisseur?->code_postal }} {{ $fournisseur?->ville }}<br>
                    @endif
                    @if($fournisseur?->telephone)
                        Tél : <a href="tel:{{ $fournisseur->telephone }}" style="color:#00bb07;">{{ $fournisseur->telephone }}</a><br>
                    @endif
                    @if($fournisseur?->siret || $fournisseur?->num_tva)
                        SIRET {{ $fournisseur?->siret }} / TVA {{ $fournisseur?->num_tva }}<br>
                    @endif
                    @if($facture->ref_client ?? false)
                        Réf. Client : {{ $facture->ref_client }}
                    @endif
                </p>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════
         BARRE DE RÉFÉRENCES (style shipment banner)
    ══════════════════════════════════════════════ --}}
    <table class="ref-section">
        <tr class="ref-banner">
            <td colspan="4">
                <h3>
                    Facture
                    <span>N° {{ $facture->numero ?? str_pad($facture->id, 7, '0', STR_PAD_LEFT) }}</span>
                </h3>
                <p>
                    Date :
                    <span class="green">
                        {{ $facture->date_facture
                            ? \Carbon\Carbon::parse($facture->date_facture)->format('d/m/Y')
                            : $date_impression }}
                    </span>
                    @if($facture->date_echeance)
                        &nbsp;|&nbsp; Échéance :
                        <span class="green">
                            {{ \Carbon\Carbon::parse($facture->date_echeance)->format('d/m/Y') }}
                        </span>
                    @endif
                </p>
            </td>
            <td colspan="4" class="ref-meta">
                <p>N° Facture : {{ $facture->numero ?? $facture->id }}</p>
                <p style="margin:3px 0">Imprimée le : {{ $date_impression }}</p>
                @if($facture->statut)
                    <p>Statut : {{ ucfirst($facture->statut) }}</p>
                @endif
                @if($facture->mode_paiement)
                    <p>Mode de paiement : {{ $facture->mode_paiement }}</p>
                @endif
            </td>
            <td colspan="4" class="ref-seller">
                <h4>Émis par :</h4>
                <p>
                    {{ $magasinEmetteur?->nom ?? config('app.name') }}<br>
                    @if($magasinEmetteur?->adresse)
                        {{ $magasinEmetteur->adresse }},
                        {{ $magasinEmetteur?->code_postal }} {{ $magasinEmetteur?->ville }}
                    @endif
                </p>
                @if($commande?->numero_commande)
                    <p style="margin-top:3px;">Bon de commande : {{ $commande->numero_commande }}</p>
                @endif
            </td>
        </tr>

        {{-- En-têtes colonnes --}}
        <tr>
            <th style="width:30px;">#</th>
            <th style="width:120px;"><h4>Désignation</h4></th>
            <th style="width:60px;"><h4>Réf.</h4></th>
            <th style="width:50px;"><h4>Unité</h4></th>
            <th style="width:40px;"><h4>Qté</h4></th>
            <th style="width:70px;"><h4>P.U. HT</h4></th>
            <th style="width:70px;"><h4>Montant HT</h4></th>
            <th style="width:50px;"><h4>TVA %</h4></th>
            <th style="width:70px;"><h4>Montant TVA</h4></th>
            <th style="width:60px;"><h4>Remise</h4></th>
            <th style="width:80px;"><h4>TOTAL TTC</h4></th>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════
         LIGNES DE FACTURE
    ══════════════════════════════════════════════ --}}
    <table class="lines-table">
        <tbody>
        @forelse($lignes as $index => $ligne)
            <tr>
                <td style="width:30px; text-align:center;">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                <td style="width:120px;">
                    <div class="designation-main">{{ $ligne['designation'] }}</div>
                </td>
                <td style="width:60px; color:#888; font-size:7.5px;">
                    {{ $ligne['article'] ?? '—' }}
                </td>
                <td class="center" style="width:50px;">{{ $ligne['unite'] ?? 'U' }}</td>
                <td class="center" style="width:40px;">{{ number_format($ligne['qte'], 0, ',', ' ') }}</td>
                <td class="right"  style="width:70px;">{{ number_format($ligne['pu_ht'], 2, ',', ' ') }}</td>
                <td class="right"  style="width:70px;">{{ number_format($ligne['montant_ht'], 2, ',', ' ') }}</td>
                <td class="center" style="width:50px;">{{ number_format($ligne['tva'], 0) }}%</td>
                <td class="right"  style="width:70px;">
                    {{ number_format($ligne['montant_ht'] * ($ligne['tva'] / 100), 2, ',', ' ') }}
                </td>
                <td class="center" style="width:60px; color:#c0392b;">
                    @if($ligne['taux_remise'] > 0)
                        {{ number_format($ligne['taux_remise'], 2, ',', ' ') }}%
                    @else —
                    @endif
                </td>
                <td class="right"  style="width:80px; font-weight:700;">
                    {{ number_format($ligne['montant_ttc'], 2, ',', ' ') }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="11" style="text-align:center; padding:16px; color:#aaa; font-style:italic;">
                    Aucune ligne de facture.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    {{-- ══════════════════════════════════════════════
         RÉCAP TVA + TOTAUX (côte à côte)
    ══════════════════════════════════════════════ --}}
    <table class="totaux-wrapper">
        <tr>
            {{-- Récap TVA --}}
            <td class="totaux-left">
                @php
                    $tvaGroupes = $lignes->groupBy('tva')->map(function($group) {
                        $baseHT     = $group->sum('montant_net_ht');
                        $tva        = $group->first()['tva'];
                        $montantTVA = $baseHT * ($tva / 100);
                        return ['taux' => $tva, 'base_ht' => $baseHT, 'montant_tva' => $montantTVA];
                    });
                @endphp
                <table class="tva-recap-table">
                    <thead>
                    <tr>
                        <th>Taux TVA</th>
                        <th>Base HT</th>
                        <th>Montant TVA</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($tvaGroupes as $recap)
                        <tr>
                            <td>{{ number_format($recap['taux'], 0) }}%</td>
                            <td>{{ number_format($recap['base_ht'], 2, ',', ' ') }} €</td>
                            <td>{{ number_format($recap['montant_tva'], 2, ',', ' ') }} €</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                @if($facture->notes || $facture->remarques)
                    <div style="margin-top:10px; font-size:7.5px; color:#555; border-left:2px solid #fcbd02; padding-left:8px;">
                        <div style="font-weight:700; color:#888; font-size:7px; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:3px;">Notes</div>
                        {{ $facture->notes ?? $facture->remarques }}
                    </div>
                @endif
            </td>

            {{-- Totaux --}}
            <td class="totaux-right">
                <table class="summary-table">
                    @if(isset($facture->membership_cashback) && $facture->membership_cashback > 0)
                        <tr>
                            <th>Cashback adhérent <span class="hint">(crédité sous 48h)</span></th>
                            <td>{{ number_format($facture->membership_cashback, 2, ',', ' ') }} €</td>
                        </tr>
                    @endif
                    @if($total_remise > 0)
                        <tr class="row-red">
                            <th>Remise totale</th>
                            <td>– {{ number_format($total_remise, 2, ',', ' ') }} €</td>
                        </tr>
                    @endif
                    <tr>
                        <th>Total brut HT</th>
                        <td>{{ number_format($total_ht, 2, ',', ' ') }} €</td>
                    </tr>
                    <tr>
                        <th>Total net HT</th>
                        <td>{{ number_format($total_net_ht, 2, ',', ' ') }} €</td>
                    </tr>
                    <tr>
                        <th>Total TVA</th>
                        <td>{{ number_format($total_tva, 2, ',', ' ') }} €</td>
                    </tr>
                </table>

                {{-- Bloc paiement + total final --}}
                <table class="payment-table" style="margin-top:4px;">
                    @if($facture->mode_paiement)
                        <tr>
                            <th style="width:55%;">
                                <p>Mode de paiement :</p>
                                @if(isset($facture->conditions_paiement))
                                    <p>Conditions :</p>
                                @endif
                                @if(isset($acompte) && $acompte > 0)
                                    <p>Acompte versé :</p>
                                @endif
                            </th>
                            <td style="width:45%; text-align:right; border-right:none; vertical-align:top;">
                                <p>&nbsp;</p>
                                <p><strong>{{ strtoupper($facture->mode_paiement) }}</strong></p>
                                @if(isset($facture->conditions_paiement))
                                    <p><strong>{{ $facture->conditions_paiement }}</strong></p>
                                @endif
                                @if(isset($acompte) && $acompte > 0)
                                    <p><strong>{{ number_format($acompte, 2, ',', ' ') }} €</strong></p>
                                @endif
                            </td>
                        </tr>
                    @endif
                    <tr class="row-total-final">
                        <th>Total TTC</th>
                        <td style="text-align:right;"><strong>{{ number_format($total_ttc, 2, ',', ' ') }} €</strong></td>
                    </tr>
                    @if(isset($acompte) && $acompte > 0)
                        <tr>
                            <th style="font-weight:700; color:#1a1a1a;">NET À PAYER</th>
                            <td style="text-align:right; font-weight:700; color:#1a1a1a;">
                                {{ number_format($total_ttc - $acompte, 2, ',', ' ') }} €
                            </td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════
         COORDONNÉES BANCAIRES
    ══════════════════════════════════════════════ --}}
    @if($magasinEmetteur?->iban)
        <table class="bank-table">
            <tr class="bank-header-row">
                <th colspan="6">Virement au compte :</th>
            </tr>
            @if($magasinEmetteur?->banque_domiciliation)
                <tr>
                    <td colspan="6" style="text-align:center; color:#555;">
                        Domiciliation : {{ $magasinEmetteur->banque_domiciliation }}
                    </td>
                </tr>
            @endif
            <tr>
                <th class="label">IBAN :</th>
                <td colspan="3">{{ $magasinEmetteur?->iban ?? '—' }}</td>
                <th class="label">BIC :</th>
                <td>{{ $magasinEmetteur?->bic ?? '—' }}</td>
            </tr>
            <tr>
                <th class="label">Banque</th>
                <th class="label">Guichet</th>
                <th class="label">Compte</th>
                <th class="label">Clé RIB</th>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td>{{ $magasinEmetteur?->banque_code ?? '—' }}</td>
                <td>{{ $magasinEmetteur?->banque_guichet ?? '—' }}</td>
                <td>{{ $magasinEmetteur?->banque_compte ?? '—' }}</td>
                <td>{{ $magasinEmetteur?->banque_cle ?? '—' }}</td>
                <td colspan="2"></td>
            </tr>
        </table>
    @endif

    {{-- ══════════════════════════════════════════════
         ARRÊTÉ EN LETTRES
    ══════════════════════════════════════════════ --}}
    <div class="arrete">
        <div class="label">Arrêtée la présente facture à la somme de</div>
        <div class="text">{{ number_format($total_ttc, 2, ',', ' ') }} Euros TTC</div>
    </div>

    {{-- ══════════════════════════════════════════════
         PIED DE PAGE
    ══════════════════════════════════════════════ --}}
    <table class="footer-note">
        <tr>
            <td style="width:60%;">
                <h4>{{ $magasinEmetteur?->nom ?? config('app.name') }}</h4>
                <p>La taxe est payable sous le régime normal — Non.</p>
                <p>Ce document est généré automatiquement et ne nécessite pas de signature.</p>
                <p style="margin-top:4px;">Document généré le {{ $date_impression }} — Facture N° {{ $facture->numero ?? $facture->id }}</p>
                @if($facture->notes_bas ?? false)
                    <p style="margin-top:6px; font-style:italic;">{{ $facture->notes_bas }}</p>
                @else
                    <p style="margin-top:6px; font-style:italic;">
                        En cas de retard de paiement, pénalités de retard de 4% applicables. Indemnité forfaitaire de recouvrement : 40€ (Art. D.441-5 C.com). Pas d'escompte pour règlement anticipé.
                    </p>
                @endif
            </td>
            <td style="width:4%;"></td>
            <td style="width:36%; vertical-align:top;">
                <div class="signature-label">Signature &amp; Cachet</div>
                <div class="signature-box">&nbsp;<br>&nbsp;<br>&nbsp;</div>
            </td>
        </tr>
    </table>

</div>
</body>
</html>
