<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture {{ $facture->numero ?? $facture->id }}</title>
    <style>
        @page {
            size: landscape;
            margin: 15mm;
        }

        /* ============================================
           RESET & BASE STYLES
        ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: white;
            line-height: 1.4;
        }

        /* ============================================
           PAGE PRINCIPALE
        ============================================ */
        .page {
            max-width: 100%;
            margin: 0 auto;
            background: white;
        }

        .facture-inner {
            padding: 24px 32px;
        }

        /* ============================================
           HEADER
        ============================================ */
        .facture-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .brand-section {
            flex: 1;
        }

        .brand-name {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .brand-sub {
            font-size: 9px;
            font-weight: 500;
            color: #64748b;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .doc-title-section {
            text-align: right;
        }

        .doc-title {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -1px;
            color: #1e293b;
            line-height: 1;
        }

        /* ============================================
           DIVIDERS
        ============================================ */
        .divider-light {
            border: none;
            border-top: 1px solid #cbd5e1;
            margin: 0 0 24px 0;
        }

        .divider-dark {
            border: none;
            border-top: 2px solid #1e293b;
            margin: 0 0 24px 0;
        }

        /* ============================================
           INFOS FACTURE
        ============================================ */
        .info-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .emitter-info {
            flex: 1;
        }

        .emitter-name {
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .emitter-details {
            font-size: 9.5px;
            color: #475569;
            line-height: 1.5;
        }

        .facture-meta {
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            min-width: 240px;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 10px;
        }

        .meta-item:last-child {
            margin-bottom: 0;
        }

        .meta-label {
            color: #64748b;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .meta-value {
            font-weight: 600;
            color: #1e293b;
            font-family: 'DM Mono', monospace;
        }

        .meta-value.due {
            color: #dc2626;
            font-size: 12px;
            font-weight: 700;
        }

        /* ============================================
           ADRESSES
        ============================================ */
        .addresses-section {
            display: flex;
            gap: 40px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }

        .address-block {
            flex: 1;
        }

        .address-label {
            font-size: 8px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 8px;
        }

        .address-name {
            font-size: 11px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .address-detail {
            font-size: 9.5px;
            color: #475569;
            line-height: 1.5;
        }

        /* ============================================
           TABLEAU DES LIGNES
        ============================================ */
        .lines-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .lines-table thead th {
            font-size: 8px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #64748b;
            padding: 0 0 8px 0;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }

        .lines-table thead th.text-right {
            text-align: right;
        }

        .lines-table thead th.text-center {
            text-align: center;
        }

        .lines-table tbody td {
            padding: 10px 0;
            font-size: 10px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        .lines-table tbody tr:last-child td {
            border-bottom: none;
        }

        .lines-table tbody td.text-right {
            text-align: right;
        }

        .lines-table tbody td.text-center {
            text-align: center;
        }

        .product-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 3px;
        }

        .product-ref {
            font-size: 8px;
            color: #94a3b8;
            font-family: 'DM Mono', monospace;
        }

        .product-tva {
            font-size: 8px;
            color: #94a3b8;
            margin-top: 3px;
        }

        .mono-font {
            font-family: 'DM Mono', monospace;
            font-weight: 500;
        }

        /* ============================================
           TOTAUX
        ============================================ */
        .totals-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 32px;
        }

        .totals-box {
            width: 300px;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            font-size: 10px;
        }

        .total-label {
            color: #64748b;
        }

        .total-amount {
            font-family: 'DM Mono', monospace;
            color: #334155;
        }

        .total-line.grand-total {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #cbd5e1;
            margin-bottom: 10px;
        }

        .total-line.grand-total .total-label {
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
        }

        .total-line.grand-total .total-amount {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }

        .payment-received {
            margin-bottom: 10px;
        }

        .balance-box {
            border: 2px solid #1e293b;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
        }

        .balance-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.8px;
            color: #1e293b;
            text-transform: uppercase;
        }

        .balance-amount {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            font-family: 'DM Mono', monospace;
        }

        /* ============================================
           FOOTER
        ============================================ */
        .facture-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid #cbd5e1;
            margin-top: 16px;
        }

        .payment-info {
            font-size: 9px;
            color: #64748b;
        }

        .thanks-note {
            font-size: 9.5px;
            font-weight: 500;
            color: #475569;
            letter-spacing: 0.3px;
        }

        /* ============================================
           EMPTY STATE
        ============================================ */
        .empty-row td {
            padding: 8px 0 !important;
        }

        .empty-message {
            text-align: center;
            padding: 32px 20px !important;
            color: #94a3b8;
            font-style: italic;
            font-size: 10px;
        }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 900px) {
            .facture-inner {
                padding: 20px;
            }

            .facture-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .doc-title-section {
                text-align: left;
            }

            .addresses-section {
                flex-direction: column;
                gap: 20px;
            }

            .totals-container {
                justify-content: stretch;
            }

            .totals-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="facture-inner">
        {{-- HEADER --}}
        <div class="facture-header">
            <div class="brand-section">
                <div class="brand-name">{{ $magasinEmetteur?->name ?? 'Cosma' }}</div>
                <div class="brand-sub">Parfumeries</div>
            </div>
            <div class="doc-title-section">
                <div class="doc-title">FACTURE</div>
            </div>
        </div>

        <hr class="divider-dark">

        {{-- INFOS FACTURE --}}
        <div class="info-grid">
            <div class="emitter-info">
                <div class="emitter-name">{{ $magasinEmetteur?->name ?? 'COSMA PARFUMERIES' }}</div>
                <div class="emitter-details">
                    @if($magasinEmetteur?->adress){{ $magasinEmetteur->adress }}<br>@endif
                    @if($magasinEmetteur?->telephone){{ $magasinEmetteur->telephone }}<br>@endif
                    @if($magasinEmetteur?->email){{ $magasinEmetteur->email }}@endif
                    @if($magasinEmetteur?->siret)
                        <br>SIRET : {{ $magasinEmetteur->siret }}
                    @endif
                </div>
            </div>
            <div class="facture-meta">
                <div class="meta-item">
                    <span class="meta-label">N° FACTURE</span>
                    <span class="meta-value">{{ $facture->numero ?? $facture->id }}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">N° COMMANDE</span>
                    <span class="meta-value">{{ $commande?->id ?? '—' }}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">DATE D'ÉMISSION</span>
                    <span class="meta-value">
                        {{ $facture->date_commande
                            ? \Carbon\Carbon::parse($facture->date_commande)->translatedFormat('d M Y')
                            : now()->translatedFormat('d M Y') }}
                    </span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">DATE D'ÉCHÉANCE</span>
                    <span class="meta-value">
                        {{ $facture->date_reception
                            ? \Carbon\Carbon::parse($facture->date_reception)->translatedFormat('d M Y')
                            : '—' }}
                    </span>
                </div>
                <div class="meta-item" style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #e2e8f0;">
                    <span class="meta-label">SOLDE DÛ</span>
                    <span class="meta-value due">{{ number_format($total_ttc, 2, ',', ' ') }} EUR</span>
                </div>
            </div>
        </div>

        {{-- ADRESSES --}}
        <div class="addresses-section">
            <div class="address-block">
                <div class="address-label">Adresse de facturation</div>
                <div class="address-name">{{ $fournisseur?->name ?? 'Client non renseigné' }}</div>
                <div class="address-detail">
                    @if($fournisseur?->adresse_siege){{ $fournisseur->adresse_siege }}<br>@endif
                    @if($fournisseur?->code_postal || $fournisseur?->ville){{ $fournisseur->code_postal }} {{ $fournisseur->ville }}<br>@endif
                    @if($fournisseur?->telephone)Tél : {{ $fournisseur->telephone }}<br>@endif
                    @if($fournisseur?->mail){{ $fournisseur->mail }}@endif
                </div>
            </div>
            <div class="address-block">
                <div class="address-label">Adresse de livraison</div>
                @if($magasin)
                    <div class="address-name">{{ $magasin->name }}</div>
                    <div class="address-detail">
                        @if($magasin->adress){{ $magasin->adress }}<br>@endif
                        @if($magasin->telephone)Tél : {{ $magasin->telephone }}<br>@endif
                        @if($magasin->email){{ $magasin->email }}@endif
                    </div>
                @else
                    <div class="address-detail" style="color:#94a3b8;">— Adresse identique à la facturation —</div>
                @endif
            </div>
        </div>

        {{-- LIGNES DE PRODUITS --}}
        <table class="lines-table">
            <thead>
            <tr>
                <th class="text-center" style="width: 60px;">Qté</th>
                <th style="width: 30%;">Produit</th>
                <th style="width: 30%;">Description</th>
                <th class="text-right" style="width: 100px;">Prix HT</th>
                <th class="text-right" style="width: 100px;">Total HT</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($lignes as $ligne)
                <tr>
                    <td class="text-center mono-font">{{ number_format($ligne['qte'], 2, ',', ' ') }}</td>
                    <td>
                        <div class="product-name">{{ $ligne['designation'] }}</div>
                        @if($ligne['article'] ?? false)
                            <div class="product-ref">Réf : {{ $ligne['article'] }}</div>
                        @endif
                    </td>
                    <td>
                        <div>{{ $ligne['description'] ?? $ligne['designation'] }}</div>
                        @if(($ligne['tva'] ?? 0) > 0)
                            <div class="product-tva">TVA {{ number_format($ligne['tva'], 2) }}%</div>
                        @endif
                    </td>
                    <td class="text-right mono-font">{{ number_format($ligne['pu_ht'], 2, ',', ' ') }} €</td>
                    <td class="text-right mono-font">{{ number_format($ligne['montant_net_ht'], 2, ',', ' ') }} €</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty-message">
                        Aucune ligne de facturation
                    </td>
                </tr>
            @endforelse

            @for ($i = 0; $i < max(0, 5 - count($lignes)); $i++)
                <tr class="empty-row"><td colspan="5"></td></tr>
            @endfor
            </tbody>
        </table>

        {{-- TOTAUX --}}
        <div class="totals-container">
            <div class="totals-box">
                <div class="total-line">
                    <span class="total-label">Sous-total HT</span>
                    <span class="total-amount">{{ number_format($total_net_ht, 2, ',', ' ') }} €</span>
                </div>
                <div class="total-line">
                    <span class="total-label">TVA</span>
                    <span class="total-amount">{{ number_format($total_tva, 2, ',', ' ') }} €</span>
                </div>
                <div class="total-line">
                    <span class="total-label">Frais de livraison</span>
                    <span class="total-amount">{{ number_format($frais_livraison ?? 0, 2, ',', ' ') }} €</span>
                </div>
                @if(($total_remise ?? 0) > 0)
                    <div class="total-line">
                        <span class="total-label">Remise</span>
                        <span class="total-amount">− {{ number_format($total_remise, 2, ',', ' ') }} €</span>
                    </div>
                @endif
                <div class="total-line grand-total">
                    <span class="total-label">Total TTC</span>
                    <span class="total-amount">{{ number_format($total_ttc, 2, ',', ' ') }} €</span>
                </div>
                <div class="total-line payment-received">
                    <span class="total-label">Acomptes & paiements</span>
                    <span class="total-amount">{{ number_format($paiements_recus ?? 0, 2, ',', ' ') }} €</span>
                </div>
                <div class="balance-box">
                    <span class="balance-label">RESTE À PAYER</span>
                    <span class="balance-amount">{{ number_format(($total_ttc - ($paiements_recus ?? 0)), 2, ',', ' ') }} €</span>
                </div>
            </div>
        </div>

        {{-- FOOTER --}}
        <div class="facture-footer">
            <div class="payment-info">
                Règlement à l'ordre de <strong>{{ $magasinEmetteur?->name ?? 'COSMA PARFUMERIES' }}</strong><br>
                IBAN : {{ $iban ?? 'FR76 XXXX XXXX XXXX XXXX XXXX XXX' }} · BIC : {{ $bic ?? 'XXXXFRPP' }}
            </div>
            <div class="thanks-note">
                Merci pour votre confiance
            </div>
        </div>
    </div>
</div>
</body>
</html>
