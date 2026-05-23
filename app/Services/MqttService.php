<?php

namespace App\Services;

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttService
{
    public function publish(string $topic, string $payload): void
    {
        $host = config('mqtt.host');
        $port = (int) config('mqtt.port');
        $clientId = config('mqtt.client_id') ?: config('mqtt.client_id_prefix') . bin2hex(random_bytes(4));

        $settings = (new ConnectionSettings())
            ->setUsername(config('mqtt.username'))
            ->setPassword(config('mqtt.password'))
            ->setConnectTimeout((int) config('mqtt.connect_timeout'))
            ->setSocketTimeout((int) config('mqtt.socket_timeout'))
            ->setKeepAliveInterval((int) config('mqtt.keep_alive'))
            ->setUseTls(false);

        $client = new MqttClient($host, $port, $clientId);
        $client->connect($settings, true);
        $client->publish($topic, $payload, 0);
        $client->disconnect();
    }
}
