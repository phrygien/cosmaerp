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
            color: #222;
            background: #fff;
            padding: 28px 32px;
        }

        /* ══════════════════════════════════
           EN-TÊTE : LOGO + ÉMETTEUR
           ══════════════════════════════════ */
        .top-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .top-logo-cell {
            width: 38%;
            vertical-align: top;
        }

        .logo-box {
            border: 1px solid #ccc;
            padding: 10px 14px;
            display: inline-block;
            min-width: 120px;
        }

        .logo-company {
            font-size: 13px;
            font-weight: bold;
            color: #444;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .logo-tagline {
            font-size: 6.5px;
            color: #888;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .top-emetteur-cell {
            width: 62%;
            vertical-align: top;
            text-align: right;
        }

        .emetteur-name {
            font-size: 11px;
            font-weight: bold;
            color: #222;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .emetteur-info {
            font-size: 7.5px;
            color: #555;
            line-height: 1.7;
            margin-top: 3px;
        }

        /* ══════════════════════════════════
           BLOC : DESTINATAIRE + N° FACTURE
           ══════════════════════════════════ */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .meta-left {
            width: 55%;
            vertical-align: top;
        }

        .meta-right {
            width: 45%;
            vertical-align: top;
            text-align: right;
        }

        .destinataire-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .destinataire-row {
            width: 100%;
            border-collapse: collapse;
        }

        .destinataire-row td {
            font-size: 8px;
            padding: 1.5px 0;
            vertical-align: top;
        }

        .dest-label {
            font-weight: bold;
            width: 30%;
            color: #444;
        }

        .dest-value {
            color: #222;
        }

        /* N° Facture + Date */
        .fac-num-label {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #444;
            margin-bottom: 2px;
        }

        .fac-num-value {
            font-size: 22px;
            font-weight: bold;
            color: #c0392b;
            letter-spacing: 1px;
        }

        .fac-date-row {
            font-size: 8px;
            color: #444;
            margin-top: 6px;
            line-height: 1.8;
        }

        .fac-date-label {
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .fac-payable {
            font-size: 7.5px;
            color: #888;
            font-style: italic;
            margin-top: 6px;
        }

        /* ══════════════════════════════════
           TITRE CENTRAL "FACTURE"
           ══════════════════════════════════ */
        .doc-title-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .doc-title-center {
            text-align: center;
        }

        .doc-title {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: #222;
        }

        .doc-page {
            font-size: 7.5px;
            color: #888;
            text-align: right;
            vertical-align: bottom;
        }

        /* Barre orange sous le titre */
        .orange-bar {
            height: 2.5px;
            background-color: #e8813a;
            margin-bottom: 10px;
        }

        /* ══════════════════════════════════
           TABLEAU LIGNES
           ══════════════════════════════════ */
        .lines-table {
            width: 100%;
            border-collapse: collapse;
            border: 0.8px solid #ccc;
        }

        .lines-table thead tr th {
            background-color: #fff;
            color: #222;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 8px;
            border-bottom: 0.8px solid #ccc;
            border-right: 0.5px solid #ddd;
            text-align: left;
        }

        .lines-table thead tr th.th-right {
            text-align: right;
        }

        .lines-table tbody tr td {
            font-size: 8px;
            padding: 7px 8px;
            vertical-align: top;
            border-bottom: 0.5px solid #eee;
            border-right: 0.5px solid #eee;
            color: #333;
        }

        .lines-table tbody tr td.td-right {
            text-align: right;
        }

        /* Zone note / description libre */
        .note-row td {
            padding: 8px;
            font-size: 7.5px;
            color: #666;
            font-style: italic;
            border-bottom: 0.5px solid #eee;
        }

        /* Ligne sous-total */
        .subtotal-row td {
            padding: 6px 8px;
            font-size: 8px;
            border-top: 0.8px solid #ccc;
            border-bottom: none;
        }

        .subtotal-label {
            color: #444;
        }

        .subtotal-value {
            text-align: right;
            font-weight: 600;
        }

        /* Lignes TVA */
        .tva-row td {
            padding: 3px 8px;
            font-size: 8px;
            color: #555;
            border-bottom: none;
        }

        .tva-value {
            text-align: right;
        }

        /* ══════════════════════════════════
           BAS DE TABLEAU : TOTAL
           ══════════════════════════════════ */
        .total-footer {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .total-footer-left {
            width: 60%;
            vertical-align: top;
        }

        .total-footer-right {
            width: 40%;
            vertical-align: top;
            text-align: right;
        }

        .total-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #444;
        }

        .total-amount {
            font-size: 18px;
            font-weight: bold;
            color: #222;
            letter-spacing: 0.5px;
        }

        .total-currency {
            font-size: 13px;
        }

        /* ══════════════════════════════════
           COMMENTAIRES + PIED DE PAGE
           ══════════════════════════════════ */
        .comments-section {
            margin-top: 14px;
            font-size: 7.5px;
            color: #555;
        }

        .comments-label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5px;
            margin-bottom: 3px;
        }

        .footer-divider {
            border: none;
            border-top: 0.8px solid #e8813a;
            margin: 12px 0 6px;
        }

        .footer-services {
            text-align: center;
            font-size: 7px;
            color: #e8813a;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .footer-merci {
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #333;
        }

        .footer-num {
            font-size: 7px;
            color: #aaa;
            margin-top: 4px;
        }
    </style>
</head>
<body>

{{-- ══════════════════════════════════════════════════
     EN-TÊTE : MAGASIN ÉMETTEUR (logo/nom) + coordonnées
     ══════════════════════════════════════════════════ --}}
<table class="top-header">
    <tr>
        {{-- Logo / Nom magasin --}}
        <td class="top-logo-cell">
            <div class="logo-box">
                <div class="logo-company">
                    {{ strtoupper($magasinEmetteur?->name ?? 'COSMA PARFUMERIES') }}
                </div>
                <div class="logo-tagline">Approvisionnement</div>
            </div>
        </td>

        {{-- Coordonnées magasin émetteur --}}
        <td class="top-emetteur-cell">
            <div class="emetteur-name">
                {{ strtoupper($magasinEmetteur?->name ?? 'COSMA PARFUMERIES') }}
            </div>
            <div class="emetteur-info">
                @if($magasinEmetteur?->adress)
                    {{ $magasinEmetteur->adress }}<br>
                @endif
                @if($magasinEmetteur?->telephone)
                    Tél. : {{ $magasinEmetteur->telephone }}<br>
                @endif
                @if($magasinEmetteur?->email)
                    Courriel : {{ $magasinEmetteur->email }}
                @endif
            </div>
        </td>
    </tr>
</table>

{{-- ══════════════════════════════════════════════════
     DESTINATAIRE (fournisseur) + N° FACTURE / DATE
     ══════════════════════════════════════════════════ --}}
<table class="meta-table">
    <tr>
        {{-- Destinataire --}}
        <td class="meta-left">
            <div class="destinataire-title">Facture à l'intention de :</div>
            <table class="destinataire-row">
                <tr>
                    <td class="dest-label">Nom :</td>
                    <td class="dest-value">{{ $fournisseur?->name ?? '—' }}</td>
                </tr>
                @if($fournisseur?->adresse_siege)
                    <tr>
                        <td class="dest-label">Adresse :</td>
                        <td class="dest-value">
                            {{ $fournisseur->adresse_siege }}<br>
                            @if($fournisseur->code_postal || $fournisseur->ville)
                                {{ $fournisseur->code_postal }} {{ $fournisseur->ville }}
                            @endif
                        </td>
                    </tr>
                @endif
                @if($fournisseur?->telephone)
                    <tr>
                        <td class="dest-label">Téléphone :</td>
                        <td class="dest-value">{{ $fournisseur->telephone }}</td>
                    </tr>
                @endif
                @if($fournisseur?->mail)
                    <tr>
                        <td class="dest-label">Courriel :</td>
                        <td class="dest-value">{{ $fournisseur->mail }}</td>
                    </tr>
                @endif
            </table>
        </td>

        {{-- N° Facture + Date --}}
        <td class="meta-right">
            <div class="fac-num-label">Facture N° :</div>
            <div class="fac-num-value">{{ $facture->numero ?? $facture->id }}</div>

            <div class="fac-date-row">
                <span class="fac-date-label">Date :</span>
                &nbsp;
                {{ $facture->date_commande
                    ? \Carbon\Carbon::parse($facture->date_commande)->translatedFormat('d F Y')
                    : now()->translatedFormat('d F Y') }}
            </div>

            @if($magasin)
                <div class="fac-date-row">
                    <span class="fac-date-label">Livraison :</span>
                    &nbsp;{{ $magasin->name }}
                </div>
            @endif

            @if($commande?->remise_facture > 0)
                <div class="fac-date-row">
                    <span class="fac-date-label">Remise :</span>
                    &nbsp;{{ $commande->remise_facture }} %
                </div>
            @endif

            <div class="fac-payable">Facture payable sur réception. Merci.</div>
        </td>
    </tr>
</table>

{{-- ══════════════════════════════════════════════════
     TITRE CENTRAL "FACTURE" + BARRE ORANGE
     ══════════════════════════════════════════════════ --}}
<table class="doc-title-row">
    <tr>
        <td class="doc-title-center">
            <span class="doc-title">Facture</span>
        </td>
        <td class="doc-page">Page N° : &nbsp;1</td>
    </tr>
</table>

<div class="orange-bar"></div>

{{-- ══════════════════════════════════════════════════
     TABLEAU DES LIGNES
     ══════════════════════════════════════════════════ --}}
<table class="lines-table">
    <thead>
    <tr>
        <th style="width:40%">Description</th>
        <th style="width:10%">Unité</th>
        <th class="th-right" style="width:10%">Quantité</th>
        <th class="th-right" style="width:13%">Prix</th>
        <th class="th-right" style="width:14%">Montant</th>
    </tr>
    </thead>
    <tbody>

    {{-- Lignes produits --}}
    @forelse ($lignes as $ligne)
        <tr>
            <td>
                <div style="font-weight:600;color:#222;">{{ $ligne['designation'] }}</div>
                @if($ligne['article'])
                    <div style="font-size:7px;color:#888;margin-top:1px;">{{ $ligne['article'] }}</div>
                @endif
                @if($ligne['taux_remise'] > 0)
                    <div style="font-size:7px;color:#c0392b;margin-top:1px;">
                        Remise : {{ $ligne['taux_remise'] }}%
                        (- {{ number_format($ligne['montant_remise'], 2, ',', ' ') }} EUR)
                    </div>
                @endif
            </td>
            <td style="color:#666;">Unité</td>
            <td class="td-right">{{ $ligne['qte'] }}</td>
            <td class="td-right">{{ number_format($ligne['pu_ht'], 2, ',', ' ') }}</td>
            <td class="td-right" style="font-weight:600;">
                {{ number_format($ligne['montant_net_ht'], 2, ',', ' ') }}
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5" style="text-align:center;padding:16px;color:#aaa;font-style:italic;">
                Aucune ligne de facturation.
            </td>
        </tr>
    @endforelse

    {{-- Note / libellé commande --}}
    @if($commande?->libelle)
        <tr class="note-row">
            <td colspan="5">
                {{ $commande->libelle }}
            </td>
        </tr>
    @endif

    {{-- Ligne vide pour espacement --}}
    <tr>
        <td colspan="5" style="padding:18px 8px;border-bottom:0.5px solid #eee;"></td>
    </tr>

    {{-- Sous-total HT --}}
    <tr class="subtotal-row">
        <td colspan="4" class="subtotal-label">Sous-total :</td>
        <td class="subtotal-value">
            {{ number_format($total_net_ht, 2, ',', ' ') }}
        </td>
    </tr>

    {{-- Lignes TVA par taux --}}
    @if($tva_groupes->isNotEmpty())
        <tr class="tva-row">
            <td colspan="4">
                TVA
                @foreach($tva_groupes as $tvaG)
                    @ {{ number_format($tvaG['taux'], 2) }}%
                @endforeach
            </td>
            <td class="tva-value">
                @foreach($tva_groupes as $tvaG)
                    {{ number_format($tvaG['montant'], 2, ',', ' ') }}<br>
                @endforeach
            </td>
        </tr>
    @elseif($total_tva > 0)
        <tr class="tva-row">
            <td colspan="4">
                TVA{{ $facture->tax ? ' @ '.$facture->tax.'%' : '' }}
            </td>
            <td class="tva-value">{{ number_format($total_tva, 2, ',', ' ') }}</td>
        </tr>
    @endif

    </tbody>
</table>

{{-- ══════════════════════════════════════════════════
     TOTAL DE LA FACTURE
     ══════════════════════════════════════════════════ --}}
<table class="total-footer">
    <tr>
        <td class="total-footer-left">
            {{-- Commentaires --}}
            <div class="comments-section">
                <div class="comments-label">Commentaires :</div>
                @if($facture->libelle)
                    <div style="margin-top:2px;">{{ $facture->libelle }}</div>
                @endif
                @if($commande?->date_cloture)
                    <div style="margin-top:2px;">
                        Date de clôture : {{ $commande->date_cloture->translatedFormat('d F Y') }}
                    </div>
                @endif
                @if($commande?->date_reception)
                    <div style="margin-top:2px;">
                        Date de réception : {{ $commande->date_reception->translatedFormat('d F Y') }}
                    </div>
                @endif
            </div>
        </td>

        <td class="total-footer-right">
            <div class="total-label">Total de la facture :</div>
            <div class="total-amount">
                {{ number_format($total_ttc, 2, ',', ' ') }}
                <span class="total-currency">EUR</span>
            </div>

            @if($total_remise > 0)
                <div style="font-size:7.5px;color:#c0392b;margin-top:6px;">
                    Économie : {{ number_format($total_remise, 2, ',', ' ') }} EUR
                </div>
            @endif

            @if($facture->numero)
                <div class="footer-num" style="margin-top:8px;">
                    N° Facture : {{ $facture->numero }}
                </div>
            @endif
        </td>
    </tr>
</table>

{{-- ══════════════════════════════════════════════════
     PIED DE PAGE ORANGE
     ══════════════════════════════════════════════════ --}}
<hr class="footer-divider">

<div class="footer-services">
    Approvisionnement
    &nbsp;•&nbsp; Finance
    &nbsp;•&nbsp; Gestion des commandes
    &nbsp;•&nbsp; {{ strtoupper($magasinEmetteur?->name ?? 'Cosma Parfumeries') }}
</div>

<div class="footer-merci">Merci de votre confiance !</div>

</body>
</html>
