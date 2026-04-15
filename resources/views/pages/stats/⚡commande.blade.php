<?php

use Livewire\Component;
use App\Models\Commande;

new class extends Component
{
    public array $chartData = [];
    public int $totalCommandes = 0;
    public float $montantTotal = 0;
    public int $enAttente = 0;

    public function mount(): void
    {
        $this->totalCommandes = Commande::count();
        $this->montantTotal   = Commande::sum('montant_total');
        $this->enAttente      = Commande::where('status', 'en_attente')->count();

        $parMois = Commande::selectRaw("
                DATE_FORMAT(created_at, '%Y-%m') as mois,
                SUM(montant_total) as total
            ")
            ->whereYear('created_at', now()->year)
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderBy('mois')
            ->get()
            ->keyBy('mois');

        $parStatut = Commande::selectRaw("
                DATE_FORMAT(created_at, '%Y-%m') as mois,
                status,
                COUNT(*) as nb
            ")
            ->whereYear('created_at', now()->year)
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m'), status")
            ->orderBy('mois')
            ->get()
            ->groupBy('mois');

        $labels = $montants = $confirmes = $annules = $attente = [];

        for ($m = 1; $m <= 12; $m++) {
            $key = now()->year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
            $labels[]    = now()->setMonth($m)->translatedFormat('M');
            $montants[]  = (float) ($parMois[$key]->total ?? 0);
            $statuts     = $parStatut[$key] ?? collect();
            $confirmes[] = (int) ($statuts->firstWhere('status', 'confirme')?->nb   ?? 0);
            $annules[]   = (int) ($statuts->firstWhere('status', 'annule')?->nb     ?? 0);
            $attente[]   = (int) ($statuts->firstWhere('status', 'en_attente')?->nb ?? 0);
        }

        $this->chartData = compact('labels', 'montants', 'confirmes', 'annules', 'attente');
    }
}
?>

<div class="flex h-full w-full flex-1 flex-col gap-3">

    {{-- 3 metric cards --}}
    <div class="grid grid-cols-3 gap-3">

        <flux:card class="flex flex-col gap-1 p-4 py-8 justify-center">
            <flux:subheading>Total commandes</flux:subheading>
            <flux:heading size="xl">{{ $totalCommandes }}</flux:heading>
        </flux:card>

        <flux:card class="flex flex-col gap-1 p-4 py-8 justify-center">
            <flux:subheading>Montant total</flux:subheading>
            <flux:heading size="xl">{{ number_format($montantTotal, 0, ',', ' ') }} Rs</flux:heading>
        </flux:card>

        <flux:card class="flex flex-col gap-1 p-4 py-8 justify-center">
            <flux:subheading>En attente</flux:subheading>
            <flux:heading size="xl">{{ $enAttente }}</flux:heading>
        </flux:card>

    </div>

    {{-- 2 graphiques côte à côte --}}
    <div class="grid grid-cols-2 gap-3 mt-5">

        {{-- Bar chart --}}
        <flux:card class="flex flex-col gap-3 p-4">
            <flux:subheading>Commandes par mois ({{ now()->year }})</flux:subheading>
            <div class="relative w-full" style="height: 280px;">
                <canvas id="commandeBarChart" role="img"
                        aria-label="Montants de commandes par mois"></canvas>
            </div>
        </flux:card>

        {{-- Line chart --}}
        <flux:card class="flex flex-col gap-3 p-4">
            <flux:subheading>Commandes par statut</flux:subheading>
            <div class="flex gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-0.5 rounded-full" style="background:#1D9E75"></span>
                    Confirmées
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-0.5 rounded-full" style="background:#E24B4A"></span>
                    Annulées
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-0.5 rounded-full" style="background:#EF9F27"></span>
                    En attente
                </span>
            </div>
            <div class="relative w-full" style="height: 250px;">
                <canvas id="commandeLineChart" role="img"
                        aria-label="Évolution des commandes par statut"></canvas>
            </div>
        </flux:card>

    </div>

    @script
    <script>
        const data = @json($chartData);
        const isDark = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)';
        const tickColor = isDark ? '#a1a1aa' : '#71717a';
        const tickFont  = { size: 10 };

        new Chart(document.getElementById('commandeBarChart'), {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.montants,
                    backgroundColor: 'rgba(24,95,165,0.75)',
                    borderColor: '#185FA5',
                    borderWidth: 1,
                    borderRadius: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        ticks: {
                            color: tickColor, font: tickFont,
                            callback: v => v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v
                        },
                        grid: { color: gridColor }
                    },
                    x: {
                        ticks: { color: tickColor, font: tickFont, autoSkip: false },
                        grid: { display: false }
                    }
                }
            }
        });

        new Chart(document.getElementById('commandeLineChart'), {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Confirmées', data: data.confirmes,
                        borderColor: '#1D9E75', backgroundColor: 'rgba(29,158,117,0.08)',
                        fill: true, tension: 0.3, pointRadius: 2
                    },
                    {
                        label: 'Annulées', data: data.annules,
                        borderColor: '#E24B4A', backgroundColor: 'rgba(226,75,74,0.05)',
                        fill: true, tension: 0.3, pointRadius: 2, borderDash: [4, 4]
                    },
                    {
                        label: 'En attente', data: data.attente,
                        borderColor: '#EF9F27', backgroundColor: 'rgba(239,159,39,0.05)',
                        fill: true, tension: 0.3, pointRadius: 2, borderDash: [2, 4]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { ticks: { color: tickColor, font: tickFont }, grid: { color: gridColor } },
                    x: {
                        ticks: { color: tickColor, font: tickFont, autoSkip: true, maxTicksLimit: 6 },
                        grid: { display: false }
                    }
                }
            }
        });
    </script>
    @endscript

</div>
