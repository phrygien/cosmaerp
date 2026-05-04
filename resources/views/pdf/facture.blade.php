<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture {{ $facture->numero ?? $facture->id }}</title>

    @php
        $logoB64 = null;
        $logoPath = public_path('cosma.png');
        if (file_exists($logoPath)) {
            $logoB64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $fontRegular = public_path('fonts/open-sans/OpenSans-Regular.ttf');
        $fontBold    = public_path('fonts/open-sans/OpenSans-Bold.ttf');
        $fontItalic  = public_path('fonts/open-sans/OpenSans-Italic.ttf');

        // Couleurs du modèle
        $blue   = '#1565C0';   // bleu foncé entêtes
        $lblue  = '#1E88E5';   // bleu moyen liens/texte émetteur
        $yellow = '#F9A825';   // jaune logo accent
        $white  = '#ffffff';
        $dgrey  = '#333333';
        $lgrey  = '#f2f2f2';
        $border = '#cccccc';
    @endphp

    <style>
        @page { size: A4 portrait; margin: 20px 0; }

        @font-face {
            font-family: 'OS';
            font-style: normal; font-weight: 400;
            src: url('{{ $fontRegular }}') format('truetype');
        }
        @font-face {
            font-family: 'OS';
            font-style: normal; font-weight: 700;
            src: url('{{ $fontBold }}') format('truetype');
        }
        @font-face {
            font-family: 'OS';
            font-style: italic; font-weight: 400;
            src: url('{{ $fontItalic }}') format('truetype');
        }

        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'OS', DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
            background: #fff;
        }

        /* ── utilitaires ── */
        .h5  { height:5px;  font-size:0; line-height:0; }
        .h8  { height:8px;  font-size:0; line-height:0; }
        .h12 { height:12px; font-size:0; line-height:0; }
        .h16 { height:16px; font-size:0; line-height:0; }
        .h20 { height:20px; font-size:0; line-height:0; }
        .h30 { height:30px; font-size:0; line-height:0; }

        /* ── en-tête bleu ── */
        .th-blue {
            background: {{ $blue }};
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 5px 8px;
            vertical-align: middle;
            text-align: center;
            letter-spacing: 0.5px;
        }

        .th-blue-left {
            background: {{ $blue }};
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 5px 8px;
            vertical-align: middle;
            text-align: left;
            letter-spacing: 0.5px;
        }

        /* ── cellule donnée (lignes claires alternées) ── */
        .td-data {
            font-size: 11px;
            color: #333;
            padding: 4px 8px;
            vertical-align: middle;
            border-bottom: 1px solid {{ $border }};
            border-right: 1px solid {{ $border }};
        }

        .td-data-center {
            font-size: 11px;
            color: #333;
            padding: 4px 8px;
            vertical-align: middle;
            text-align: center;
            border-bottom: 1px solid {{ $border }};
            border-right: 1px solid {{ $border }};
        }

        .td-data-right {
            font-size: 11px;
            color: #333;
            padding: 4px 8px;
            vertical-align: middle;
            text-align: right;
            border-bottom: 1px solid {{ $border }};
            border-right: 1px solid {{ $border }};
        }

        /* ── texte émetteur bleu ── */
        .emit-txt {
            font-size: 11px;
            color: {{ $lblue }};
            line-height: 18px;
            vertical-align: top;
        }

        /* ── totaux ── */
        .tot-lbl {
            background: {{ $blue }};
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 6px 10px;
            vertical-align: middle;
            text-align: left;
            letter-spacing: 0.3px;
        }

        .tot-val {
            font-size: 11px;
            color: #333;
            padding: 6px 10px;
            vertical-align: middle;
            text-align: right;
            border: 1px solid {{ $border }};
            white-space: nowrap;
        }

        .tot-val-big {
            font-size: 13px;
            font-weight: 700;
            color: #333;
            padding: 6px 10px;
            vertical-align: middle;
            text-align: right;
            border: 1px solid {{ $border }};
            white-space: nowrap;
        }

        /* ── note footer ── */
        .footer-italic {
            font-size: 10px;
            color: {{ $lblue }};
            font-style: italic;
            text-align: center;
            line-height: 18px;
        }

        .footer-url {
            font-size: 10px;
            color: {{ $blue }};
            text-align: center;
        }
    </style>
</head>
<body>

{{-- ════════════════════════════════════
     EN-TÊTE : Logo + Titre FACTURE
════════════════════════════════════ --}}
<table width="560" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr><td class="h20"></td></tr>
    <tr>
        <td>
            <table width="520" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr>
                    {{-- Logo gauche --}}
                    <td width="260" style="vertical-align:middle;">
                        @if(!empty($logoB64))
                            <img src="{{ $logoB64 }}" height="55" alt="logo" border="0"
                                 style="display:block;" />
                        @else
                            <span style="font-size:28px; font-weight:700; color:{{ $yellow }};
                                         font-style:italic; font-family:'OS',DejaVu Sans,sans-serif;">
                                {{ $magasinEmetteur?->nom ?? config('app.name', 'Your Logo') }}
                            </span>
                        @endif
                    </td>
                    {{-- FACTURE droite --}}
                    <td width="260" style="vertical-align:middle; text-align:right;">
                        <span style="font-size:28px; font-weight:700; color:{{ $blue }};
                                     letter-spacing:1px; font-family:'OS',DejaVu Sans,sans-serif;">
                            FACTURE
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td class="h16"></td></tr>
</table>

{{-- ════════════════════════════════════
     BLOC INFO : Émetteur + N°/Date/Client
════════════════════════════════════ --}}
<table width="560" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr>
        <td>
            <table width="520" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr>
                    {{-- Colonne gauche : coordonnées émetteur --}}
                    <td width="255" style="vertical-align:top;">
                        <table border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="emit-txt">
                                    {{ $magasinEmetteur?->nom ?? config('app.name') }}<br>
                                    @if($magasinEmetteur?->adresse)
                                        {{ $magasinEmetteur->adresse }}<br>
                                    @endif
                                    @if($magasinEmetteur?->code_postal || $magasinEmetteur?->ville)
                                        {{ $magasinEmetteur?->code_postal }} {{ $magasinEmetteur?->ville }}<br>
                                    @endif
                                    @if($magasinEmetteur?->telephone)
                                        {{ $magasinEmetteur->telephone }}<br>
                                    @endif
                                    @if($magasinEmetteur?->email)
                                        {{ $magasinEmetteur->email }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>

                    {{-- Colonne droite : grille N° facture / date / client / modalités --}}
                    <td width="255" style="vertical-align:top;">
                        <table width="255" border="0" cellpadding="0" cellspacing="0"
                               style="border-collapse:collapse; border:1px solid {{ $border }};">
                            {{-- Entêtes --}}
                            <tr>
                                <td width="128" class="th-blue">N&deg; DE FACTURE</td>
                                <td width="127" class="th-blue">DATE</td>
                            </tr>
                            {{-- Valeurs --}}
                            <tr>
                                <td class="td-data-center">
                                    {{ $facture->numero ?? str_pad($facture->id, 6, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="td-data-center">
                                    {{ $facture->date_facture
                                        ? \Carbon\Carbon::parse($facture->date_facture)->format('d/m/Y')
                                        : $date_impression }}
                                </td>
                            </tr>
                            {{-- Entêtes ligne 2 --}}
                            <tr>
                                <td class="th-blue">ID CLIENT</td>
                                <td class="th-blue">MODALIT&Eacute;S</td>
                            </tr>
                            {{-- Valeurs --}}
                            <tr>
                                <td class="td-data-center">
                                    {{ $fournisseur?->code ?? $fournisseur?->id ?? '&mdash;' }}
                                </td>
                                <td class="td-data-center">
                                    {{ $facture->conditions_paiement ?? 'Net 30 jours' }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td class="h16"></td></tr>
</table>

{{-- ════════════════════════════════════
     FACTURE POUR + EXPÉDITION À
════════════════════════════════════ --}}
<table width="560" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr>
        <td>
            <table width="520" border="0" cellpadding="0" cellspacing="0" align="center"
                   style="border-collapse:collapse;">
                <tr>
                    {{-- Facture pour --}}
                    <td width="255" style="vertical-align:top; padding-right:10px;">
                        <table width="255" border="0" cellpadding="0" cellspacing="0"
                               style="border-collapse:collapse; border:1px solid {{ $border }};">
                            <tr>
                                <td class="th-blue-left" colspan="1">FACTURE POUR :</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 10px; font-size:11px; color:#333; line-height:18px; vertical-align:top;">
                                    @if($fournisseur?->contact)
                                        ATTN : {{ $fournisseur->contact }}<br>
                                    @endif
                                    {{ $fournisseur?->name ?? $fournisseur?->raison_social ?? '&mdash;' }}<br>
                                    @if($fournisseur?->adresse_siege)
                                        {{ $fournisseur->adresse_siege }}<br>
                                    @endif
                                    @if($fournisseur?->code_postal || $fournisseur?->ville)
                                        {{ $fournisseur?->code_postal }} {{ $fournisseur?->ville }}<br>
                                    @endif
                                    @if($fournisseur?->telephone)
                                        {{ $fournisseur->telephone }}<br>
                                    @endif
                                    @if($fournisseur?->email)
                                        {{ $fournisseur->email }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>

                    {{-- Expédition à --}}
                    <td width="255" style="vertical-align:top;">
                        <table width="255" border="0" cellpadding="0" cellspacing="0"
                               style="border-collapse:collapse; border:1px solid {{ $border }};">
                            <tr>
                                <td class="th-blue-left">EXP&Eacute;DITION &Agrave; :</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 10px; font-size:11px; color:#333; line-height:18px; vertical-align:top;">
                                    @if($magasin?->nom)
                                        {{ $magasin->nom }}<br>
                                    @endif
                                    @if($magasin?->adress)
                                        {{ $magasin->adress }}<br>
                                    @endif
                                    @if($magasin?->code_postal || $magasin?->ville)
                                        {{ $magasin?->code_postal }} {{ $magasin?->ville }}<br>
                                    @endif
                                    @if($magasin?->telephone)
                                        {{ $magasin->telephone }}
                                    @endif
                                    @if(!$magasin)
                                        {{-- fallback : même adresse que fournisseur --}}
                                        {{ $fournisseur?->name ?? '&mdash;' }}<br>
                                        @if($fournisseur?->adresse_siege)
                                            {{ $fournisseur->adresse_siege }}<br>
                                        @endif
                                        @if($fournisseur?->code_postal || $fournisseur?->ville)
                                            {{ $fournisseur?->code_postal }} {{ $fournisseur?->ville }}<br>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td class="h16"></td></tr>
</table>

{{-- ════════════════════════════════════
     TABLEAU DES ARTICLES
════════════════════════════════════ --}}
<table width="560" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr>
        <td>
            <table width="520" border="0" cellpadding="0" cellspacing="0" align="center"
                   style="border-collapse:collapse; border:1px solid {{ $border }};">
                {{-- Entête --}}
                <tr>
                    <td width="34%" class="th-blue-left">DESCRIPTION</td>
                    <td width="12%" class="th-blue">R&Eacute;F.</td>
                    <td width="7%"  class="th-blue">QT&Eacute;</td>
                    <td width="7%"  class="th-blue">TVA</td>
                    <td width="14%" class="th-blue">P.U. HT</td>
                    <td width="12%" class="th-blue">REMISE</td>
                    <td width="14%" class="th-blue">MONTANT</td>
                </tr>

                {{-- Lignes articles --}}
                @forelse($lignes as $i => $ligne)
                    <tr bgcolor="{{ $i % 2 === 0 ? '#ffffff' : '#f7f7f7' }}">
                        <td class="td-data">
                            <strong style="color:#1565C0;">{{ $ligne['designation'] }}</strong>
                        </td>
                        <td class="td-data-center" style="font-size:10px; color:#666;">
                            {{ $ligne['article'] ?? '&mdash;' }}
                        </td>
                        <td class="td-data-center">
                            {{ number_format($ligne['qte'], 0, ',', ' ') }}
                        </td>
                        <td class="td-data-center">
                            {{ number_format($ligne['tva'], 0) }}%
                        </td>
                        <td class="td-data-right">
                            {{ number_format($ligne['pu_ht'], 2, ',', ' ') }} &euro;
                        </td>
                        <td class="td-data-center" style="color:#cc0000;">
                            @if($ligne['taux_remise'] > 0)
                                {{ number_format($ligne['taux_remise'], 1) }}%
                                @else
                                    &mdash;
                            @endif
                        </td>
                        <td class="td-data-right" style="font-weight:600;">
                            {{ number_format($ligne['montant_net_ht'], 2, ',', ' ') }} &euro;
                        </td>
                    </tr>
                @empty
                    {{-- Lignes vides comme sur le modèle --}}
                    @for($i = 0; $i < 6; $i++)
                        <tr>
                            <td class="td-data">&nbsp;</td>
                            <td class="td-data-center">&nbsp;</td>
                            <td class="td-data-center">&nbsp;</td>
                            <td class="td-data-center">&nbsp;</td>
                            <td class="td-data-right">&nbsp;</td>
                            <td class="td-data-center">&nbsp;</td>
                            <td class="td-data-right">&nbsp;</td>
                        </tr>
                    @endfor
                @endforelse

                {{-- Lignes vides padding (au moins 3 lignes vides sous les articles) --}}
                @for($i = 0; $i < 3; $i++)
                    <tr>
                        <td class="td-data">&nbsp;</td>
                        <td class="td-data-center">&nbsp;</td>
                        <td class="td-data-center">&nbsp;</td>
                        <td class="td-data-center">&nbsp;</td>
                        <td class="td-data-right">&nbsp;</td>
                        <td class="td-data-center">&nbsp;</td>
                        <td class="td-data-right">&nbsp;</td>
                    </tr>
                @endfor
            </table>
        </td>
    </tr>
    <tr><td class="h12"></td></tr>
</table>

{{-- ════════════════════════════════════
     REMARQUES + TOTAUX (côte à côte)
════════════════════════════════════ --}}
@php
    $tvaGroupes = $lignes->groupBy('tva')->map(function($group) {
        $baseHT     = $group->sum('montant_net_ht');
        $tva        = $group->first()['tva'];
        $montantTVA = $baseHT * ($tva / 100);
        return ['taux' => $tva, 'base_ht' => $baseHT, 'montant_tva' => $montantTVA];
    });
@endphp
<table width="560" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr>
        <td>
            <table width="520" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr style="vertical-align:top;">

                    {{-- Gauche : remarques + MERCI --}}
                    <td width="245" style="vertical-align:top; padding-right:10px;">
                        <table border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="font-size:11px; color:#333; line-height:1;">
                                    Remarques / instructions :
                                </td>
                            </tr>
                            <tr><td class="h8"></td></tr>
                            <tr>
                                <td style="font-size:11px; color:#555; line-height:16px; font-style:italic;">
                                    @if($facture->notes ?? false)
                                        {{ $facture->notes }}
                                    @else
                                        &nbsp;
                                    @endif
                                </td>
                            </tr>
                            <tr><td class="h30"></td></tr>
                            {{-- MERCI --}}
                            <tr>
                                <td style="font-size:20px; font-weight:700; color:{{ $blue }};
                                           text-align:center; letter-spacing:1px;">
                                    MERCI
                                </td>
                            </tr>
                        </table>
                    </td>

                    {{-- Droite : tableau totaux --}}
                    <td width="265" style="vertical-align:top;">
                        <table width="265" border="0" cellpadding="0" cellspacing="0"
                               style="border-collapse:collapse;">

                            {{-- Sous-total HT --}}
                            <tr>
                                <td width="170" class="tot-lbl">SOUS-TOTAL HT</td>
                                <td width="95"  class="tot-val">
                                    {{ number_format($total_ht, 2, ',', ' ') }} &euro;
                                </td>
                            </tr>

                            {{-- Remise globale --}}
                            @if($total_remise > 0)
                                <tr>
                                    <td class="tot-lbl">REMISE</td>
                                    <td class="tot-val" style="color:#cc0000;">
                                        &minus; {{ number_format($total_remise, 2, ',', ' ') }} &euro;
                                    </td>
                                </tr>
                            @endif

                            {{-- TVA par taux --}}
                            @foreach($tvaGroupes as $recap)
                                <tr>
                                    <td class="tot-lbl">
                                        TVA ({{ number_format($recap['taux'], 1) }}%)
                                    </td>
                                    <td class="tot-val">
                                        {{ number_format($recap['montant_tva'], 2, ',', ' ') }} &euro;
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Acompte --}}
                            @if(isset($acompte) && $acompte > 0)
                                <tr>
                                    <td class="tot-lbl">ACOMPTE VERS&Eacute;</td>
                                    <td class="tot-val" style="color:#cc0000;">
                                        &minus; {{ number_format($acompte, 2, ',', ' ') }} &euro;
                                    </td>
                                </tr>
                            @endif

                            {{-- Mode paiement --}}
                            <tr>
                                <td class="tot-lbl">MODE DE PAIEMENT</td>
                                <td class="tot-val" style="font-size:10px;">
                                    {{ $facture->mode_paiement ?? '&mdash;' }}
                                </td>
                            </tr>

                            {{-- TOTAL TTC --}}
                            <tr>
                                <td class="tot-lbl" style="font-size:13px; padding:8px 10px;">TOTAL TTC</td>
                                <td class="tot-val-big">
                                    &euro;&nbsp;{{ number_format($total_ttc, 2, ',', ' ') }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td class="h20"></td></tr>
</table>

{{-- ════════════════════════════════════
     COORDONNÉES BANCAIRES
════════════════════════════════════ --}}
@if($magasinEmetteur?->iban)
    <table width="560" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
        <tr>
            <td>
                <table width="520" border="0" cellpadding="0" cellspacing="0" align="center"
                       style="border-collapse:collapse; border:1px solid {{ $border }};">
                    <tr>
                        <td class="th-blue-left" colspan="6">VIREMENT BANCAIRE</td>
                    </tr>
                    <tr>
                        <td style="font-size:10px; font-weight:700; color:#666; text-transform:uppercase; padding:4px 8px; vertical-align:top;">IBAN</td>
                        <td style="font-size:10px; font-weight:700; color:#666; text-transform:uppercase; padding:4px 8px; vertical-align:top;">BIC</td>
                        <td style="font-size:10px; font-weight:700; color:#666; text-transform:uppercase; padding:4px 8px; vertical-align:top;">Banque</td>
                        <td style="font-size:10px; font-weight:700; color:#666; text-transform:uppercase; padding:4px 8px; vertical-align:top;">Guichet</td>
                        <td style="font-size:10px; font-weight:700; color:#666; text-transform:uppercase; padding:4px 8px; vertical-align:top;">Compte</td>
                        <td style="font-size:10px; font-weight:700; color:#666; text-transform:uppercase; padding:4px 8px; vertical-align:top;">Cl&eacute; RIB</td>
                    </tr>
                    <tr>
                        <td style="font-size:11px; font-weight:700; color:#333; padding:4px 8px; border-top:1px solid {{ $border }};">{{ $magasinEmetteur?->iban ?? '&mdash;' }}</td>
                        <td style="font-size:11px; font-weight:700; color:#333; padding:4px 8px; border-top:1px solid {{ $border }};">{{ $magasinEmetteur?->bic ?? '&mdash;' }}</td>
                        <td style="font-size:11px; font-weight:700; color:#333; padding:4px 8px; border-top:1px solid {{ $border }};">{{ $magasinEmetteur?->banque_code ?? '&mdash;' }}</td>
                        <td style="font-size:11px; font-weight:700; color:#333; padding:4px 8px; border-top:1px solid {{ $border }};">{{ $magasinEmetteur?->banque_guichet ?? '&mdash;' }}</td>
                        <td style="font-size:11px; font-weight:700; color:#333; padding:4px 8px; border-top:1px solid {{ $border }};">{{ $magasinEmetteur?->banque_compte ?? '&mdash;' }}</td>
                        <td style="font-size:11px; font-weight:700; color:#333; padding:4px 8px; border-top:1px solid {{ $border }};">{{ $magasinEmetteur?->banque_cle ?? '&mdash;' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><td class="h16"></td></tr>
    </table>
@endif

{{-- ════════════════════════════════════
     FOOTER
════════════════════════════════════ --}}
<table width="560" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr>
        <td>
            <table width="520" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr>
                    <td class="footer-italic">
                        En cas de questions concernant cette facture, veuillez contacter<br>
                        {{ $magasinEmetteur?->nom ?? config('app.name') }}
                        @if($magasinEmetteur?->telephone)
                            , {{ $magasinEmetteur->telephone }}
                        @endif
                        @if($magasinEmetteur?->email)
                            , {{ $magasinEmetteur->email }}
                        @endif
                    </td>
                </tr>
                <tr><td class="h8"></td></tr>
                @if($magasinEmetteur?->site_web ?? false)
                    <tr>
                        <td class="footer-url">{{ $magasinEmetteur->site_web }}</td>
                    </tr>
                @endif
                <tr><td class="h8"></td></tr>
                <tr>
                    <td style="font-size:10px; color:#aaa; text-align:center; font-style:italic;">
                        Facture N&deg; {{ $facture->numero ?? $facture->id }}
                        &mdash; Imprim&eacute;e le {{ $date_impression }}
                        @if($magasinEmetteur?->siret)
                            &mdash; SIRET : {{ $magasinEmetteur->siret }}
                        @endif
                        @if($magasinEmetteur?->num_tva)
                            &mdash; TVA : {{ $magasinEmetteur->num_tva }}
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td class="h20"></td></tr>
</table>

</body>
</html>
