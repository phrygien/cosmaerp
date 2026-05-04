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

        /* ── COLOR PALETTE ── */
        :root {
            --orange:      #E8720C;
            --orange-light:#F5A050;
            --dark:        #1a1a1a;
            --text:        #222222;
            --muted:       #555555;
            --border:      #cccccc;
            --bg-light:    #f9f9f9;
        }

        body {
            font-family: 'Roboto Condensed', 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            color: var(--text);
            background: #ffffff;
            line-height: 1.4;
        }

        .page {
            padding: 22px 28px 20px 28px;
        }

        /* ══════════════════════════════════════
           HEADER
        ══════════════════════════════════════ */
        .header {
            width: 100%;
            margin-bottom: 18px;
        }

        .header-table {
            width: 100%;
        }

        /* Left: emitter */
        .company-block {
            width: 55%;
            vertical-align: top;
        }

        .logo-placeholder {
            width: 48px;
            height: 40px;
            margin-bottom: 4px;
            /* In production, replace with <img> */
            background: #eee;
            display: inline-block;
        }

        .company-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--orange);
            letter-spacing: 0.5px;
        }

        .company-details {
            margin-top: 4px;
            font-size: 8.5px;
            color: var(--text);
            font-weight: 700;
            line-height: 1.7;
        }

        .company-details .normal {
            font-weight: 400;
            color: var(--muted);
        }

        .company-details a {
            color: var(--orange);
            text-decoration: underline;
        }

        /* Right: client */
        .client-block {
            width: 42%;
            vertical-align: top;
            text-align: left;
        }

        .client-name {
            font-size: 11px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 2px;
        }

        .client-info {
            font-size: 8.5px;
            color: var(--text);
            line-height: 1.7;
        }

        .client-info .label {
            color: var(--muted);
            font-style: italic;
        }

        /* ══════════════════════════════════════
           META BAR (Date / Facture N° / ...)
        ══════════════════════════════════════ */
        .meta-bar {
            width: 100%;
            margin-bottom: 16px;
            border-collapse: collapse;
        }

        .meta-bar th {
            background: var(--orange);
            color: #ffffff;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            text-align: center;
            border: 1px solid #fff;
        }

        .meta-bar td {
            background: #ffffff;
            color: var(--dark);
            font-size: 8.5px;
            font-weight: 400;
            padding: 5px 8px;
            text-align: center;
            border: 1px solid var(--border);
        }

        /* ══════════════════════════════════════
           LINES TABLE
        ══════════════════════════════════════ */
        .table-wrapper {
            width: 100%;
            margin-bottom: 14px;
        }

        .lines-table {
            width: 100%;
            border-collapse: collapse;
        }

        .lines-table thead tr {
            background: var(--orange);
        }

        .lines-table thead th {
            padding: 7px 6px;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            color: #ffffff;
            border: 1px solid #fff;
        }

        .lines-table thead th.left   { text-align: left; }
        .lines-table thead th.right  { text-align: right; }
        .lines-table thead th.center { text-align: center; }

        .lines-table tbody tr {
            border-bottom: 1px solid #e8e8e8;
        }

        .lines-table tbody td {
            padding: 5px 6px;
            font-size: 8.5px;
            vertical-align: middle;
            border: 1px solid #e8e8e8;
            color: var(--text);
        }

        .lines-table tbody td.left   { text-align: left; }
        .lines-table tbody td.right  { text-align: right; }
        .lines-table tbody td.center { text-align: center; }

        .designation-main { font-weight: 600; color: var(--dark); }
        .designation-sub  { font-size: 7px; color: #888; margin-top: 1px; }

        /* ══════════════════════════════════════
           TOTAUX
        ══════════════════════════════════════ */
        .totaux-wrapper {
            width: 100%;
            margin-bottom: 14px;
        }

        .totaux-left  { width: 50%; vertical-align: top; }
        .totaux-right { width: 46%; vertical-align: top; }
        .totaux-spacer{ width: 4%; }

        /* TVA recap */
        .tva-recap-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .tva-recap-table thead th {
            background: var(--orange);
            color: #fff;
            padding: 5px 6px;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            text-align: center;
            border: 1px solid #fff;
        }

        .tva-recap-table tbody td {
            padding: 4px 6px;
            text-align: center;
            border: 1px solid var(--border);
            color: var(--text);
        }

        /* Total block */
        .totaux-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totaux-table tr td {
            padding: 5px 8px;
            font-size: 8.5px;
            border: 1px solid var(--border);
        }

        .totaux-table tr td:first-child {
            color: var(--muted);
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 8px;
        }

        .totaux-table tr td:last-child {
            text-align: right;
            font-weight: 700;
            color: var(--dark);
        }

        /* Sous-total HT row */
        .row-sous-total td {
            background: var(--orange) !important;
            color: #ffffff !important;
            font-weight: 700;
        }
        .row-sous-total td:first-child,
        .row-sous-total td:last-child {
            color: #ffffff !important;
        }

        /* TVA italic orange */
        .row-tva td {
            color: var(--orange) !important;
            font-style: italic;
        }
        .row-tva td:first-child { color: var(--orange) !important; }

        /* Total TTC */
        .row-total-ttc td {
            font-size: 10px;
            font-weight: 700;
        }

        /* Acompte orange italic */
        .row-acompte td {
            color: var(--orange) !important;
            font-style: italic;
            font-weight: 700;
        }
        .row-acompte td:first-child { color: var(--orange) !important; }

        /* A payer orange bg */
        .row-a-payer td {
            background: var(--orange) !important;
            color: #ffffff !important;
            font-size: 10px;
            font-weight: 700;
        }
        .row-a-payer td:first-child { color: #ffffff !important; }

        /* Remise */
        .row-remise td { color: #c0392b !important; }

        /* ══════════════════════════════════════
           BANQUE SECTION
        ══════════════════════════════════════ */
        .bank-section {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
        }

        .bank-header {
            background: var(--orange);
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            text-align: center;
            padding: 6px 10px;
            letter-spacing: 0.5px;
        }

        .bank-body {
            border: 1px solid var(--border);
            padding: 8px 12px;
            font-size: 8.5px;
            color: var(--dark);
            text-align: center;
        }

        .bank-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .bank-grid th {
            font-size: 7.5px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            padding: 3px 8px;
            text-align: left;
        }

        .bank-grid td {
            font-size: 8.5px;
            color: var(--dark);
            font-weight: 600;
            padding: 2px 8px;
            text-align: left;
        }

        /* ══════════════════════════════════════
           FOOTER NOTE
        ══════════════════════════════════════ */
        .footer-note {
            width: 100%;
            font-size: 7px;
            color: var(--muted);
            line-height: 1.5;
            font-style: italic;
            border-top: 1px solid var(--border);
            padding-top: 8px;
            margin-top: 8px;
        }

        /* ── WATERMARK STATUT ── */
        .status-stamp {
            position: absolute;
            top: 120px;
            right: 40px;
            font-size: 32px;
            font-weight: 700;
            color: rgba(232, 114, 12, 0.12);
            border: 4px solid rgba(232, 114, 12, 0.12);
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
         EN-TÊTE : Émetteur (gauche) + Client (droite)
    ══════════════════════════════════════════════ --}}
    <div class="header">
        <table class="header-table">
            <tr>
                {{-- Émetteur --}}
                <td class="company-block">
                    {{-- Logo : remplacer par <img src="..." ...> si disponible --}}
                    {{-- <div class="logo-placeholder"></div> --}}

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
                            SIRET {{ $magasinEmetteur->siret }}<br>
                        @endif
                        @if($magasinEmetteur?->rcs)
                            <span class="normal">RCS {{ $magasinEmetteur->rcs }}</span><br>
                        @endif
                        @if($magasinEmetteur?->num_tva)
                            TVA Intracommunautaire : <span class="normal">{{ $magasinEmetteur->num_tva }}</span><br>
                        @endif
                        @if($magasinEmetteur?->telephone)
                            Tél : <span class="normal">{{ $magasinEmetteur->telephone }}</span><br>
                        @endif
                        @if($magasinEmetteur?->email)
                            <a href="mailto:{{ $magasinEmetteur->email }}">{{ $magasinEmetteur->email }}</a>
                        @endif
                    </div>
                </td>

                {{-- Client --}}
                <td class="client-block">
                    <div class="client-name">
                        {{ $fournisseur?->name ?? $fournisseur?->raison_social ?? '—' }}
                    </div>
                    <div class="client-info">
                        @if($fournisseur?->adresse_siege)
                            {{ $fournisseur->adresse_siege }}<br>
                        @endif
                        @if($fournisseur?->code_postal || $fournisseur?->ville)
                            {{ $fournisseur?->code_postal }} {{ $fournisseur?->ville }}<br>
                        @endif
                        @if($fournisseur?->siret || $fournisseur?->num_tva)
                            <span class="label">Siret {{ $fournisseur?->siret }}/ TVA {{ $fournisseur?->num_tva }}</span><br>
                        @endif
                        @if($facture->ref_client ?? false)
                            Ref Client : {{ $facture->ref_client }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ══════════════════════════════════════════════
         BARRE META : Date / N° / Réf commande / Échéance / Mode règlement
    ══════════════════════════════════════════════ --}}
    <table class="meta-bar">
        <thead>
        <tr>
            <th>Date</th>
            <th>Facture N°</th>
            @if($commande?->numero_commande)<th>Réf. Commande</th>@endif
            @if($facture->date_echeance)<th>Échéance</th>@endif
            @if($facture->conditions_paiement)<th>Soit le</th>@endif
            @if($facture->mode_paiement)<th>Mode de règlement</th>@endif
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                {{ $facture->date_facture
                    ? \Carbon\Carbon::parse($facture->date_facture)->format('d/m/Y')
                    : $date_impression }}
            </td>
            <td>{{ $facture->numero ?? str_pad($facture->id, 7, '0', STR_PAD_LEFT) }}</td>
            @if($commande?->numero_commande)<td>{{ $commande->numero_commande }}</td>@endif
            @if($facture->date_echeance)
                <td>{{ \Carbon\Carbon::parse($facture->date_echeance)->format('d/m/Y') }}</td>
            @endif
            @if($facture->conditions_paiement)<td>{{ $facture->conditions_paiement }}</td>@endif
            @if($facture->mode_paiement)<td>{{ strtoupper($facture->mode_paiement) }}</td>@endif
        </tr>
        </tbody>
    </table>

    {{-- ══════════════════════════════════════════════
         LIGNES DE FACTURE
    ══════════════════════════════════════════════ --}}
    <div class="table-wrapper">
        <table class="lines-table">
            <thead>
            <tr>
                <th class="left"   style="width:34%">Désignation</th>
                <th class="center" style="width:7%">Unité</th>
                <th class="center" style="width:8%">Quantité</th>
                <th class="right"  style="width:10%">PU HT</th>
                <th class="center" style="width:7%">TVA</th>
                @if($lignes->contains(fn($l) => $l['taux_remise'] > 0))
                    <th class="center" style="width:7%">Remise</th>
                @endif
                <th class="right"  style="width:12%">TOTAL HT</th>
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
                    <td class="center">{{ $ligne['unite'] ?? 'F' }}</td>
                    <td class="center">{{ number_format($ligne['qte'], 0, ',', ' ') }}</td>
                    <td class="right">{{ number_format($ligne['pu_ht'], 2, ',', ' ') }} €</td>
                    <td class="center">{{ number_format($ligne['tva'], 0) }}%</td>
                    @if($lignes->contains(fn($l) => $l['taux_remise'] > 0))
                        <td class="center" style="color:#c0392b;">
                            @if($ligne['taux_remise'] > 0)
                                {{ number_format($ligne['taux_remise'], 2, ',', ' ') }}%
                            @else —
                            @endif
                        </td>
                    @endif
                    <td class="right">{{ number_format($ligne['montant_net_ht'], 2, ',', ' ') }} €</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="center" style="padding:20px; color:#aaa; font-style:italic;">
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
            {{-- Récap TVA (gauche) --}}
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
                        <th>Base HT</th>
                        <th>% TVA</th>
                        <th>Montant TVA</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($tvaGroupes as $recap)
                        <tr>
                            <td>{{ number_format($recap['base_ht'], 2, ',', ' ') }} €</td>
                            <td style="color:var(--orange); font-weight:700;">{{ number_format($recap['taux'], 1) }}%</td>
                            <td>{{ number_format($recap['montant_tva'], 2, ',', ' ') }} €</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                @if($facture->notes || $facture->remarques)
                    <div style="margin-top:12px; font-size:7.5px; color:#555; border-left:3px solid var(--orange); padding-left:8px;">
                        <div style="font-weight:700; color:#888; font-size:7px; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:3px;">Notes</div>
                        {{ $facture->notes ?? $facture->remarques }}
                    </div>
                @endif
            </td>

            <td class="totaux-spacer"></td>

            {{-- Bloc totaux (droite) --}}
            <td class="totaux-right">
                <table class="totaux-table">
                    <tr class="row-sous-total">
                        <td>Sous Total HT</td>
                        <td>{{ number_format($total_ht, 2, ',', ' ') }} €</td>
                    </tr>
                    <tr class="row-tva">
                        <td>TVA</td>
                        <td>{{ number_format($total_tva, 2, ',', ' ') }} €</td>
                    </tr>
                    @if($total_remise > 0)
                        <tr class="row-remise">
                            <td>Remise</td>
                            <td>– {{ number_format($total_remise, 2, ',', ' ') }} €</td>
                        </tr>
                    @endif
                    <tr class="row-total-ttc">
                        <td>Total TTC</td>
                        <td>{{ number_format($total_ttc, 2, ',', ' ') }} €</td>
                    </tr>
                    @if(isset($acompte) && $acompte > 0)
                        <tr class="row-acompte">
                            <td>Acompte</td>
                            <td>{{ number_format($acompte, 2, ',', ' ') }} €</td>
                        </tr>
                        <tr class="row-a-payer">
                            <td>A Payer</td>
                            <td>{{ number_format($total_ttc - $acompte, 2, ',', ' ') }} €</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════
         COORDONNÉES BANCAIRES (si virement)
    ══════════════════════════════════════════════ --}}
    @if($magasinEmetteur?->iban || ($facture->mode_paiement && strtolower($facture->mode_paiement) == 'virement'))
        <table class="bank-section" style="width:100%; border-collapse:collapse; margin-bottom:14px;">
            <tr>
                <td style="padding:0;">
                    <div class="bank-header">Virement au compte :</div>
                    <div class="bank-body">
                        @if($magasinEmetteur?->banque_domiciliation)
                            <div style="margin-bottom:6px;">Domiciliation : {{ $magasinEmetteur->banque_domiciliation }}</div>
                        @endif
                        <table class="bank-grid">
                            <tr>
                                <th>IBAN :</th>
                                <td colspan="3">{{ $magasinEmetteur?->iban ?? '—' }}</td>
                                <th>BIC :</th>
                                <td>{{ $magasinEmetteur?->bic ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Banque</th>
                                <th>Guichet</th>
                                <th>Compte</th>
                                <th>Clé RIB</th>
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
                    </div>
                </td>
            </tr>
        </table>
    @endif

    {{-- ══════════════════════════════════════════════
         NOTE DE BAS DE PAGE
    ══════════════════════════════════════════════ --}}
    <div class="footer-note">
        @if($facture->notes_bas ?? false)
            {{ $facture->notes_bas }}
        @else
            En cas de retard de paiement, application d'une indemnité forfaitaire pour frais de recouvrement de 40€ selon l'article D. 441-5 du code du commerce. Taux des pénalités de retard : 4%. Pas d'escompte pour règlement anticipé.
        @endif
        <br>
        Document généré le {{ $date_impression }} — Facture N° {{ $facture->numero ?? $facture->id }}
    </div>

</div>
</body>
</html>
