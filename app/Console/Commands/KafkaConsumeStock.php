<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PrestashopService;
use App\Services\StockMagasinService;
use App\Models\StockEvent;
use App\Models\StockMagasin;
use App\Models\Product;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;

class KafkaConsumeStock extends Command
{
    protected $signature   = 'app:kafka-consume-stock';
    protected $description = 'Consumer Kafka — synchronisation stock ERP ↔ PrestaShop';

    public function handle(StockMagasinService $stockService, PrestashopService $prestashop)
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', '127.0.0.1:19092,127.0.0.1:19094,127.0.0.1:19096'));
        $conf->set('group.id', 'erp-stock-consumer');
        $conf->set('auto.offset.reset', 'earliest');
        $conf->set('enable.auto.commit', 'true');

        // Timeouts adaptés à un cluster multi-brokers
        $conf->set('socket.timeout.ms', '10000');
        $conf->set('session.timeout.ms', '30000');
        $conf->set('heartbeat.interval.ms', '3000');
        $conf->set('max.poll.interval.ms', '300000');

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe([env('KAFKA_TOPIC', 'stock-topic')]);  // ← corrigé

        $this->info('Consumer démarré — en attente de messages...');

        while (true) {
            $message = $consumer->consume(10000);

            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $this->processMessage($message, $stockService, $prestashop);
                    break;

                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    // Normal — pas de nouveaux messages
                    break;

                default:
                    $this->error('Erreur Kafka : ' . $message->errstr());
                    break;
            }
        }
    }

    private function processMessage($message, StockMagasinService $stockService, PrestashopService $prestashop): void
    {
        $data = json_decode($message->payload, true);

        if (!$data || !isset($data['event_id'])) {
            $this->warn('Payload invalide, message ignoré');
            return;
        }

        // Idempotence
        if (StockEvent::where('event_id', $data['event_id'])->exists()) {
            $this->line("Event {$data['event_id']} déjà traité — skip");
            return;
        }

        StockEvent::create([
            'event_id' => $data['event_id'],
            'sku'      => $data['sku'],
            'delta'    => $data['delta'],
            'source'   => $data['source'],
        ]);

        $magasinId = (int) env('STOCK_MAGASIN_ID');

        if ($data['source'] !== 'erp') {
            // Vient de PS → on applique le delta FIFO
            $totalStock = $stockService->applyDelta($data['sku'], $data['delta']);
        } else {
            // Vient de l'ERP → stock déjà mis à jour, on lit juste le total actuel
            $product = Product::where('EAN', $data['sku'])->first();

            if (!$product) {
                $this->warn("Produit introuvable pour SKU : {$data['sku']}");
                return;
            }

            $totalStock = StockMagasin::where('product_id', $product->id)
                ->where('magasin_id', $magasinId)
                ->sum('nb_item');
        }

        // Sync systématique vers PS — la base est volatile
        $prestashop->updateStock($data['sku'], $totalStock);

        $this->info("Traité — SKU: {$data['sku']} | delta: {$data['delta']} | source: {$data['source']} | total: {$totalStock}");
    }
}
