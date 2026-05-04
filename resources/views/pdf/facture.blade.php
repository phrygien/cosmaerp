<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture {{ $facture->numero ?? $facture->id }}</title>

    @php
        // ── Images en base64 (seule méthode fiable avec DomPDF) ─────────
        $logoB64 = null;
        $logoPath = public_path('cosma.png');
        if (file_exists($logoPath)) {
            $logoB64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        // ── Chemins absolus pour les polices ────────────────────────────
        $fontRegular = public_path('fonts/open-sans/OpenSans-Regular.ttf');
        $fontBold    = public_path('fonts/open-sans/OpenSans-Bold.ttf');
        $fontItalic  = public_path('fonts/open-sans/OpenSans-Italic.ttf');
    @endphp

    <style>
        @page {
            size: A4 portrait;
            margin: 30px 0;
        }

        @font-face {
            font-family: 'OpenSans';
            font-style: normal;
            font-weight: 400;
            src: url('{{ $fontRegular }}') format('truetype');
        }
        @font-face {
            font-family: 'OpenSans';
            font-style: normal;
            font-weight: 700;
            src: url('{{ $fontBold }}') format('truetype');
        }
        @font-face {
            font-family: 'OpenSans';
            font-style: italic;
            font-weight: 400;
            src: url('{{ $fontItalic }}') format('truetype');
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'OpenSans', DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #5b5b5b;
            background: #e1e1e1;
            line-height: 1.5;
        }

        /* ── separateurs ── */
        .sep       { height:1px; background:#bebebe; font-size:0; line-height:0; }
        .sep-light { border-bottom:1px solid #e4e4e4; font-size:0; line-height:0; }

        /* ── article cells ── */
        .art-name  { font-size:12px; color:#ff0000; line-height:18px; vertical-align:top; padding:10px 0; }
        .art-sku   { font-size:11px; color:#646a6e; line-height:18px; vertical-align:top; padding:10px 0; }
        .art-qty   { font-size:12px; color:#646a6e; line-height:18px; vertical-align:top; padding:10px 0; text-align:center; }
        .art-tva   { font-size:12px; color:#646a6e; line-height:18px; vertical-align:top; padding:10px 0; text-align:center; }
        .art-price { font-size:12px; color:#1e2b33; line-height:18px; vertical-align:top; padding:10px 0; text-align:right; white-space:nowrap; }

        /* ── totaux ── */
        .tot-lbl      { font-size:12px; color:#646a6e; line-height:22px; vertical-align:top; text-align:right; }
        .tot-val      { font-size:12px; color:#646a6e; line-height:22px; vertical-align:top; text-align:right; white-space:nowrap; width:100px; }
        .tot-lbl-big  { font-size:12px; color:#000; font-weight:700; line-height:22px; vertical-align:top; text-align:right; }
        .tot-val-big  { font-size:12px; color:#000; font-weight:700; line-height:22px; vertical-align:top; text-align:right; white-space:nowrap; width:100px; }
        .tot-lbl-tva  { font-size:11px; color:#b0b0b0; line-height:22px; vertical-align:top; text-align:right; }
        .tot-val-tva  { font-size:11px; color:#b0b0b0; line-height:22px; vertical-align:top; text-align:right; white-space:nowrap; }
        .tot-lbl-rem  { font-size:12px; color:#ff0000; line-height:22px; vertical-align:top; text-align:right; }
        .tot-val-rem  { font-size:12px; color:#ff0000; line-height:22px; vertical-align:top; text-align:right; white-space:nowrap; }

        /* ── info blocks ── */
        .info-lbl  { font-size:11px; color:#5b5b5b; line-height:1; vertical-align:top; font-weight:700; text-transform:uppercase; }
        .info-val  { font-size:12px; color:#5b5b5b; line-height:20px; vertical-align:top; }

        /* ── banque ── */
        .bank-lbl  { font-size:10px; font-weight:700; color:#b0b0b0; text-transform:uppercase; padding:4px 8px 4px 0; vertical-align:top; text-align:left; }
        .bank-val  { font-size:11px; font-weight:700; color:#1e2b33; padding:4px 8px 4px 0; vertical-align:top; }

        /* ── note legale ── */
        .alert-legal { background:#f9f9f9; border-left:4px solid #e4e4e4; padding:12px 16px; font-size:11px; color:#b0b0b0; line-height:18px; font-style:italic; }

        /* ── arrêté ── */
        .arrete      { border-left:4px solid #ff0000; padding:8px 14px; font-size:11px; color:#5b5b5b; }
        .arrete strong { color:#1e2b33; font-size:10px; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:2px; }

        /* ── signature ── */
        .sig-box { border:1px solid #ccc; padding:10px 14px; height:55px; font-size:10px; color:#aaa; text-align:center; width:180px; }

        /* ── footer ── */
        .footer-text { font-size:12px; color:#5b5b5b; line-height:18px; vertical-align:top; }
        .footer-note { font-size:10px; color:#b0b0b0; line-height:1.6; font-style:italic; }

        /* ── spacers ── */
        .h10 { height:10px; font-size:0; line-height:0; }
        .h20 { height:20px; font-size:0; line-height:0; }
        .h30 { height:30px; font-size:0; line-height:0; }
        .h40 { height:40px; font-size:0; line-height:0; }
        .h50 { height:50px; font-size:0; line-height:0; }
    </style>
</head>
<body>

{{-- ════════════════════════════════════
     EN-TÊTE
════════════════════════════════════ --}}
<table width="560" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr><td class="h40"></td></tr>
    <tr>
        <td>
            <table width="460" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr>
                    {{-- Gauche : logo + coordonnées émetteur --}}
                    <td width="220" style="vertical-align:top;">
                        <table border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    @if(!empty($logoB64))
                                        <img src="{{ $logoB64 }}" width="36" height="36" alt="logo" border="0" style="display:block;" />
                                    @else
                                        <span style="font-size:20px; font-weight:700; color:#ff0000;">
                                            {{ strtoupper(substr($magasinEmetteur?->nom ?? config('app.name', 'CO'), 0, 2)) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            <tr><td class="h40"></td></tr>
                            <tr>
                                <td style="font-size:12px; color:#5b5b5b; line-height:18px; vertical-align:top; text-align:left;">
                                    {{ $magasinEmetteur?->nom ?? config('app.name') }}<br>
                                    @if($magasinEmetteur?->adresse)
                                        {{ $magasinEmetteur->adresse }}<br>
                                        {{ $magasinEmetteur?->code_postal }} {{ $magasinEmetteur?->ville }}<br>
                                    @endif
                                    @if($magasinEmetteur?->siret)
                                        SIRET : {{ $magasinEmetteur->siret }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>

                    {{-- Droite : "Facture" + numéro + date --}}
                    <td width="220" style="vertical-align:top; text-align:right;">
                        <table border="0" cellpadding="0" cellspacing="0" align="right">
                            <tr><td class="h10"></td></tr>
                            <tr>
                                <td style="font-size:21px; color:#ff0000; letter-spacing:-1px; line-height:1; vertical-align:top; text-align:right;">
                                    Facture
                                </td>
                            </tr>
                            <tr><td class="h50"></td></tr>
                            <tr>
                                <td style="font-size:12px; color:#5b5b5b; line-height:18px; vertical-align:top; text-align:right;">
                                    <small>FACTURE</small>
                                    N&deg; {{ $facture->numero ?? str_pad($facture->id, 7, '0', STR_PAD_LEFT) }}<br>
                                    @if($commande?->numero_commande)
                                        <small>COMMANDE</small> {{ $commande->numero_commande }}<br>
                                    @endif
                                    <small>{{ strtoupper($date_impression) }}</small>
                                    @if($facture->date_echeance)
                                        <br><small>ECH&Eacute;ANCE :</small>
                                        {{ \Carbon\Carbon::parse($facture->date_echeance)->format('d/m/Y') }}
                                    @endif
                                    @if($facture->statut)
                                        <br><small>STATUT :</small> {{ strtoupper($facture->statut) }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td class="h40"></td></tr>
</table>

{{-- ════════════════════════════════════
     ADRESSES
════════════════════════════════════ --}}
<table width="560" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr>
        <td>
            <table width="460" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr>
                    <td width="220" style="vertical-align:top;">
                        <table border="0" cellpadding="0" cellspacing="0">
                            <tr><td class="info-lbl"><strong>INFORMATIONS &Eacute;METTEUR</strong></td></tr>
                            <tr><td class="h10"></td></tr>
                            <tr>
                                <td class="info-val">
                                    {{ $magasinEmetteur?->nom ?? config('app.name') }}<br>
                                    @if($magasinEmetteur?->adresse)
                                        {{ $magasinEmetteur->adresse }}<br>
                                    @endif
                                    @if($magasinEmetteur?->code_postal || $magasinEmetteur?->ville)
                                        {{ $magasinEmetteur?->code_postal }} {{ $magasinEmetteur?->ville }}<br>
                                    @endif
                                    @if($magasinEmetteur?->telephone)
                                        T : {{ $magasinEmetteur->telephone }}<br>
                                    @endif
                                    @if($magasinEmetteur?->email)
                                        {{ $magasinEmetteur->email }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td width="220" style="vertical-align:top; text-align:right;">
                        <table border="0" cellpadding="0" cellspacing="0" align="right">
                            <tr><td class="info-lbl" style="text-align:right;"><strong>FOURNISSEUR / LIVRAISON</strong></td></tr>
                            <tr><td class="h10"></td></tr>
                            <tr>
                                <td class="info-val" style="text-align:right;">
                                    {{ $fournisseur?->name ?? $fournisseur?->raison_social ?? '&mdash;' }}<br>
                                    @if($fournisseur?->adresse_siege)
                                        {{ $fournisseur->adresse_siege }}<br>
                                    @endif
                                    @if($fournisseur?->code_postal || $fournisseur?->ville)
                                        {{ $fournisseur?->code_postal }} {{ $fournisseur?->ville }}<br>
                                    @endif
                                    @if($fournisseur?->telephone)
                                        T : {{ $fournisseur->telephone }}<br>
                                    @endif
                                    @if($magasin?->adress)
                                        Livraison : {{ $magasin->adress }},
                                        {{ $magasin?->code_postal }} {{ $magasin?->ville }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td class="h40"></td></tr>
</table>

{{-- ════════════════════════════════════
     LIGNES DE FACTURE
════════════════════════════════════ --}}
<table width="560" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr>
        <td>
            <table width="460" border="0" cellpadding="0" cellspacing="0" align="center">
                {{-- En-têtes --}}
                <tr>
                    <th width="34%" style="font-size:12px; color:#5b5b5b; font-weight:normal; line-height:1; vertical-align:top; padding:0 10px 7px 0; text-align:left;">D&eacute;signation</th>
                    <th width="13%" style="font-size:12px; color:#5b5b5b; font-weight:normal; line-height:1; vertical-align:top; padding:0 0 7px; text-align:left;"><small>R&eacute;f.</small></th>
                    <th width="8%"  style="font-size:12px; color:#5b5b5b; font-weight:normal; line-height:1; vertical-align:top; padding:0 0 7px; text-align:center;">Qt&eacute;</th>
                    <th width="8%"  style="font-size:12px; color:#5b5b5b; font-weight:normal; line-height:1; vertical-align:top; padding:0 0 7px; text-align:center;">TVA</th>
                    <th width="18%" style="font-size:12px; color:#5b5b5b; font-weight:normal; line-height:1; vertical-align:top; padding:0 0 7px; text-align:right;">P.U. HT</th>
                    <th width="19%" style="font-size:12px; color:#1e2b33; font-weight:normal; line-height:1; vertical-align:top; padding:0 0 7px; text-align:right;">Total HT</th>
                </tr>
                <tr><td class="sep" colspan="6"></td></tr>
                <tr><td class="h10" colspan="6"></td></tr>

                {{-- Lignes --}}
                @forelse($lignes as $ligne)
                    <tr>
                        <td class="art-name"><strong>{{ $ligne['designation'] }}</strong></td>
                        <td class="art-sku"><small>{{ $ligne['article'] ?? '&mdash;' }}</small></td>
                        <td class="art-qty">{{ number_format($ligne['qte'], 0, ',', ' ') }}</td>
                        <td class="art-tva">{{ number_format($ligne['tva'], 0) }}%</td>
                        <td class="art-price">{{ number_format($ligne['pu_ht'], 2, ',', ' ') }} &euro;</td>
                        <td class="art-price">
                            {{ number_format($ligne['montant_net_ht'], 2, ',', ' ') }} &euro;
                            @if($ligne['taux_remise'] > 0)
                                <br><span style="color:#ff0000; font-size:10px;">&minus;{{ number_format($ligne['taux_remise'], 1) }}%</span>
                            @endif
                        </td>
                    </tr>
                    <tr><td class="sep-light" colspan="6"></td></tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:20px 0; text-align:center; color:#aaa; font-style:italic; font-size:12px;">
                            Aucune ligne de facture.
                        </td>
                    </tr>
                @endforelse
            </table>
        </td>
    </tr>
    <tr><td class="h20"></td></tr>
</table>

{{-- ════════════════════════════════════
     RECAP TVA
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
            <table width="460" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr>
                    <th style="font-size:10px; color:#b0b0b0; font-weight:700; text-transform:uppercase; padding:4px 10px 4px 0; text-align:left; vertical-align:top;">Taux TVA</th>
                    <th style="font-size:10px; color:#b0b0b0; font-weight:700; text-transform:uppercase; padding:4px 10px 4px 0; text-align:right; vertical-align:top;">Base HT</th>
                    <th style="font-size:10px; color:#b0b0b0; font-weight:700; text-transform:uppercase; padding:4px 10px 4px 0; text-align:right; vertical-align:top;">TVA</th>
                    <th style="font-size:10px; color:#b0b0b0; font-weight:700; text-transform:uppercase; padding:4px 0; text-align:right; vertical-align:top;">Total TTC</th>
                </tr>
                @foreach($tvaGroupes as $recap)
                    <tr>
                        <td style="font-size:12px; color:#5b5b5b; padding:3px 10px 3px 0; vertical-align:top;">{{ number_format($recap['taux'], 0) }}%</td>
                        <td style="font-size:12px; color:#5b5b5b; padding:3px 10px 3px 0; text-align:right; vertical-align:top;">{{ number_format($recap['base_ht'], 2, ',', ' ') }} &euro;</td>
                        <td style="font-size:12px; color:#5b5b5b; padding:3px 10px 3px 0; text-align:right; vertical-align:top;">{{ number_format($recap['montant_tva'], 2, ',', ' ') }} &euro;</td>
                        <td style="font-size:12px; color:#1e2b33; font-weight:700; padding:3px 0; text-align:right; vertical-align:top;">{{ number_format($recap['base_ht'] + $recap['montant_tva'], 2, ',', ' ') }} &euro;</td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
    <tr><td class="h20"></td></tr>
</table>

{{-- ════════════════════════════════════
     TOTAUX
════════════════════════════════════ --}}
<table width="560" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr>
        <td>
            <table width="460" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr>
                    <td class="tot-lbl">Sous-total HT</td>
                    <td class="tot-val">{{ number_format($total_ht, 2, ',', ' ') }} &euro;</td>
                </tr>
                @if($total_remise > 0)
                    <tr>
                        <td class="tot-lbl-rem">Remise</td>
                        <td class="tot-val-rem">&minus; {{ number_format($total_remise, 2, ',', ' ') }} &euro;</td>
                    </tr>
                @endif
                <tr>
                    <td class="tot-lbl">Total net HT</td>
                    <td class="tot-val">{{ number_format($total_net_ht, 2, ',', ' ') }} &euro;</td>
                </tr>
                <tr>
                    <td class="tot-lbl-big"><strong>Total TTC (TVA incl.)</strong></td>
                    <td class="tot-val-big"><strong>{{ number_format($total_ttc, 2, ',', ' ') }} &euro;</strong></td>
                </tr>
                <tr>
                    <td class="tot-lbl-tva"><small>TVA</small></td>
                    <td class="tot-val-tva"><small>{{ number_format($total_tva, 2, ',', ' ') }} &euro;</small></td>
                </tr>
                @if(isset($acompte) && $acompte > 0)
                    <tr>
                        <td class="tot-lbl-tva"><small>Acompte vers&eacute;</small></td>
                        <td class="tot-val-tva"><small>&minus; {{ number_format($acompte, 2, ',', ' ') }} &euro;</small></td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
    <tr><td class="h30"></td></tr>
</table>

{{-- ════════════════════════════════════
     ARRÊTÉ + PAIEMENT + SIGNATURE
════════════════════════════════════ --}}
<table width="560" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr>
        <td>
            <table width="460" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr>
                    <td class="arrete">
                        <strong>Arr&ecirc;t&eacute;e la pr&eacute;sente facture &agrave; la somme de</strong>
                        {{ number_format($total_ttc, 2, ',', ' ') }} Euros TTC
                    </td>
                </tr>
                <tr><td class="h20"></td></tr>
                <tr>
                    <td>
                        <table width="100%" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="220" style="vertical-align:top;">
                                    <table border="0" cellpadding="0" cellspacing="0">
                                        <tr><td class="info-lbl"><strong>MODE DE PAIEMENT</strong></td></tr>
                                        <tr><td class="h10"></td></tr>
                                        <tr>
                                            <td class="info-val">
                                                {{ $facture->mode_paiement ?? '&mdash;' }}<br>
                                                @if($facture->conditions_paiement)
                                                    {{ $facture->conditions_paiement }}<br>
                                                @endif
                                                @if($facture->date_echeance)
                                                    Ech&eacute;ance :
                                                    {{ \Carbon\Carbon::parse($facture->date_echeance)->format('d/m/Y') }}
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td width="220" style="vertical-align:top; text-align:right;">
                                    <table border="0" cellpadding="0" cellspacing="0" align="right">
                                        <tr><td class="info-lbl" style="text-align:right;"><strong>SIGNATURE &amp; CACHET</strong></td></tr>
                                        <tr><td class="h10"></td></tr>
                                        <tr><td class="sig-box">&nbsp;</td></tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td class="h30"></td></tr>
</table>

{{-- ════════════════════════════════════
     COORDONNÉES BANCAIRES
════════════════════════════════════ --}}
@if($magasinEmetteur?->iban)
    <table width="560" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
        <tr>
            <td>
                <table width="460" border="0" cellpadding="0" cellspacing="0" align="center">
                    <tr><td class="info-lbl"><strong>VIREMENT BANCAIRE</strong></td></tr>
                    @if($magasinEmetteur?->banque_domiciliation)
                        <tr><td class="h10"></td></tr>
                        <tr><td class="info-val">Domiciliation : {{ $magasinEmetteur->banque_domiciliation }}</td></tr>
                    @endif
                    <tr><td class="h10"></td></tr>
                    <tr>
                        <td>
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td class="bank-lbl">IBAN</td>
                                    <td class="bank-lbl">BIC</td>
                                    <td class="bank-lbl">Banque</td>
                                    <td class="bank-lbl">Guichet</td>
                                    <td class="bank-lbl">Compte</td>
                                    <td class="bank-lbl">Cl&eacute; RIB</td>
                                </tr>
                                <tr>
                                    <td class="bank-val">{{ $magasinEmetteur?->iban ?? '&mdash;' }}</td>
                                    <td class="bank-val">{{ $magasinEmetteur?->bic ?? '&mdash;' }}</td>
                                    <td class="bank-val">{{ $magasinEmetteur?->banque_code ?? '&mdash;' }}</td>
                                    <td class="bank-val">{{ $magasinEmetteur?->banque_guichet ?? '&mdash;' }}</td>
                                    <td class="bank-val">{{ $magasinEmetteur?->banque_compte ?? '&mdash;' }}</td>
                                    <td class="bank-val">{{ $magasinEmetteur?->banque_cle ?? '&mdash;' }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><td class="h30"></td></tr>
    </table>
@endif

{{-- ════════════════════════════════════
     NOTE LÉGALE
════════════════════════════════════ --}}
<table width="560" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr>
        <td>
            <table width="460" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr>
                    <td class="alert-legal">
                        @if($facture->notes ?? false)
                            {{ $facture->notes }}
                        @else
                            En cas de retard de paiement, application d'une indemnit&eacute; forfaitaire pour frais
                            de recouvrement de 40 &euro; selon l'article D. 441-5 du code du commerce.
                            Taux des p&eacute;nalit&eacute;s de retard : 4 %. Pas d'escompte pour r&egrave;glement anticip&eacute;.
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td class="h30"></td></tr>
</table>

{{-- ════════════════════════════════════
     FOOTER
════════════════════════════════════ --}}
<table width="560" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr>
        <td>
            <table width="460" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr>
                    <td class="footer-text">
                        Document g&eacute;n&eacute;r&eacute; le {{ $date_impression }}
                        &mdash; Facture N&deg;&nbsp;{{ $facture->numero ?? $facture->id }}
                    </td>
                </tr>
                <tr><td class="h10"></td></tr>
                <tr>
                    <td class="footer-note">
                        @if($magasinEmetteur?->siret)
                            SIRET : {{ $magasinEmetteur->siret }}
                        @endif
                        @if($magasinEmetteur?->num_tva)
                            &nbsp;&mdash;&nbsp; TVA : {{ $magasinEmetteur->num_tva }}
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td class="h50"></td></tr>
</table>

</body>
</html>
