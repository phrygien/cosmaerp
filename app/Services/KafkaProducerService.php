<?php

namespace App\Services;

use Illuminate\Support\Str;
use RdKafka\Conf;
use RdKafka\Producer;

class KafkaProducerService
{
    private Producer $producer;
    private string $topic;

    public function __construct()
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', '127.0.0.1:19092,127.0.0.1:19094,127.0.0.1:19096'));
        $conf->set('socket.timeout.ms', '10000');
        $conf->set('message.timeout.ms', '10000');
        $conf->set('request.required.acks', '-1');  // attendre tous les ISR (cohérent avec min.insync.replicas=2)

        $this->producer = new Producer($conf);
        $this->topic    = env('KAFKA_TOPIC', 'stock-events');
    }

    public function publish(string $sku, int $delta, string $source): void
    {
        $topic = $this->producer->newTopic($this->topic);

        $payload = json_encode([
            'event_id'  => Str::uuid()->toString(),
            'sku'       => $sku,
            'delta'     => $delta,
            'source'    => $source,
            'timestamp' => time(),
        ]);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $payload);

        $remaining = $this->producer->flush(10000);

        if ($remaining > 0) {
            throw new \RuntimeException("Kafka flush incomplet — {$remaining} message(s) non envoyé(s)");
        }
    }
}
