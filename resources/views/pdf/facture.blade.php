<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture {{ $facture->numero ?? $facture->id }}</title>
    <style>
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
            font-family: 'Roboto Condensed', 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1a1a2e;
            background: #ffffff;
            line-height: 1.4;
        }

        /* ── PAGE WRAPPER ── */
        .page {
            padding: 20px 28px 20px 28px;
        }

        /* ── HEADER ── */
        .header {
            width: 100%;
            border-bottom: 3px solid #1a1a2e;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }

        .header-table {
            width: 100%;
        }

        .company-block {
            width: 55%;
            vertical-align: top;
        }

        .invoice-label-block {
            width: 45%;
            vertical-align: top;
            text-align: right;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a2e;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .company-tagline {
            font-size: 8px;
            color: #666;
            margin-top: 2px;
            letter-spacing: 0.5px;
        }

        .company-details {
            margin-top: 8px;
            font-size: 8px;
            color: #444;
            line-height: 1.6;
        }

        .invoice-title {
            font-size: 26px;
            font-weight: 700;
            color: #1a1a2e;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .invoice-badge {
            display: inline-block;
            background: #1a1a2e;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            padding: 4px 10px;
            margin-top: 6px;
            letter-spacing: 0.5px;
        }

        .invoice-meta {
            margin-top: 8px;
            font-size: 8px;
            color: #555;
            line-height: 1.7;
        }

        .invoice-meta strong {
            color: #1a1a2e;
        }

        /* ── PARTIES (fournisseur + livraison) ── */
        .parties {
            width: 100%;
            margin-bottom: 16px;
        }

        .party-cell {
            width: 48%;
            vertical-align: top;
            padding: 12px 14px;
        }

        .party-cell.left {
            background: #f5f5f5;
            border-left: 3px solid #1a1a2e;
        }

        .party-cell.right {
            background: #f9f9f9;
            border-left: 3px solid #aaaaaa;
            margin-left: 4%;
        }

        .party-spacer {
            width: 4%;
        }

        .party-label {
            font-size: 7px;
            font-weight: 700;
            color: #888;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .party-name {
            font-size: 11px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 3px;
        }

        .party-info {
            font-size: 8px;
            color: #555;
            line-height: 1.6;
        }

        /* ── DIVIDER LINE ── */
        .divider {
            width: 100%;
            height: 1px;
            background: #e0e0e0;
            margin: 4px 0 14px 0;
        }

        /* ── TABLE LINES ── */
        .table-wrapper {
            width: 100%;
            margin-bottom: 14px;
        }

        .lines-table {
            width: 100%;
            border-collapse: collapse;
        }

        .lines-table thead tr {
            background: #1a1a2e;
            color: #ffffff;
        }

        .lines-table thead th {
            padding: 7px 6px;
            font-size: 7.5px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .lines-table thead th.left { text-align: left; }
        .lines-table thead th.right { text-align: right; }
        .lines-table thead th.center { text-align: center; }

        .lines-table tbody tr {
            border-bottom: 1px solid #ebebeb;
        }

        .lines-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .lines-table tbody td {
            padding: 6px 6px;
            font-size: 8px;
            vertical-align: middle;
        }

        .lines-table tbody td.left { text-align: left; }
        .lines-table tbody td.right { text-align: right; }
        .lines-table tbody td.center { text-align: center; }

        .designation-main {
            font-weight: 600;
            color: #1a1a2e;
        }

        .designation-sub {
            font-size: 7px;
            color: #888;
            margin-top: 1px;
        }

        .tva-badge {
            display: inline-block;
            background: #e8e8e8;
            color: #444;
            font-size: 7px;
            padding: 1px 4px;
            border-radius: 2px;
        }

        .remise-cell {
            color: #c0392b;
            font-size: 7.5px;
        }

        /* ── TOTAUX ── */
        .totaux-wrapper {
            width: 100%;
            margin-bottom: 14px;
        }

        .totaux-left {
            width: 52%;
            vertical-align: top;
        }

        .totaux-right {
            width: 44%;
            vertical-align: top;
        }

        .totaux-spacer {
            width: 4%;
        }

        /* Récapitulatif TVA */
        .tva-recap-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
        }

        .tva-recap-table thead tr {
            background: #eeeeee;
        }

        .tva-recap-table thead th {
            padding: 5px 6px;
            font-weight: 700;
            text-align: center;
            font-size: 7px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #555;
        }

        .tva-recap-table tbody td {
            padding: 4px 6px;
            text-align: center;
            border-bottom: 1px solid #eeeeee;
            color: #333;
        }

        .tva-recap-label {
            font-size: 7px;
            font-weight: 700;
            color: #888;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        /* Bloc totaux */
        .totaux-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totaux-table tr td {
            padding: 5px 8px;
            font-size: 8.5px;
            border-bottom: 1px solid #eeeeee;
        }

        .totaux-table tr td:first-child {
            color: #666;
            font-weight: 600;
        }

        .totaux-table tr td:last-child {
            text-align: right;
            font-weight: 600;
            color: #1a1a2e;
        }

        .totaux-table .row-total-ttc td {
            background: #1a1a2e;
            color: #ffffff !important;
            font-size: 10.5px;
            font-weight: 700;
            padding: 8px 8px;
            border-bottom: none;
        }

        .totaux-table .row-total-ttc td:last-child {
            color: #ffffff !important;
        }

        .totaux-table .row-remise td {
            color: #c0392b !important;
        }

        /* ── ARRETÉ EN LETTRES ── */
        .arrete {
            width: 100%;
            background: #f5f5f5;
            border-left: 3px solid #1a1a2e;
            padding: 8px 12px;
            margin-bottom: 14px;
            font-size: 8px;
        }

        .arrete-label {
            font-size: 7px;
            font-weight: 700;
            color: #888;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .arrete-text {
            color: #1a1a2e;
            font-style: italic;
            font-weight: 600;
        }

        /* ── PIED DE PAGE ── */
        .footer {
            width: 100%;
            border-top: 2px solid #e0e0e0;
            padding-top: 10px;
            margin-top: 8px;
        }

        .footer-table {
            width: 100%;
        }

        .footer-left {
            width: 60%;
            vertical-align: top;
            font-size: 7px;
            color: #888;
            line-height: 1.6;
        }

        .footer-right {
            width: 40%;
            vertical-align: top;
            text-align: right;
        }

        .signature-box {
            border: 1px solid #cccccc;
            padding: 8px 12px;
            min-height: 50px;
            font-size: 7px;
            color: #999;
            text-align: center;
        }

        .signature-label {
            font-size: 7px;
            color: #888;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        /* ── WATERMARK STATUT ── */
        .status-stamp {
            position: absolute;
            top: 120px;
            right: 40px;
            font-size: 32px;
            font-weight: 700;
            color: rgba(192, 57, 43, 0.12);
            border: 4px solid rgba(192, 57, 43, 0.12);
            padding: 4px 10px;
            transform: rotate(-20deg);
            letter-spacing: 3px;
            text-transform: uppercase;
            pointer-events: none;
        }

        /* ── COMMANDE REF BAR ── */
        .ref-bar {
            width: 100%;
            background: #f0f0f0;
            padding: 6px 10px;
            margin-bottom: 14px;
            font-size: 7.5px;
        }

        .ref-bar-table {
            width: 100%;
        }

        .ref-item {
            padding-right: 20px;
            white-space: nowrap;
        }

        .ref-item-label {
            color: #888;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 6.5px;
            letter-spacing: 0.8px;
        }

        .ref-item-value {
            color: #1a1a2e;
            font-weight: 600;
            font-size: 8px;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ══════════════════════════════════════════════
         EN-TÊTE
    ══════════════════════════════════════════════ --}}
    <div class="header">
        <table class="header-table">
            <tr>
                {{-- Bloc émetteur --}}
                <td class="company-block">
                    <div class="company-name">
                        {{ $magasinEmetteur?->nom ?? config('app.name', 'Mon Entreprise') }}
                    </div>
                    @if($magasinEmetteur?->description)
                        <div class="company-tagline">{{ $magasinEmetteur->description }}</div>
                    @endif
                    <div class="company-details">
                        @if($magasinEmetteur?->adresse)
                            {{ $magasinEmetteur->adresse }}<br>
                        @endif
                        @if($magasinEmetteur?->ville || $magasinEmetteur?->code_postal)
                            {{ $magasinEmetteur?->code_postal }} {{ $magasinEmetteur?->ville }}<br>
                        @endif
                        @if($magasinEmetteur?->telephone)
                            Tél : {{ $magasinEmetteur->telephone }}<br>
                        @endif
                        @if($magasinEmetteur?->email)
                            {{ $magasinEmetteur->email }}<br>
                        @endif
                        @if($magasinEmetteur?->siret)
                            SIRET : {{ $magasinEmetteur->siret }}
                        @endif
                    </div>
                </td>

                {{-- Bloc titre facture --}}
                <td class="invoice-label-block">
                    <div class="invoice-title">Facture</div>
                    <div>
                        <span class="invoice-badge">
                            N° {{ $facture->numero ?? str_pad($facture->id, 6, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>
                    <div class="invoice-meta">
                        <strong>Date :</strong>
                        {{ $facture->date_facture
                            ? \Carbon\Carbon::parse($facture->date_facture)->format('d/m/Y')
                            : $date_impression }}<br>
                        @if($facture->date_echeance)
                            <strong>Échéance :</strong>
                            {{ \Carbon\Carbon::parse($facture->date_echeance)->format('d/m/Y') }}<br>
                        @endif
                        @if($commande?->numero_commande)
                            <strong>Bon de commande :</strong> {{ $commande->numero_commande }}<br>
                        @endif
                        <strong>Imprimée le :</strong> {{ $date_impression }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ══════════════════════════════════════════════
         BARRE DE RÉFÉRENCES
    ══════════════════════════════════════════════ --}}
    @if($facture->statut || $commande?->reference || $facture->mode_paiement)
        <div class="ref-bar">
            <table class="ref-bar-table">
                <tr>
                    @if($facture->statut)
                        <td class="ref-item">
                            <div class="ref-item-label">Statut</div>
                            <div class="ref-item-value">{{ ucfirst($facture->statut) }}</div>
                        </td>
                    @endif
                    @if($commande?->reference)
                        <td class="ref-item">
                            <div class="ref-item-label">Référence commande</div>
                            <div class="ref-item-value">{{ $commande->reference }}</div>
                        </td>
                    @endif
                    @if($facture->mode_paiement)
                        <td class="ref-item">
                            <div class="ref-item-label">Mode de paiement</div>
                            <div class="ref-item-value">{{ $facture->mode_paiement }}</div>
                        </td>
                    @endif
                    @if($facture->conditions_paiement)
                        <td class="ref-item">
                            <div class="ref-item-label">Conditions</div>
                            <div class="ref-item-value">{{ $facture->conditions_paiement }}</div>
                        </td>
                    @endif
                </tr>
            </table>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════
         PARTIES : FOURNISSEUR + LIVRAISON
    ══════════════════════════════════════════════ --}}
    <table class="parties">
        <tr>
            {{-- Fournisseur --}}
            <td class="party-cell left">
                <div class="party-label">Fournisseur</div>
                <div class="party-name">
                    {{ $fournisseur?->nom ?? $fournisseur?->raison_sociale ?? '—' }}
                </div>
                <div class="party-info">
                    @if($fournisseur?->adresse)
                        {{ $fournisseur->adresse }}<br>
                    @endif
                    @if($fournisseur?->ville || $fournisseur?->code_postal)
                        {{ $fournisseur?->code_postal }} {{ $fournisseur?->ville }}<br>
                    @endif
                    @if($fournisseur?->telephone)
                        Tél : {{ $fournisseur->telephone }}<br>
                    @endif
                    @if($fournisseur?->email)
                        {{ $fournisseur->email }}<br>
                    @endif
                    @if($fournisseur?->num_tva || $fournisseur?->siret)
                        @if($fournisseur?->num_tva) N° TVA : {{ $fournisseur->num_tva }}<br>@endif
                        @if($fournisseur?->siret) SIRET : {{ $fournisseur->siret }}@endif
                    @endif
                </div>
            </td>

            <td class="party-spacer"></td>

            {{-- Livraison --}}
            <td class="party-cell right">
                <div class="party-label">Livraison</div>
                <div class="party-name">
                    {{ $magasin?->nom ?? '—' }}
                </div>
                <div class="party-info">
                    @if($magasin?->adresse)
                        {{ $magasin->adresse }}<br>
                    @endif
                    @if($magasin?->ville || $magasin?->code_postal)
                        {{ $magasin?->code_postal }} {{ $magasin?->ville }}<br>
                    @endif
                    @if($magasin?->telephone)
                        Tél : {{ $magasin->telephone }}<br>
                    @endif
                    @if($magasin?->responsable)
                        Contact : {{ $magasin->responsable }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════
         LIGNES DE FACTURE
    ══════════════════════════════════════════════ --}}
    <div class="table-wrapper">
        <table class="lines-table">
            <thead>
            <tr>
                <th class="left"  style="width:30%">Désignation</th>
                <th class="center" style="width:8%">TVA</th>
                <th class="center" style="width:7%">Qté</th>
                <th class="right"  style="width:11%">P.U. HT</th>
                <th class="right"  style="width:11%">Montant HT</th>
                <th class="center" style="width:8%">Remise</th>
                <th class="right"  style="width:12%">Net HT</th>
                <th class="right"  style="width:13%">Montant TTC</th>
            </tr>
            </thead>
            <tbody>
            @forelse($lignes as $ligne)
                <tr>
                    <td class="left">
                        <div class="designation-main">{{ $ligne['designation'] }}</div>
                        @if($ligne['article'])
                            <div class="designation-sub">Réf : {{ $ligne['article'] }}</div>
                        @endif
                    </td>
                    <td class="center">
                        <span class="tva-badge">{{ number_format($ligne['tva'], 0) }}%</span>
                    </td>
                    <td class="center">{{ number_format($ligne['qte'], 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($ligne['pu_ht'], 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($ligne['montant_ht'], 2, ',', ' ') }}</td>
                    <td class="center remise-cell">
                        @if($ligne['taux_remise'] > 0)
                            {{ number_format($ligne['taux_remise'], 2, ',', ' ') }}%
                        @else
                            —
                        @endif
                    </td>
                    <td class="right">{{ number_format($ligne['montant_net_ht'], 2, ',', ' ') }}</td>
                    <td class="right" style="font-weight:600;">
                        {{ number_format($ligne['montant_ttc'], 2, ',', ' ') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center" style="padding:16px; color:#aaa; font-style:italic;">
                        Aucune ligne de facture.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- ══════════════════════════════════════════════
         TOTAUX
    ══════════════════════════════════════════════ --}}
    <table class="totaux-wrapper">
        <tr>
            {{-- Récapitulatif TVA (gauche) --}}
            <td class="totaux-left">
                <div class="tva-recap-label">Récapitulatif TVA</div>
                @php
                    $tvaGroupes = $lignes->groupBy('tva')->map(function($group) {
                        $baseHT  = $group->sum('montant_net_ht');
                        $tva     = $group->first()['tva'];
                        $montantTVA = $baseHT * ($tva / 100);
                        return [
                            'taux'        => $tva,
                            'base_ht'     => $baseHT,
                            'montant_tva' => $montantTVA,
                        ];
                    });
                @endphp
                <table class="tva-recap-table">
                    <thead>
                    <tr>
                        <th>Taux</th>
                        <th>Base HT</th>
                        <th>Montant TVA</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($tvaGroupes as $recap)
                        <tr>
                            <td>{{ number_format($recap['taux'], 0) }}%</td>
                            <td>{{ number_format($recap['base_ht'], 2, ',', ' ') }}</td>
                            <td>{{ number_format($recap['montant_tva'], 2, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                @if($facture->notes || $facture->remarques)
                    <div style="margin-top:12px; font-size:7.5px; color:#555; border-left:2px solid #ddd; padding-left:8px;">
                        <div style="font-weight:700; color:#888; font-size:7px; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:3px;">Notes</div>
                        {{ $facture->notes ?? $facture->remarques }}
                    </div>
                @endif
            </td>

            <td class="totaux-spacer"></td>

            {{-- Bloc totaux (droite) --}}
            <td class="totaux-right">
                <table class="totaux-table">
                    <tr>
                        <td>Total brut HT</td>
                        <td>{{ number_format($total_ht, 2, ',', ' ') }} €</td>
                    </tr>
                    @if($total_remise > 0)
                        <tr class="row-remise">
                            <td>Remise</td>
                            <td>– {{ number_format($total_remise, 2, ',', ' ') }} €</td>
                        </tr>
                    @endif
                    <tr>
                        <td>Total net HT</td>
                        <td>{{ number_format($total_net_ht, 2, ',', ' ') }} €</td>
                    </tr>
                    <tr>
                        <td>Total TVA</td>
                        <td>{{ number_format($total_tva, 2, ',', ' ') }} €</td>
                    </tr>
                    <tr class="row-total-ttc">
                        <td>Total TTC</td>
                        <td>{{ number_format($total_ttc, 2, ',', ' ') }} €</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════
         ARRÊTÉ EN LETTRES
    ══════════════════════════════════════════════ --}}
    <div class="arrete">
        <div class="arrete-label">Arrêtée la présente facture à la somme de</div>
        <div class="arrete-text">
            {{-- Idéalement, utilisez un helper numberToWords() ou une lib PHP --}}
            {{ number_format($total_ttc, 2, ',', ' ') }} Euros TTC
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         PIED DE PAGE
    ══════════════════════════════════════════════ --}}
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    @if($magasinEmetteur?->nom)
                        <strong>{{ $magasinEmetteur->nom }}</strong><br>
                    @endif
                    @if($magasinEmetteur?->siret)
                        SIRET : {{ $magasinEmetteur->siret }} —
                    @endif
                    @if($magasinEmetteur?->num_tva)
                        N° TVA : {{ $magasinEmetteur->num_tva }}<br>
                    @endif
                    @if($magasinEmetteur?->adresse)
                        {{ $magasinEmetteur->adresse }},
                        {{ $magasinEmetteur?->code_postal }} {{ $magasinEmetteur?->ville }}
                    @endif
                    <br><br>
                    Document généré le {{ $date_impression }} — Facture N° {{ $facture->numero ?? $facture->id }}
                </td>
                <td style="width:4%;"></td>
                <td class="totaux-right">
                    <div class="signature-label">Signature &amp; Cachet</div>
                    <div class="signature-box">
                        &nbsp;<br>&nbsp;<br>&nbsp;
                    </div>
                </td>
            </tr>
        </table>
    </div>

</div>
</body>
</html>
