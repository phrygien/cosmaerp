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
            font-family: 'Roboto Condensed', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #333;
            background: #ffffff;
            line-height: 1.5;
        }

        .container {
            max-width: 680px;
            margin: 0 auto;
            padding: 28px 20px 28px 20px;
        }

        /* ══════════════════════════
           LOGOTYPE + TITRE
        ══════════════════════════ */
        .logotype {
            background: #000;
            color: #fff;
            width: 75px;
            height: 75px;
            line-height: 75px;
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .invoice-banner {
            background: #ffd9e8;
            border-left: 15px solid #fff;
            padding-left: 30px;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: -1px;
            height: 75px;
            line-height: 75px;
            color: #1a1a1a;
        }

        /* ══════════════════════════
           SECTION TITLE
        ══════════════════════════ */
        h3 {
            font-size: 14px;
            color: #1a1a1a;
            margin-bottom: 6px;
        }

        p {
            font-size: 11px;
            color: #555;
            line-height: 1.6;
        }

        /* ══════════════════════════
           DETAIL BOXES (grey bg)
        ══════════════════════════ */
        .detail-box {
            background: #eee;
            padding: 16px 20px;
            font-size: 12px;
            line-height: 1.8;
            color: #333;
        }

        .detail-box strong {
            color: #1a1a1a;
        }

        /* ══════════════════════════
           ADRESSE ICON BLOCKS
        ══════════════════════════ */
        .icon-block {
            background: #ffd9e8;
            width: 46px;
            height: 46px;
            margin-right: 10px;
            display: inline-block;
            vertical-align: top;
            text-align: center;
            line-height: 46px;
            font-size: 18px;
            color: #c0397a;
            font-weight: 700;
        }

        .addr-label {
            font-weight: 700;
            font-size: 12px;
            color: #1a1a1a;
            margin-bottom: 2px;
        }

        .addr-info {
            font-size: 11px;
            color: #555;
            line-height: 1.7;
        }

        /* ══════════════════════════
           CHECKOUT BAR
        ══════════════════════════ */
        .checkout-bar {
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }

        .checkout-bar h3 { margin-bottom: 3px; }
        .checkout-bar p  { font-size: 11px; color: #555; }

        /* ══════════════════════════
           ARTICLES SECTION
        ══════════════════════════ */
        .section-icon {
            background: #ffd9e8;
            width: 46px;
            height: 46px;
            display: inline-block;
            vertical-align: middle;
            text-align: center;
            line-height: 46px;
            font-size: 20px;
            margin-right: 8px;
            color: #c0397a;
        }

        .column-header {
            background: #eee;
            text-transform: uppercase;
            padding: 12px 14px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-right: 1px solid #ddd;
            color: #444;
        }

        .row {
            padding: 8px 14px;
            border-left: 1px solid #eee;
            border-right: 1px solid #eee;
            border-bottom: 1px solid #eee;
            font-size: 12px;
            vertical-align: middle;
        }

        .row-ref {
            color: #777;
            font-size: 10px;
        }

        .row-remise {
            color: #c0392b;
            font-size: 11px;
        }

        /* ══════════════════════════
           TOTAUX (grey bg)
        ══════════════════════════ */
        .totaux-bg {
            background: #eee;
            padding: 16px 20px;
        }

        .totaux-inner {
            width: 280px;
            float: right;
            border-collapse: collapse;
        }

        .totaux-inner td {
            padding: 4px 6px;
            font-size: 12px;
            color: #333;
        }

        .totaux-inner td:last-child {
            text-align: right;
            font-weight: 600;
            color: #1a1a1a;
        }

        .totaux-inner .row-grand td {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a1a;
            border-top: 2px solid #ccc;
            padding-top: 8px;
        }

        .totaux-inner .row-tva td {
            color: #777;
            font-size: 11px;
        }

        .totaux-inner .row-remise td {
            color: #c0392b;
        }

        .clearfix::after {
            content: '';
            display: table;
            clear: both;
        }

        /* ══════════════════════════
           TVA RECAP TABLE
        ══════════════════════════ */
        .tva-recap {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 16px;
        }

        .tva-recap th {
            background: #eee;
            padding: 10px 14px;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-right: 1px solid #ddd;
            color: #444;
            text-align: center;
        }

        .tva-recap td {
            padding: 7px 14px;
            border-left: 1px solid #eee;
            border-right: 1px solid #eee;
            border-bottom: 1px solid #eee;
            text-align: center;
            color: #333;
        }

        /* ══════════════════════════
           BANQUE SECTION
        ══════════════════════════ */
        .bank-section {
            background: #eee;
            padding: 16px 20px;
            font-size: 11px;
            color: #444;
            line-height: 1.8;
            margin-top: 16px;
        }

        .bank-section strong {
            color: #1a1a1a;
        }

        .bank-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .bank-grid th {
            font-size: 10px;
            font-weight: 700;
            color: #777;
            text-transform: uppercase;
            padding: 4px 10px;
            text-align: left;
        }

        .bank-grid td {
            font-size: 11px;
            font-weight: 600;
            color: #1a1a1a;
            padding: 3px 10px;
        }

        /* ══════════════════════════
           ALERT (pink)
        ══════════════════════════ */
        .alert {
            background: #ffd9e8;
            padding: 16px 20px;
            margin: 16px 0;
            line-height: 22px;
            color: #333;
            font-size: 11px;
        }

        /* ══════════════════════════
           ARRÊTÉ EN LETTRES
        ══════════════════════════ */
        .arrete {
            border-left: 4px solid #ffd9e8;
            padding: 8px 14px;
            margin: 12px 0;
            font-size: 11px;
            color: #555;
        }

        .arrete strong {
            color: #1a1a1a;
            display: block;
            margin-bottom: 2px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ══════════════════════════
           SIGNATURE
        ══════════════════════════ */
        .signature-box {
            border: 1px solid #ccc;
            padding: 10px 14px;
            min-height: 55px;
            font-size: 10px;
            color: #aaa;
            text-align: center;
            width: 200px;
            float: right;
        }

        .signature-label {
            font-size: 10px;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
            text-align: right;
        }

        /* ══════════════════════════
           SOCIAL FOOTER
        ══════════════════════════ */
        .socialmedia {
            background: #eee;
            padding: 14px 20px;
            display: inline-block;
            font-size: 11px;
            color: #555;
            margin-top: 14px;
        }

        .footer-note {
            font-size: 10px;
            color: #777;
            line-height: 1.6;
            font-style: italic;
            margin-top: 10px;
        }

        /* ── spacer ── */
        .spacer { margin-top: 16px; }
        .spacer-sm { margin-top: 10px; }
    </style>
</head>
<body>
<div class="container">

    {{-- ══════════════════════════════════════════════
         EN-TÊTE : Logo + Titre
    ══════════════════════════════════════════════ --}}
    <table width="100%">
        <tr>
            <td width="75px">
                <div class="logotype">
                    {{ strtoupper(substr($magasinEmetteur?->nom ?? config('app.name', 'Co'), 0, 2)) }}
                </div>
            </td>
            <td>
                <div class="invoice-banner">Facture</div>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════
         INTRO
    ══════════════════════════════════════════════ --}}
    <div class="spacer"></div>
    <h3>Coordonnées</h3>
    <p>
        {{ $magasinEmetteur?->nom ?? config('app.name') }}
        @if($magasinEmetteur?->adresse)
            — {{ $magasinEmetteur->adresse }},
            {{ $magasinEmetteur?->code_postal }} {{ $magasinEmetteur?->ville }}
        @endif
        @if($magasinEmetteur?->siret)
            — SIRET : {{ $magasinEmetteur->siret }}
        @endif
    </p>

    {{-- ══════════════════════════════════════════════
         DÉTAILS FACTURE (2 colonnes grises)
    ══════════════════════════════════════════════ --}}
    <div class="spacer"></div>
    <table width="100%" style="border-collapse:collapse;">
        <tr>
            <td width="50%" class="detail-box" style="border-right:3px solid #fff;">
                <strong>Date :</strong>
                {{ $facture->date_facture
                    ? \Carbon\Carbon::parse($facture->date_facture)->format('d/m/Y')
                    : $date_impression }}<br>
                @if($facture->date_echeance)
                    <strong>Échéance :</strong>
                    {{ \Carbon\Carbon::parse($facture->date_echeance)->format('d/m/Y') }}<br>
                @endif
                @if($facture->mode_paiement)
                    <strong>Mode de paiement :</strong> {{ $facture->mode_paiement }}<br>
                @endif
                @if($facture->conditions_paiement)
                    <strong>Conditions :</strong> {{ $facture->conditions_paiement }}<br>
                @endif
                @if($facture->statut)
                    <strong>Statut :</strong> {{ ucfirst($facture->statut) }}
                @endif
            </td>
            <td width="50%" class="detail-box">
                <strong>N° Facture :</strong>
                {{ $facture->numero ?? str_pad($facture->id, 7, '0', STR_PAD_LEFT) }}<br>
                @if($commande?->numero_commande)
                    <strong>Réf. commande :</strong> {{ $commande->numero_commande }}<br>
                @endif
                @if($fournisseur?->email)
                    <strong>E-mail :</strong> {{ $fournisseur->email }}<br>
                @endif
                @if($fournisseur?->telephone)
                    <strong>Tél :</strong> {{ $fournisseur->telephone }}<br>
                @endif
                <strong>Imprimée le :</strong> {{ $date_impression }}
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════
         ADRESSES : Émetteur + Client
    ══════════════════════════════════════════════ --}}
    <div class="spacer"></div>
    <table width="100%">
        <tr>
            {{-- Émetteur --}}
            <td width="50%">
                <table>
                    <tr>
                        <td style="vertical-align:top; padding-right:10px;">
                            <div class="icon-block">&#9993;</div>
                        </td>
                        <td>
                            <div class="addr-label">Émetteur</div>
                            <div class="addr-info">
                                {{ $magasinEmetteur?->nom ?? config('app.name') }}<br>
                                @if($magasinEmetteur?->adresse)
                                    {{ $magasinEmetteur->adresse }}<br>
                                @endif
                                @if($magasinEmetteur?->code_postal || $magasinEmetteur?->ville)
                                    {{ $magasinEmetteur?->code_postal }} {{ $magasinEmetteur?->ville }}<br>
                                @endif
                                @if($magasinEmetteur?->telephone)
                                    Tél : {{ $magasinEmetteur->telephone }}<br>
                                @endif
                                @if($magasinEmetteur?->email)
                                    {{ $magasinEmetteur->email }}
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            {{-- Client / Livraison --}}
            <td width="50%">
                <table>
                    <tr>
                        <td style="vertical-align:top; padding-right:10px;">
                            <div class="icon-block">&#10003;</div>
                        </td>
                        <td>
                            <div class="addr-label">Fournisseur / Livraison</div>
                            <div class="addr-info">
                                {{ $fournisseur?->name ?? $fournisseur?->raison_social ?? '—' }}<br>
                                @if($fournisseur?->adresse_siege)
                                    {{ $fournisseur->adresse_siege }}<br>
                                @endif
                                @if($fournisseur?->code_postal || $fournisseur?->ville)
                                    {{ $fournisseur?->code_postal }} {{ $fournisseur?->ville }}<br>
                                @endif
                                @if($magasin?->adress)
                                    Livraison : {{ $magasin->adress }},
                                    {{ $magasin?->code_postal }} {{ $magasin?->ville }}
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════
         CHECKOUT INFO
    ══════════════════════════════════════════════ --}}
    <div class="spacer"></div>
    <div class="checkout-bar">
        <h3>Informations de facturation</h3>
        <p>
            Paiement par :
            <strong>{{ $facture->mode_paiement ?? '—' }}</strong>
            @if($facture->conditions_paiement)
                &nbsp;—&nbsp; {{ $facture->conditions_paiement }}
            @endif
        </p>
    </div>

    {{-- ══════════════════════════════════════════════
         LIGNES DE FACTURE
    ══════════════════════════════════════════════ --}}
    <div class="spacer"></div>
    <div style="overflow:hidden; margin-bottom:6px;">
        <div class="section-icon" style="float:left;">&#128722;</div>
        <h3 style="line-height:46px; display:inline-block;">Lignes de facture</h3>
    </div>

    <table width="100%" style="border-collapse:collapse; border-bottom:1px solid #eee;">
        <tr>
            <td width="35%" class="column-header">Désignation</td>
            <td width="15%" class="column-header">Réf.</td>
            <td width="10%" class="column-header">Unité</td>
            <td width="8%"  class="column-header">Qté</td>
            <td width="12%" class="column-header">P.U. HT</td>
            <td width="8%"  class="column-header">TVA</td>
            <td width="12%" class="column-header">Total HT</td>
        </tr>
        @forelse($lignes as $ligne)
            <tr>
                <td class="row">
                    <span class="row-ref">{{ $ligne['article'] ?? '' }}</span>
                    @if($ligne['article'])<br>@endif
                    <strong style="font-size:12px; color:#1a1a1a;">{{ $ligne['designation'] }}</strong>
                </td>
                <td class="row" style="color:#777; font-size:11px;">{{ $ligne['article'] ?? '—' }}</td>
                <td class="row" style="text-align:center;">{{ $ligne['unite'] ?? 'U' }}</td>
                <td class="row" style="text-align:center;">{{ number_format($ligne['qte'], 0, ',', ' ') }}</td>
                <td class="row" style="text-align:right;">
                    {{ number_format($ligne['qte'], 0) }}
                    <span style="color:#777">×</span>
                    {{ number_format($ligne['pu_ht'], 2, ',', ' ') }} €
                </td>
                <td class="row" style="text-align:center;">{{ number_format($ligne['tva'], 0) }}%</td>
                <td class="row" style="text-align:right; font-weight:600;">
                    {{ number_format($ligne['montant_net_ht'], 2, ',', ' ') }} €
                    @if($ligne['taux_remise'] > 0)
                        <br><span class="row-remise">−{{ number_format($ligne['taux_remise'], 1) }}%</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="row" style="text-align:center; color:#aaa; font-style:italic; padding:20px;">
                    Aucune ligne de facture.
                </td>
            </tr>
        @endforelse
    </table>

    {{-- ══════════════════════════════════════════════
         RÉCAP TVA
    ══════════════════════════════════════════════ --}}
    <div class="spacer-sm"></div>
    @php
        $tvaGroupes = $lignes->groupBy('tva')->map(function($group) {
            $baseHT     = $group->sum('montant_net_ht');
            $tva        = $group->first()['tva'];
            $montantTVA = $baseHT * ($tva / 100);
            return ['taux' => $tva, 'base_ht' => $baseHT, 'montant_tva' => $montantTVA];
        });
    @endphp
    <table class="tva-recap">
        <tr>
            <th>Taux TVA</th>
            <th>Base HT</th>
            <th>Montant TVA</th>
            <th>Total TTC</th>
        </tr>
        @foreach($tvaGroupes as $recap)
            <tr>
                <td>{{ number_format($recap['taux'], 0) }}%</td>
                <td>{{ number_format($recap['base_ht'], 2, ',', ' ') }} €</td>
                <td>{{ number_format($recap['montant_tva'], 2, ',', ' ') }} €</td>
                <td>{{ number_format($recap['base_ht'] + $recap['montant_tva'], 2, ',', ' ') }} €</td>
            </tr>
        @endforeach
    </table>

    {{-- ══════════════════════════════════════════════
         BLOC TOTAUX (fond gris, aligné à droite)
    ══════════════════════════════════════════════ --}}
    <div class="totaux-bg clearfix">
        <table class="totaux-inner">
            <tr>
                <td><strong>Sous-total HT :</strong></td>
                <td>{{ number_format($total_ht, 2, ',', ' ') }} €</td>
            </tr>
            @if($total_remise > 0)
                <tr class="row-remise">
                    <td><strong>Remise :</strong></td>
                    <td>– {{ number_format($total_remise, 2, ',', ' ') }} €</td>
                </tr>
            @endif
            <tr>
                <td><strong>Total net HT :</strong></td>
                <td>{{ number_format($total_net_ht, 2, ',', ' ') }} €</td>
            </tr>
            <tr class="row-tva">
                <td>TVA :</td>
                <td>{{ number_format($total_tva, 2, ',', ' ') }} €</td>
            </tr>
            @if(isset($acompte) && $acompte > 0)
                <tr class="row-tva">
                    <td>Acompte versé :</td>
                    <td>– {{ number_format($acompte, 2, ',', ' ') }} €</td>
                </tr>
            @endif
            <tr class="row-grand">
                <td><strong>Total TTC :</strong></td>
                <td>{{ number_format($total_ttc, 2, ',', ' ') }} €</td>
            </tr>
        </table>
    </div>

    {{-- ══════════════════════════════════════════════
         ARRÊTÉ EN LETTRES
    ══════════════════════════════════════════════ --}}
    <div class="arrete">
        <strong>Arrêtée la présente facture à la somme de</strong>
        {{ number_format($total_ttc, 2, ',', ' ') }} Euros TTC
    </div>

    {{-- ══════════════════════════════════════════════
         COORDONNÉES BANCAIRES
    ══════════════════════════════════════════════ --}}
    @if($magasinEmetteur?->iban)
        <div class="bank-section">
            <strong>Virement bancaire :</strong>
            @if($magasinEmetteur?->banque_domiciliation)
                Domiciliation : {{ $magasinEmetteur->banque_domiciliation }}<br>
            @endif
            <table class="bank-grid">
                <tr>
                    <th>IBAN</th>
                    <th>BIC</th>
                    <th>Banque</th>
                    <th>Guichet</th>
                    <th>Compte</th>
                    <th>Clé RIB</th>
                </tr>
                <tr>
                    <td>{{ $magasinEmetteur?->iban ?? '—' }}</td>
                    <td>{{ $magasinEmetteur?->bic ?? '—' }}</td>
                    <td>{{ $magasinEmetteur?->banque_code ?? '—' }}</td>
                    <td>{{ $magasinEmetteur?->banque_guichet ?? '—' }}</td>
                    <td>{{ $magasinEmetteur?->banque_compte ?? '—' }}</td>
                    <td>{{ $magasinEmetteur?->banque_cle ?? '—' }}</td>
                </tr>
            </table>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════
         SIGNATURE
    ══════════════════════════════════════════════ --}}
    <div class="spacer clearfix">
        <div class="signature-label">Signature &amp; Cachet</div>
        <div class="signature-box">&nbsp;<br>&nbsp;<br>&nbsp;</div>
    </div>

    {{-- ══════════════════════════════════════════════
         NOTE LÉGALE (alert rose)
    ══════════════════════════════════════════════ --}}
    <div class="alert">
        @if($facture->notes ?? false)
            {{ $facture->notes }}
        @else
            En cas de retard de paiement, application d'une indemnité forfaitaire pour frais de recouvrement de 40 € selon l'article D. 441-5 du code du commerce. Taux des pénalités de retard : 4 %. Pas d'escompte pour règlement anticipé.
        @endif
    </div>

    {{-- ══════════════════════════════════════════════
         FOOTER
    ══════════════════════════════════════════════ --}}
    <div class="socialmedia">
        Document généré le {{ $date_impression }} — Facture N°&nbsp;{{ $facture->numero ?? $facture->id }}
    </div>
    <div class="footer-note" style="margin-top:8px;">
        @if($magasinEmetteur?->siret)
            SIRET : {{ $magasinEmetteur->siret }}
        @endif
        @if($magasinEmetteur?->num_tva)
            &nbsp;—&nbsp; TVA : {{ $magasinEmetteur->num_tva }}
        @endif
    </div>

</div>
</body>
</html>
