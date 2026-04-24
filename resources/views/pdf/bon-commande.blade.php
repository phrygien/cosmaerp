<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Bon de commande</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Josefin Sans', sans-serif;
            font-size: 12px;
            color: #111;
            background: #fff;
            padding: 18px 22px;
            letter-spacing: 0.02em;
        }

        /* ── Adresses ── */
        .top-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .address-box {
            border: 1px solid #999;
            padding: 7px 9px;
            font-size: 11px;
            line-height: 1.8;
            vertical-align: top;
        }
        .box-label {
            font-size: 9px;
            font-weight: 700;
            color: #fff;
            background: #C44545;
            display: inline-block;
            padding: 1px 6px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .destinataire-box {
            border: 1px solid #999;
            border-top: none;
            padding: 7px 9px;
            font-size: 11px;
            line-height: 1.8;
        }

        /* ── Notre N° client ── */
        .notre-num {
            font-size: 11px;
            margin: 6px 0 4px 0;
            letter-spacing: 0.04em;
        }

        /* ── Titre ── */
        .titre-wrap { text-align: center; margin: 6px 0 8px 0; }
        .titre-inner {
            display: inline-block;
            border: 2px solid #C44545;
            padding: 6px 70px;
        }
        .titre-inner h1 {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #C44545;
        }

        /* ── Représentant ── */
        .rep-section {
            font-size: 11px;
            margin-bottom: 9px;
            line-height: 2;
            letter-spacing: 0.03em;
        }

        /* ── Corps ── */
        .corps-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 9px;
            font-size: 11px;
        }
        .corps-table td { vertical-align: top; padding: 0 4px; }
        .corps-table td:first-child  { width: 38%; padding-left: 0; }
        .corps-table td:nth-child(2) { width: 27%; text-align: center; }
        .corps-table td:last-child   { width: 35%; padding-right: 0; text-align: right; }
        .corps-line { margin-bottom: 3px; line-height: 1.7; }
        .fournisseur-name {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .page-info { line-height: 2; }

        /* ── Table lignes ── */
        .lignes-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10px;
        }
        .lignes-table th {
            background: #111;
            color: #fff;
            border: 1px solid #111;
            padding: 5px 4px;
            text-align: center;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .lignes-table td {
            border: 1px solid #bbb;
            padding: 4px 3px;
            vertical-align: middle;
            line-height: 1.5;
        }
        .lignes-table tbody tr:nth-child(even) td {
            background: #f9f9f9;
        }
        .tc { text-align: center; }
        .tr { text-align: right; }

        /* ── Totaux ── */
        .totaux-table {
            width: 55%;
            margin-left: auto;
            border-collapse: collapse;
            font-size: 11px;
        }
        .totaux-table td { padding: 4px 7px; border: 1px solid #bbb; }
        .t-label    {
            font-weight: 700;
            background: #f2f2f2;
            width: 62%;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-size: 9.5px;
        }
        .t-currency { text-align: center; color: #666; width: 9%; border-left: none; font-weight: 600; }
        .t-value    { text-align: right; font-weight: 700; width: 29%; border-left: none; font-size: 11px; }

        strong { font-weight: 700; }
    </style>
</head>
<body>

@php
    $totalBrut     = $commande->details->sum(fn($d) => $d->pu_achat_HT  * $d->quantite);
    $totalNet      = $commande->details->sum(fn($d) => $d->pu_achat_net * $d->quantite);
    $montantRemise = $totalBrut - $totalNet;
    $remisePct     = $totalBrut > 0 ? round((1 - $totalNet / $totalBrut) * 100, 2) : 0;
    $nbArticles    = $commande->details->count();
    $magLiv        = $bonCommande?->magasinLivraison ?? $commande->magasinLivraison;
@endphp

{{-- ══ ADRESSES ══ --}}
<table class="top-table" cellspacing="0" cellpadding="0">
    <tr>
        {{-- Adresse de facturation (statique) --}}
        <td style="width:48%; vertical-align:top;">
            <div class="address-box">
                <span class="box-label">Adresse de facturation</span><br>
                <strong>COSMAPARFUMERIES</strong><br>
                Zone Industrielle<br>
                17 Route des Boulangers<br>
                78530 &nbsp; Buc<br>
                Tel : 06 40 18 31 12<br>
                Fax
            </div>
        </td>

        <td style="width:4%;"></td>

        {{-- Adresse de livraison (statique) + Destinataire (dynamique) --}}
        <td style="width:48%; vertical-align:top;">
            <div class="address-box">
                <span class="box-label">Adresse de livraison</span><br>
                <strong>COSMAPARFUMERIES</strong><br>
                Zone Industrielle<br>
                17 Route des Boulangers<br>
                78530 &nbsp; Buc<br>
                Tel : 06 16 23 02 12<br>
                Fax 0238603031
            </div>
            <div class="destinataire-box">
                <span class="box-label">Destinataire</span><br>
                @if($commande->fournisseur)
                    <strong>{{ $commande->fournisseur->name }}</strong><br>
                    @if($commande->fournisseur->raison_social)
                        {{ $commande->fournisseur->raison_social }}<br>
                    @endif
                    @if($commande->fournisseur->adresse_siege)
                        {{ $commande->fournisseur->adresse_siege }}<br>
                    @endif
                    @if($commande->fournisseur->code_postal || $commande->fournisseur->ville)
                        {{ $commande->fournisseur->code_postal }} &nbsp; {{ $commande->fournisseur->ville }}<br>
                    @endif
                    @if($commande->fournisseur->telephone)
                        Tel : {{ $commande->fournisseur->telephone }}<br>
                    @endif
                    @if($commande->fournisseur->fax)
                        Fax : {{ $commande->fournisseur->fax }}<br>
                    @endif
                    @if($commande->fournisseur->mail)
                        {{ $commande->fournisseur->mail }}
                    @endif
                @else
                    <span style="color:#aaa;">—</span>
                @endif
            </div>
        </td>
    </tr>
</table>

{{-- Notre N° de client --}}
<p class="notre-num">Notre N° de client :
    @if($bonCommande?->code_fournisseur) <strong>{{ $bonCommande->code_fournisseur }}</strong> @endif
</p>

{{-- ══ TITRE ══ --}}
<div class="titre-wrap">
    <div class="titre-inner"><h1>Bon de commande</h1></div>
</div>

{{-- Représentant --}}
<div class="rep-section">
    Représentant :<br>&nbsp;<br>Fax :
</div>

{{-- ══ CORPS ══ --}}
<table class="corps-table" cellspacing="0" cellpadding="0">
    <tr>
        <td>
            <div class="corps-line">
                Le {{ $bonCommande?->date_commande
                    ? \Carbon\Carbon::parse($bonCommande->date_commande)->format('d/m/Y')
                    : \Carbon\Carbon::now()->format('d/m/Y') }}
            </div>
            <div class="corps-line">
                N° de commande
                {{ $bonCommande?->numero_compte ? $bonCommande->numero_compte . ' ' : '' }}du
                {{ $bonCommande?->date_commande
                    ? \Carbon\Carbon::parse($bonCommande->date_commande)->format('d/m/Y')
                    : \Carbon\Carbon::now()->format('d/m/Y') }}
            </div>
            <div class="corps-line">Libellé : {{ $commande->libelle ?? '' }}</div>
            <div class="corps-line">Nombre de jours délais d'échéance</div>
            <div class="corps-line">Jour d'échéance</div>
        </td>
        <td>
            <div class="fournisseur-name">{{ strtoupper($commande->fournisseur?->name ?? '') }}</div>
        </td>
        <td>
            <div class="page-info">
                <div>Page : 1</div>
                <div>Magasin de livraison N°&nbsp;{{ str_pad($magLiv?->id ?? '', 4, '0', STR_PAD_LEFT) }} {{ $magLiv?->name ?? '' }}</div>
                <div style="margin-top:5px;">
                    Date de livraison &nbsp; {{ $bonCommande?->date_livraison_prevue
                        ? \Carbon\Carbon::parse($bonCommande->date_livraison_prevue)->format('d/m/Y')
                        : '___ / ___ / ______' }}
                </div>
                <div>Date de règlement &nbsp; ___ / ___</div>
            </div>
        </td>
    </tr>
</table>

{{-- ══ TABLE LIGNES ══ --}}
<table class="lignes-table">
    <thead>
    <tr>
        <th style="width:11%">Code EAN</th>
        <th style="width:23%">Désignation</th>
        <th style="width:7%">Article</th>
        <th style="width:11%">Votre référence</th>
        <th style="width:5%">Qté</th>
        <th style="width:9%">PU brut H.T.</th>
        <th style="width:5%">% rem</th>
        <th style="width:9%">Mt net H.T.</th>
        <th style="width:8%">Genecod</th>
        <th style="width:12%">EAN</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($commande->details as $detail)
        <tr>
            <td class="tc" style="font-family:monospace; font-size:7.5px;">{{ $detail->product?->EAN ?? '—' }}</td>
            <td>{{ $detail->product?->designation ?? '—' }}</td>
            <td class="tc">{{ $detail->product?->reference ?? '' }}</td>
            <td class="tc">{{ $detail->product?->ref_fournisseur ?? '' }}</td>
            <td class="tc">{{ $detail->quantite }}</td>
            <td class="tr">{{ number_format($detail->pu_achat_HT, 2, ',', ' ') }}</td>
            <td class="tc">{{ $detail->taux_remise ?? '—' }}</td>
            <td class="tr">{{ number_format($detail->pu_achat_net, 2, ',', ' ') }}</td>
            <td class="tc" style="font-family:monospace; font-size:7.5px;">{{ $detail->product?->EAN ?? '' }}</td>
            <td class="tc">
                @if($detail->product?->EAN)
                    {!! DNS1D::getBarcodeSVG(
                        $detail->product->EAN,
                        strlen($detail->product->EAN) === 8 ? 'EAN8' : 'EAN13',
                        0.9, 22, 'black', false
                    ) !!}
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="10" class="tc" style="padding:20px; color:#aaa;">Aucune ligne de commande</td>
        </tr>
    @endforelse
    </tbody>
</table>

{{-- ══ TOTAUX ══ --}}
<table class="totaux-table" cellspacing="0" cellpadding="0">
    <tr>
        <td class="t-label">Nombre total d'articles</td>
        <td class="t-currency"></td>
        <td class="t-value">{{ $nbArticles }}</td>
    </tr>
    <tr>
        <td class="t-label">Montant total brut Hors Taxes</td>
        <td class="t-currency">EUR</td>
        <td class="t-value">{{ number_format($totalBrut, 2, ',', ' ') }}</td>
    </tr>
    <tr>
        <td class="t-label">Remise &nbsp; {{ $remisePct }} %</td>
        <td class="t-currency">EUR</td>
        <td class="t-value">{{ number_format($montantRemise, 2, ',', ' ') }}</td>
    </tr>
    <tr>
        <td class="t-label">Montant total net Hors Taxes</td>
        <td class="t-currency">EUR</td>
        <td class="t-value">{{ number_format($bonCommande?->montant_commande_net ?? $totalNet, 2, ',', ' ') }}</td>
    </tr>
</table>

</body>
</html>
