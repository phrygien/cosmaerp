<?php

namespace App\Services;

use Illuminate\Support\Str;

class KafkaProducerService
{
    private \RdKafka\Producer $producer;

    public function __construct()
    {
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', '127.0.0.1:9092'));
        $conf->set('socket.timeout.ms', '5000');

        $this->producer = new \RdKafka\Producer($conf);
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

        $this->producer->flush(5000);
    }
}
