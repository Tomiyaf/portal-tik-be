<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use App\Models\AccessLog;
use App\Models\ParkingQuota;

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
    fwrite(STDOUT, "[mqtt] starting listener for {$topic}\n");
    fflush(STDOUT);

    while (true) {
        try {
            $baseClientId = config('mqtt.client_id') ?: config('mqtt.client_id_prefix') . 'listener-';
            $clientId = $baseClientId . bin2hex(random_bytes(4));
            $client = new MqttClient($host, $port, $clientId);
            $client->connect($settings, true);
            fwrite(STDOUT, "[mqtt] connected as {$clientId}\n");
            fflush(STDOUT);

            $client->subscribe($topic, function (string $topic, string $message): void {
                $payload = json_decode($message, true);

                if (!is_array($payload)) {
                    Log::warning('MQTT access status payload is invalid JSON.', ['message' => $message]);
                    fwrite(STDOUT, "[mqtt] invalid JSON: {$message}\n");
                    fflush(STDOUT);
                    return;
                }

                Log::info('MQTT access status received.', ['payload' => $payload]);
                fwrite(STDOUT, '[mqtt] received: ' . $message . "\n");
                fflush(STDOUT);

                $accessLogId = $payload['access_log_id'] ?? $payload['request_id'] ?? null;
                $status = $payload['status'] ?? null;
                $notes = $payload['notes'] ?? null;

                if (!$accessLogId || !$status) {
                    Log::warning('MQTT access status missing fields.', ['payload' => $payload]);
                    fwrite(STDOUT, '[mqtt] missing fields: ' . json_encode($payload) . "\n");
                    fflush(STDOUT);
                    return;
                }

                if (is_numeric($accessLogId)) {
                    $accessLogId = (int) $accessLogId;
                }

                $accessLog = AccessLog::find($accessLogId);
                if (!$accessLog) {
                    Log::warning('Access log not found for MQTT status.', ['access_log_id' => $accessLogId]);
                    fwrite(STDOUT, "[mqtt] access_log not found: {$accessLogId}\n");
                    fflush(STDOUT);
                    return;
                }

                $previousStatus = $accessLog->access_status;
                $normalizedStatus = $status === 'success' ? 'success' : 'failed';

                if ($normalizedStatus === 'failed' && $accessLog->access_status !== 'pending') {
                    return;
                }

                $accessLog->access_status = $normalizedStatus;
                if (is_string($notes) && $notes !== '') {
                    $accessLog->notes = $notes;
                }
                $accessLog->save();

                if ($previousStatus !== 'success' && $normalizedStatus === 'success') {
                    $quota = ParkingQuota::first();

                    if ($quota) {
                        if ($accessLog->action === 'entry') {
                            ParkingQuota::where('id', $quota->id)
                                ->whereColumn('used_slots', '<', 'total_slots')
                                ->increment('used_slots');
                        }

                        if ($accessLog->action === 'exit') {
                            ParkingQuota::where('id', $quota->id)
                                ->where('used_slots', '>', 0)
                                ->decrement('used_slots');
                        }
                    }
                }

                Log::info('Access log updated from MQTT.', [
                    'access_log_id' => $accessLog->id,
                    'status' => $accessLog->access_status,
                ]);
                fwrite(STDOUT, "[mqtt] updated access_log_id={$accessLog->id} status={$accessLog->access_status}\n");
                fflush(STDOUT);
            }, 0);

            fwrite(STDOUT, "[mqtt] subscribed to {$topic}\n");
            fflush(STDOUT);

            $client->loop(true);
        } catch (\Throwable $exception) {
            Log::warning('MQTT listener disconnected. Reconnecting...', [
                'error' => $exception->getMessage(),
            ]);
            fwrite(STDOUT, "[mqtt] reconnecting after error: {$exception->getMessage()}\n");
            fflush(STDOUT);
            sleep(2);
        }
    }
})->purpose('Listen to MQTT status updates and update access logs');
