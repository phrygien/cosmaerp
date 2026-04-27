<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture {{ $facture->numero ?? $facture->id }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            background: #f5f5f3;
            line-height: 1.5;
        }

        /* ── PAGE ── */
        .page {
            max-width: 760px;
            margin: 32px auto;
            background: #fff;
            padding: 48px 52px 40px;
        }

        /* ── HEADER ── */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }

        .header-left, .header-right {
            display: table-cell;
            vertical-align: bottom;
        }

        .header-right { text-align: right; }

        .brand-name {
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #1a1a1a;
        }

        .brand-sub {
            font-size: 9px;
            color: #999;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .doc-title {
            font-size: 32px;
            font-weight: 300;
            letter-spacing: 6px;
            text-transform: uppercase;
            color: #1a1a1a;
        }

        /* ── DIVIDER ── */
        .rule {
            border: none;
            border-top: 1px solid #e0e0e0;
            margin: 0 0 28px;
        }

        .rule-bold {
            border: none;
            border-top: 2px solid #1a1a1a;
            margin: 0 0 28px;
        }

        /* ── META BLOCK ── */
        .meta-block {
            display: table;
            width: 100%;
            margin-bottom: 36px;
        }

        .meta-left, .meta-right {
            display: table-cell;
            vertical-align: top;
        }

        .meta-right { text-align: right; }

        .emetteur-name {
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .emetteur-detail {
            font-size: 9.5px;
            color: #777;
            line-height: 1.8;
        }

        .meta-grid {
            display: inline-block;
            text-align: left;
        }

        .meta-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }

        .meta-label {
            display: table-cell;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #999;
            padding-right: 20px;
            white-space: nowrap;
        }

        .meta-value {
            display: table-cell;
            font-size: 9.5px;
            font-weight: 600;
            font-family: 'DM Mono', monospace;
            color: #1a1a1a;
            text-align: right;
            white-space: nowrap;
        }

        .meta-value.due {
            color: #c0392b;
            font-size: 11px;
        }

        /* ── ADDRESS BLOCKS ── */
        .addr-row {
            display: table;
            width: 100%;
            margin-bottom: 36px;
        }

        .addr-col {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }

        .addr-col:last-child { padding-left: 40px; }

        .addr-label {
            font-size: 8px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #bbb;
            margin-bottom: 8px;
        }

        .addr-name {
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .addr-detail {
            font-size: 9.5px;
            color: #666;
            line-height: 1.8;
        }

        /* ── LINES TABLE ── */
        .lines-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .lines-table thead th {
            font-size: 8px;
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #999;
            padding: 0 0 10px;
            border-bottom: 1px solid #e0e0e0;
            text-align: left;
        }

        .lines-table thead th.right { text-align: right; }
        .lines-table thead th.center { text-align: center; }

        .lines-table tbody tr td {
            padding: 12px 0;
            font-size: 10px;
            color: #333;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }

        .lines-table tbody tr:last-child td { border-bottom: none; }

        .lines-table tbody td.right { text-align: right; }
        .lines-table tbody td.center { text-align: center; }

        .line-designation {
            font-weight: 500;
            color: #1a1a1a;
        }

        .line-ref {
            font-size: 8.5px;
            color: #bbb;
            margin-top: 2px;
        }

        .line-tva {
            font-size: 8px;
            color: #ccc;
        }

        .mono { font-family: 'DM Mono', monospace; }

        /* ── TOTALS ── */
        .totals-wrap {
            display: table;
            width: 100%;
            border-top: 1px solid #e0e0e0;
            padding-top: 16px;
            margin-top: 4px;
        }

        .totals-spacer {
            display: table-cell;
            width: 55%;
        }

        .totals-block {
            display: table-cell;
            width: 45%;
            vertical-align: top;
        }

        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }

        .total-label {
            display: table-cell;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #aaa;
        }

        .total-value {
            display: table-cell;
            font-size: 9.5px;
            font-family: 'DM Mono', monospace;
            text-align: right;
            color: #555;
        }

        .total-row.grand { margin-top: 12px; padding-top: 12px; border-top: 1px solid #e0e0e0; }

        .total-row.grand .total-label {
            font-size: 10px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .total-row.grand .total-value {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .total-row.solde {
            margin-top: 8px;
            padding-top: 8px;
        }

        .solde-box {
            background: #1a1a1a;
            padding: 10px 14px;
            display: table;
            width: 100%;
        }

        .solde-box .total-label {
            color: #fff;
            font-size: 9px;
            font-weight: 600;
        }

        .solde-box .total-value {
            color: #fff;
            font-size: 12px;
            font-weight: 600;
        }

        /* ── FOOTER ── */
        .footer {
            margin-top: 44px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            vertical-align: middle;
        }

        .footer-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }

        .footer-text {
            font-size: 8.5px;
            color: #bbb;
            line-height: 1.8;
        }

        .footer-thanks {
            font-size: 9px;
            font-weight: 500;
            color: #999;
            letter-spacing: 0.5px;
        }

        /* ── EMPTY ROWS ── */
        .empty-row td {
            height: 38px;
            border-bottom: 1px solid #f0f0f0;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- HEADER --}}
    <table class="header">
        <tr>
            <td class="header-left">
                <div class="brand-name">{{ $magasinEmetteur?->name ?? 'Cosma' }}</div>
                <div class="brand-sub">Parfumeries</div>
            </td>
            <td class="header-right">
                <div class="doc-title">Facture</div>
            </td>
        </tr>
    </table>

    <hr class="rule-bold">

    {{-- META : émetteur + infos facture --}}
    <table class="meta-block">
        <tr>
            <td class="meta-left">
                <div class="emetteur-name">{{ $magasinEmetteur?->name ?? 'COSMA PARFUMERIES' }}</div>
                <div class="emetteur-detail">
                    @if($magasinEmetteur?->adress){{ $magasinEmetteur->adress }}<br>@endif
                    @if($magasinEmetteur?->telephone){{ $magasinEmetteur->telephone }}<br>@endif
                    @if($magasinEmetteur?->email){{ $magasinEmetteur->email }}@endif
                </div>
            </td>
            <td class="meta-right">
                <div class="meta-grid">
                    <div class="meta-row">
                        <span class="meta-label">N° Facture</span>
                        <span class="meta-value">{{ $facture->numero ?? $facture->id }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">N° Commande</span>
                        <span class="meta-value">{{ $commande?->id ?? '—' }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Date</span>
                        <span class="meta-value">
                            {{ $facture->date_commande
                                ? \Carbon\Carbon::parse($facture->date_commande)->translatedFormat('d M Y')
                                : now()->translatedFormat('d M Y') }}
                        </span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Échéance</span>
                        <span class="meta-value">
                            {{ $facture->date_reception
                                ? \Carbon\Carbon::parse($facture->date_reception)->translatedFormat('d M Y')
                                : '—' }}
                        </span>
                    </div>
                    <div class="meta-row" style="margin-top:8px;">
                        <span class="meta-label">Solde dû</span>
                        <span class="meta-value due">{{ number_format($total_ttc, 2, ',', ' ') }} EUR</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ADDRESSES --}}
    <table class="addr-row">
        <tr>
            <td class="addr-col">
                <div class="addr-label">Adresse de Facturation</div>
                <div class="addr-name">{{ $fournisseur?->name ?? '—' }}</div>
                <div class="addr-detail">
                    @if($fournisseur?->adresse_siege){{ $fournisseur->adresse_siege }}<br>@endif
                    @if($fournisseur?->code_postal || $fournisseur?->ville){{ $fournisseur->code_postal }} {{ $fournisseur->ville }}<br>@endif
                    @if($fournisseur?->telephone)Tél : {{ $fournisseur->telephone }}<br>@endif
                    @if($fournisseur?->mail){{ $fournisseur->mail }}@endif
                </div>
            </td>
            <td class="addr-col">
                <div class="addr-label">Adresse de Livraison</div>
                @if($magasin)
                    <div class="addr-name">{{ $magasin->name }}</div>
                    <div class="addr-detail">
                        @if($magasin->adress){{ $magasin->adress }}<br>@endif
                        @if($magasin->telephone)Tél : {{ $magasin->telephone }}<br>@endif
                        @if($magasin->email){{ $magasin->email }}@endif
                    </div>
                @else
                    <div class="addr-detail" style="color:#ccc;font-style:italic;">Non renseigné</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- LINES TABLE --}}
    <table class="lines-table">
        <thead>
        <tr>
            <th class="center" style="width:9%">Qté</th>
            <th style="width:32%">Produit</th>
            <th style="width:29%">Description</th>
            <th class="right" style="width:15%">Prix HT</th>
            <th class="right" style="width:15%">Total HT</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($lignes as $ligne)
            <tr>
                <td class="center mono">{{ number_format($ligne['qte'], 2, ',', ' ') }}</td>
                <td>
                    <div class="line-designation">{{ $ligne['designation'] }}</div>
                    @if($ligne['article'])
                        <div class="line-ref">{{ $ligne['article'] }}</div>
                    @endif
                </td>
                <td>
                    <div>{{ $ligne['designation'] }}</div>
                    @if($ligne['tva'] > 0)
                        <div class="line-tva">TVA {{ number_format($ligne['tva'], 2) }}%</div>
                    @endif
                </td>
                <td class="right mono">{{ number_format($ligne['pu_ht'], 2, ',', ' ') }} €</td>
                <td class="right mono">{{ number_format($ligne['montant_net_ht'], 2, ',', ' ') }} €</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align:center;padding:24px;color:#ccc;font-style:italic;font-size:10px;">
                    Aucune ligne de facturation.
                </td>
            </tr>
        @endforelse

        @for ($i = 0; $i < max(0, 5 - count($lignes)); $i++)
            <tr class="empty-row"><td colspan="5"></td></tr>
        @endfor
        </tbody>
    </table>

    {{-- TOTALS --}}
    <table class="totals-wrap">
        <tr>
            <td class="totals-spacer"></td>
            <td class="totals-block">
                <div class="total-row">
                    <span class="total-label">Sous-total HT</span>
                    <span class="total-value">{{ number_format($total_net_ht, 2, ',', ' ') }} €</span>
                </div>
                <div class="total-row">
                    <span class="total-label">TVA</span>
                    <span class="total-value">{{ number_format($total_tva, 2, ',', ' ') }} €</span>
                </div>
                <div class="total-row">
                    <span class="total-label">Frais</span>
                    <span class="total-value">0,00 €</span>
                </div>
                <div class="total-row">
                    <span class="total-label">Remise</span>
                    <span class="total-value">
                        {{ $total_remise > 0
                            ? '−'.number_format($total_remise, 2, ',', ' ').' €'
                            : '0,00 €' }}
                    </span>
                </div>
                <div class="total-row grand">
                    <span class="total-label">Total TTC</span>
                    <span class="total-value">{{ number_format($total_ttc, 2, ',', ' ') }} €</span>
                </div>
                <div class="total-row">
                    <span class="total-label">Paiements reçus</span>
                    <span class="total-value">0,00 €</span>
                </div>
                <div class="total-row solde">
                    <div class="solde-box">
                        <span class="total-label">Solde à régler</span>
                        <span class="total-value">{{ number_format($total_ttc, 2, ',', ' ') }} €</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- FOOTER --}}
    <table class="footer">
        <tr>
            <td class="footer-left">
                <div class="footer-text">
                    Règlement à l'ordre de
                    <strong>{{ $magasinEmetteur?->name ?? 'COSMA PARFUMERIES' }}</strong>
                </div>
            </td>
            <td class="footer-right">
                <div class="footer-thanks">Merci pour votre confiance.</div>
            </td>
        </tr>
    </table>

</div>
</body>
</html>
