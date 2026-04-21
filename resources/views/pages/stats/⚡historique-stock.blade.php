<?php

use Livewire\Component;
use App\Models\StockMagasin;
use App\Models\Magasin;

new class extends Component
{
    public array $chartData = [];
    public int $totalStock = 0;
    public int $totalDepots = 0;
    public string $semaineCourante = '';

    public function mount(): void
    {
        $this->totalStock      = StockMagasin::where('state', 1)->sum('nb_item');
        $this->totalDepots     = Magasin::where('state', 1)->count();
        $this->semaineCourante = 'S' . now()->weekOfYear;

        $magasins = Magasin::where('state', 1)->get();

        // Générer les 12 dernières semaines
        $semaines = [];
        for ($i = 11; $i >= 0; $i--) {
            $semaines[] = now()->subWeeks($i)->startOfWeek();
        }

        $labels   = [];
        $datasets = [];

        $colors = [
            ['border' => '#185FA5', 'bg' => 'rgba(24,95,165,0.07)'],
            ['border' => '#1D9E75', 'bg' => 'rgba(29,158,117,0.07)'],
            ['border' => '#EF9F27', 'bg' => 'rgba(239,159,39,0.05)'],
            ['border' => '#E24B4A', 'bg' => 'rgba(226,75,74,0.05)'],
            ['border' => '#7F77DD', 'bg' => 'rgba(127,119,221,0.05)'],
            ['border' => '#D4537E', 'bg' => 'rgba(212,83,126,0.05)'],
        ];

        foreach ($semaines as $debut) {
            $labels[] = 'S' . $debut->weekOfYear;
        }

        foreach ($magasins as $index => $magasin) {
            $color = $colors[$index % count($colors)];
            $data  = [];

            foreach ($semaines as $debut) {
                $fin    = $debut->copy()->endOfWeek();
                $stock  = StockMagasin::where('magasin_id', $magasin->id)
                    ->whereBetween('deposite_date', [$debut, $fin])
                    ->sum('nb_item');

                $data[] = (int) $stock;
            }

            $datasets[] = [
                'label'           => $magasin->name,
                'data'            => $data,
                'borderColor'     => $color['border'],
                'backgroundColor' => $color['bg'],
                'fill'            => true,
                'tension'         => 0.35,
                'pointRadius'     => 3,
                'pointHoverRadius'=> 5,
                'borderWidth'     => 2,
            ];
        }

        $this->chartData = compact('labels', 'datasets');
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-3">

    {{-- Metric cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

        <flux:card class="flex flex-col gap-1 p-4 py-6 sm:py-8 justify-center">
            <flux:subheading>Total en stock</flux:subheading>
            <flux:heading size="xl">{{ number_format($totalStock, 0, ',', ' ') }}</flux:heading>
        </flux:card>

        <flux:card class="flex flex-col gap-1 p-4 py-6 sm:py-8 justify-center">
            <flux:subheading>Dépôts actifs</flux:subheading>
            <flux:heading size="xl">{{ $totalDepots }}</flux:heading>
        </flux:card>

        <flux:card class="flex flex-col gap-1 p-4 py-6 sm:py-8 justify-center">
            <flux:subheading>Semaine courante</flux:subheading>
            <flux:heading size="xl">{{ $semaineCourante }}</flux:heading>
        </flux:card>

    </div>

    {{-- Line chart --}}
    <flux:card class="flex flex-col gap-3 p-4 mt-5">

        <flux:subheading>Évolution du stock par dépôt (12 dernières semaines)</flux:subheading>

        {{-- Légende dynamique --}}
        <div class="flex flex-wrap gap-3 text-xs text-zinc-500 dark:text-zinc-400">
            @foreach($chartData['datasets'] ?? [] as $dataset)
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-0.5 rounded-full"
                          style="background: {{ $dataset['borderColor'] }}"></span>
                    {{ $dataset['label'] }}
                </span>
            @endforeach
        </div>

        <div class="relative w-full" style="height: 280px;">
            <canvas id="stockLineChart" role="img"
                    aria-label="Évolution du stock par dépôt sur les 12 dernières semaines"></canvas>
        </div>

    </flux:card>

    @script
    <script>
        const data    = @json($chartData);
        const isDark  = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)';
        const tickColor = isDark ? '#a1a1aa' : '#71717a';
        const isMobile  = window.innerWidth < 640;
        const tickFont  = { size: isMobile ? 9 : 11 };

        new Chart(document.getElementById('stockLineChart'), {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: data.datasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.dataset.label} : ${ctx.parsed.y} unités`
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            color: tickColor,
                            font: tickFont,
                            maxTicksLimit: 6,
                            callback: v => v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v,
                        },
                        grid: { color: gridColor }
                    },
                    x: {
                        ticks: {
                            color: tickColor,
                            font: tickFont,
                            autoSkip: isMobile,
                            maxTicksLimit: isMobile ? 6 : 12,
                        },
                        grid: { display: false }
                    }
                }
            }
        });
    </script>
    @endscript

</div>
