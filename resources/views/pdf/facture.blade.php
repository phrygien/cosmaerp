<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture {{ $facture->numero ?? $facture->id }}</title>
    <style>
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
            font-size: 12px;
            color: #1e293b;
            background: #f8fafc;
            line-height: 1.5;
            padding: 40px 20px;
        }

        /* ============================================
           PAGE PRINCIPALE
        ============================================ */
        .page {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .facture-inner {
            padding: 48px 56px;
        }

        /* ============================================
           HEADER
        ============================================ */
        .facture-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 48px;
            flex-wrap: wrap;
            gap: 24px;
        }

        .brand-section {
            flex: 1;
        }

        .brand-name {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 4px;
        }

        .brand-sub {
            font-size: 10px;
            font-weight: 500;
            color: #94a3b8;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .doc-title-section {
            text-align: right;
        }

        .doc-title {
            font-size: 38px;
            font-weight: 700;
            letter-spacing: -1px;
            color: #0f172a;
            line-height: 1;
        }

        /* ============================================
           DIVIDERS
        ============================================ */
        .divider-light {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 0 0 32px 0;
        }

        .divider-dark {
            border: none;
            border-top: 2px solid #0f172a;
            margin: 0 0 32px 0;
        }

        /* ============================================
           INFOS FACTURE
        ============================================ */
        .info-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 48px;
            flex-wrap: wrap;
            gap: 24px;
        }

        .emitter-info {
            flex: 1;
        }

        .emitter-name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .emitter-details {
            font-size: 10.5px;
            color: #475569;
            line-height: 1.6;
        }

        .facture-meta {
            background: #f8fafc;
            padding: 16px 20px;
            border-radius: 12px;
            min-width: 240px;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 11px;
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
            color: #0f172a;
            font-family: 'DM Mono', monospace;
        }

        .meta-value.due {
            color: #dc2626;
            font-size: 13px;
            font-weight: 700;
        }

        /* ============================================
           ADRESSES
        ============================================ */
        .addresses-section {
            display: flex;
            gap: 48px;
            margin-bottom: 48px;
            flex-wrap: wrap;
        }

        .address-block {
            flex: 1;
        }

        .address-label {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 12px;
        }

        .address-name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .address-detail {
            font-size: 10.5px;
            color: #475569;
            line-height: 1.6;
        }

        /* ============================================
           TABLEAU DES LIGNES
        ============================================ */
        .lines-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 32px;
        }

        .lines-table thead th {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #64748b;
            padding: 0 0 14px 0;
            border-bottom: 1.5px solid #e2e8f0;
            text-align: left;
        }

        .lines-table thead th.text-right {
            text-align: right;
        }

        .lines-table thead th.text-center {
            text-align: center;
        }

        .lines-table tbody td {
            padding: 16px 0;
            font-size: 11px;
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
            color: #0f172a;
            margin-bottom: 4px;
        }

        .product-ref {
            font-size: 9px;
            color: #94a3b8;
            font-family: 'DM Mono', monospace;
        }

        .product-tva {
            font-size: 9px;
            color: #a0aec0;
            margin-top: 4px;
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
            margin-bottom: 48px;
        }

        .totals-box {
            width: 320px;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 11px;
        }

        .total-label {
            color: #64748b;
        }

        .total-amount {
            font-family: 'DM Mono', monospace;
            color: #334155;
        }

        .total-line.grand-total {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1.5px solid #e2e8f0;
            margin-bottom: 16px;
        }

        .total-line.grand-total .total-label {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }

        .total-line.grand-total .total-amount {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .payment-received {
            margin-bottom: 16px;
        }

        .balance-box {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 16px 20px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
        }

        .balance-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            color: #cbd5e1;
            text-transform: uppercase;
        }

        .balance-amount {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            font-family: 'DM Mono', monospace;
        }

        /* ============================================
           FOOTER
        ============================================ */
        .facture-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 32px;
            border-top: 1px solid #e2e8f0;
            margin-top: 16px;
        }

        .payment-info {
            font-size: 10px;
            color: #64748b;
        }

        .thanks-note {
            font-size: 10.5px;
            font-weight: 500;
            color: #475569;
            letter-spacing: 0.3px;
        }

        /* ============================================
           EMPTY STATE
        ============================================ */
        .empty-row td {
            padding: 12px 0 !important;
        }

        .empty-message {
            text-align: center;
            padding: 48px 24px !important;
            color: #94a3b8;
            font-style: italic;
            font-size: 11px;
        }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 700px) {
            .facture-inner {
                padding: 32px 24px;
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
                gap: 24px;
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
                <div class="brand-sub">Parfumeries · Depuis 1998</div>
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
                <div class="meta-item" style="margin-top: 12px; padding-top: 8px; border-top: 1px solid #e2e8f0;">
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
                <th class="text-center" style="width: 80px;">Qté</th>
                <th style="width: 35%;">Produit</th>
                <th style="width: 30%;">Description</th>
                <th class="text-right" style="width: 120px;">Prix HT</th>
                <th class="text-right" style="width: 120px;">Total HT</th>
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
                    <span class="total-label">TVA ({{ number_format(($total_tva / max($total_net_ht, 0.01)) * 100, 1) }}%)</span>
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
