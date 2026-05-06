<?php

namespace App\Services;

use Illuminate\Support\Str;

class KafkaProducerService
{
    private Producer $producer;

    public function __construct()
    {
        $conf = new RdKafka\Conf();
        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', '127.0.0.1:9092'));
        $conf->set('socket.timeout.ms', '5000');

        $this->producer = new RdKafka\Producer($conf);
    }

    public function publish(string $sku, int $delta, string $source): void
    {
        $topic = $this->producer->newTopic('stock-events');

        $payload = json_encode([
            'event_id'  => Str::uuid()->toString(),
            'sku'       => $sku,
            'delta'     => $delta,
            'source'    => $source,
            'timestamp' => time(),
        ]);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $payload);

        // Flush — attend confirmation du broker (timeout 5s)
        $this->producer->flush(5000);
    }
}