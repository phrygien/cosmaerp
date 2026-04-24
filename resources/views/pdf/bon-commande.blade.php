<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Bon de commande</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #000;
            background: #fff;
            padding: 14px 18px;
        }

        /* ── Adresses ── */
        .top-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .address-box {
            border: 1px solid #888;
            padding: 5px 7px;
            font-size: 8.5px;
            line-height: 1.55;
            vertical-align: top;
        }
        .box-label {
            font-size: 7px;
            color: #555;
            display: block;
            margin-bottom: 2px;
        }
        .destinataire-box {
            border: 1px solid #888;
            border-top: none;
            padding: 5px 7px;
            font-size: 8.5px;
            line-height: 1.55;
        }

        /* ── Notre N° client ── */
        .notre-num { font-size: 8.5px; margin: 5px 0 3px 0; }

        /* ── Titre ── */
        .titre-wrap { text-align: center; margin: 4px 0 5px 0; }
        .titre-inner {
            display: inline-block;
            border: 1px solid #bbb;
            background: #f7f7f7;
            padding: 5px 60px;
        }
        .titre-inner h1 { font-size: 13px; font-weight: bold; letter-spacing: 0.5px; }

        /* ── Représentant ── */
        .rep-section { font-size: 8.5px; margin-bottom: 7px; line-height: 1.7; }

        /* ── Corps ── */
        .corps-table { width: 100%; border-collapse: collapse; margin-bottom: 7px; font-size: 8.5px; }
        .corps-table td { vertical-align: top; padding: 0 3px; }
        .corps-table td:first-child  { width: 38%; padding-left: 0; }
        .corps-table td:nth-child(2) { width: 27%; text-align: center; }
        .corps-table td:last-child   { width: 35%; padding-right: 0; text-align: right; }
        .corps-line { margin-bottom: 2px; }
        .fournisseur-name { font-size: 12px; font-weight: bold; }
        .page-info { line-height: 1.8; }

        /* ── Table lignes ── */
        .lignes-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 7.5px;
        }
        .lignes-table th {
            background: #e8e8e8;
            border: 1px solid #aaa;
            padding: 3px 2px;
            text-align: center;
            font-size: 7px;
            font-weight: bold;
        }
        .lignes-table td {
            border: 1px solid #aaa;
            padding: 3px 2px;
            vertical-align: middle;
        }
        .tc { text-align: center; }
        .tr { text-align: right; }

        /* ── Totaux ── */
        .totaux-table {
            width: 55%;
            margin-left: auto;
            border-collapse: collapse;
            font-size: 8px;
        }
        .totaux-table td { padding: 2.5px 5px; border: 1px solid #aaa; }
        .t-label    { font-weight: bold; background: #f4f4f4; width: 62%; }
        .t-currency { text-align: center; color: #555; width: 9%;  border-left: none; }
        .t-value    { text-align: right;  font-weight: bold; width: 29%; border-left: none; }
    </style>
</head>
<body>

@php
    $totalBrut     = $commande->details->sum(fn($d) => $d->pu_achat_HT  * $d->quantite);
    $totalNet      = $commande->details->sum(fn($d) => $d->pu_achat_net * $d->quantite);
    $montantRemise = $totalBrut - $totalNet;
    $remisePct     = $totalBrut > 0 ? round((1 - $totalNet / $totalBrut) * 100, 2) : 0;
    $nbArticles    = $commande->details->count();
    $magFact       = $bonCommande?->magasinFacturation;
    $magLiv        = $bonCommande?->magasinLivraison ?? $commande->magasinLivraison;
@endphp

{{-- ══ ADRESSES ══ --}}
<table class="top-table" cellspacing="0" cellpadding="0">
    <tr>
        {{-- Facturation --}}
        <td style="width:48%; vertical-align:top;">
            <div class="address-box">
                <span class="box-label">Adresse de facturation</span>
                <strong>{{ $magFact?->name ?? $commande->magasinLivraison?->name ?? '—' }}</strong><br>
                @if($magFact?->adresse)  {{ $magFact->adresse }}<br>  @endif
                @if($magFact?->adresse1) {{ $magFact->adresse1 }}<br> @endif
                @php $cpF = $magFact?->code_postal ?? $magFact?->cp ?? null; @endphp
                @if($cpF || $magFact?->ville) {{ $cpF }} {{ $magFact?->ville }}<br> @endif
                @if($magFact?->telephone ?? $magFact?->tel ?? null)
                    &#9742;&nbsp;{{ $magFact->telephone ?? $magFact->tel }}<br>
                @endif
                @if($magFact?->fax) Fax {{ $magFact->fax }} @endif
            </div>
        </td>

        <td style="width:4%;"></td>

        {{-- Livraison + Destinataire --}}
        <td style="width:48%; vertical-align:top;">
            <div class="address-box">
                <span class="box-label">Adresse de livraison</span>
                <strong>{{ $magLiv?->name ?? '—' }}</strong><br>
                @if($magLiv?->adresse)  {{ $magLiv->adresse }}<br>  @endif
                @if($magLiv?->adresse1) {{ $magLiv->adresse1 }}<br> @endif
                @php $cpL = $magLiv?->code_postal ?? $magLiv?->cp ?? null; @endphp
                @if($cpL || $magLiv?->ville) {{ $cpL }} {{ $magLiv?->ville }}<br> @endif
                @if($magLiv?->telephone ?? $magLiv?->tel ?? null)
                    &#9742;&nbsp;{{ $magLiv->telephone ?? $magLiv->tel }}<br>
                @endif
                @if($magLiv?->fax) Fax {{ $magLiv->fax }} @endif
            </div>
            <div class="destinataire-box">
                <span class="box-label">Destinataire</span>
                <strong>{{ $commande->fournisseur?->name ?? '—' }}</strong><br>
                @if($commande->fournisseur?->adresse)  {{ $commande->fournisseur->adresse }}<br>  @endif
                @if($commande->fournisseur?->adresse1) {{ $commande->fournisseur->adresse1 }}<br> @endif
                @php $cpFo = $commande->fournisseur?->code_postal ?? $commande->fournisseur?->cp ?? null; @endphp
                @if($cpFo || $commande->fournisseur?->ville)
                    {{ $cpFo }} {{ $commande->fournisseur?->ville }}<br>
                @endif
                @if($commande->fournisseur?->telephone ?? $commande->fournisseur?->tel ?? null)
                    &#9742;&nbsp;{{ $commande->fournisseur->telephone ?? $commande->fournisseur->tel }}<br>
                @endif
                @if($commande->fournisseur?->fax) Fax {{ $commande->fournisseur->fax }} @endif
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
    Représentant :<br>&nbsp;<br>Fax
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
                <div style="margin-top:4px;">
                    Date de livraison&nbsp;&nbsp;{{ $bonCommande?->date_livraison_prevue
                        ? \Carbon\Carbon::parse($bonCommande->date_livraison_prevue)->format('d/m/Y')
                        : '___ /___ /______' }}
                </div>
                <div>Date de règlement&nbsp;&nbsp;___ /___</div>
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
            <td class="tc" style="font-family:monospace; font-size:7px;">{{ $detail->product?->EAN ?? '—' }}</td>
            <td>{{ $detail->product?->designation ?? '—' }}</td>
            <td class="tc">{{ $detail->product?->reference ?? '' }}</td>
            <td class="tc">{{ $detail->product?->ref_fournisseur ?? '' }}</td>
            <td class="tc">{{ $detail->quantite }}</td>
            <td class="tr">{{ number_format($detail->pu_achat_HT, 2, ',', ' ') }}</td>
            <td class="tc">{{ $detail->taux_remise ?? '—' }}</td>
            <td class="tr">{{ number_format($detail->pu_achat_net, 2, ',', ' ') }}</td>
            <td class="tc" style="font-family:monospace; font-size:7px;">{{ $detail->product?->EAN ?? '' }}</td>
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
