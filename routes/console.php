<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use App\Models\AccessLog;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mqtt:listen-access-status', function () {
    $host = config('mqtt.host');
    $port = (int) config('mqtt.port');
    $topic = config('mqtt.topic_status');

    $settings = (new ConnectionSettings())
        ->setUsername(config('mqtt.username'))
        ->setPassword(config('mqtt.password'))
        ->setConnectTimeout((int) config('mqtt.connect_timeout'))
        ->setSocketTimeout((int) config('mqtt.socket_timeout'))
        ->setKeepAliveInterval((int) config('mqtt.keep_alive'))
        ->setUseTls(false);

    $this->info("Listening to MQTT topic: {$topic}");

    while (true) {
        try {
            $clientId = config('mqtt.client_id') ?: config('mqtt.client_id_prefix') . bin2hex(random_bytes(4));
            $client = new MqttClient($host, $port, $clientId);
            $client->connect($settings, true);

            $client->subscribe($topic, function (string $topic, string $message): void {
                $payload = json_decode($message, true);

                if (!is_array($payload)) {
                    Log::warning('MQTT access status payload is invalid JSON.', ['message' => $message]);
                    return;
                }

                $accessLogId = $payload['access_log_id'] ?? $payload['request_id'] ?? null;
                $status = $payload['status'] ?? null;
                $notes = $payload['notes'] ?? null;

                if (!$accessLogId || !$status) {
                    Log::warning('MQTT access status missing fields.', ['payload' => $payload]);
                    return;
                }

                $accessLog = AccessLog::find($accessLogId);
                if (!$accessLog) {
                    Log::warning('Access log not found for MQTT status.', ['access_log_id' => $accessLogId]);
                    return;
                }

                $normalizedStatus = $status === 'success' ? 'success' : 'failed';

                if ($normalizedStatus === 'failed' && $accessLog->access_status !== 'pending') {
                    return;
                }

                $accessLog->access_status = $normalizedStatus;
                if (is_string($notes) && $notes !== '') {
                    $accessLog->notes = $notes;
                }
                $accessLog->save();
            }, 0);

            $client->loop(true);
        } catch (\Throwable $exception) {
            Log::warning('MQTT listener disconnected. Reconnecting...', [
                'error' => $exception->getMessage(),
            ]);
            sleep(2);
        }
    }
})->purpose('Listen to MQTT status updates and update access logs');
