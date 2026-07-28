<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use App\Models\AccessLog;
use App\Models\ParkingQuota;
use App\Models\IotDevice;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mqtt:listen', function () {
    $host = config('mqtt.host');
    $port = (int) config('mqtt.port');

    $accessTopic = config('mqtt.topic_access_status');
    $deviceTopic = config('mqtt.topic_device_status');

    $settings = (new ConnectionSettings())
        ->setUsername(config('mqtt.username'))
        ->setPassword(config('mqtt.password'))
        ->setConnectTimeout((int) config('mqtt.connect_timeout'))
        ->setSocketTimeout((int) config('mqtt.socket_timeout'))
        ->setKeepAliveInterval((int) config('mqtt.keep_alive'))
        ->setUseTls(false);

    while (true) {
        try {
            $clientId = config('mqtt.client_id_prefix') . 'listener-' . bin2hex(random_bytes(4));

            $client = new MqttClient($host, $port, $clientId);
            $client->connect($settings, true);

            $this->info("MQTT connected as {$clientId}");

            // 1. Listen hasil akses gate: success / failed
            $client->subscribe($accessTopic, function (string $topic, string $message): void {
                $payload = json_decode($message, true);

                if (!is_array($payload)) {
                    Log::warning('Invalid access status payload.', ['message' => $message]);
                    return;
                }

                $accessLogId = $payload['access_log_id'] ?? $payload['request_id'] ?? null;
                $status = $payload['status'] ?? null;
                $notes = $payload['notes'] ?? null;

                if (!$accessLogId || !$status) {
                    Log::warning('Access status missing fields.', ['payload' => $payload]);
                    return;
                }

                $accessLog = AccessLog::find($accessLogId);

                if (!$accessLog) {
                    Log::warning('Access log not found.', ['access_log_id' => $accessLogId]);
                    return;
                }

                $previousStatus = $accessLog->access_status;
                $normalizedStatus = $status === 'success' ? 'success' : 'failed';

                if ($normalizedStatus === 'failed' && $accessLog->access_status !== 'pending') {
                    return;
                }

                DB::transaction(function () use ($accessLog, $normalizedStatus, $notes, $previousStatus) {
                    $accessLog->access_status = $normalizedStatus;

                    if (is_string($notes) && $notes !== '') {
                        $accessLog->notes = $notes;
                    }

                    $accessLog->save();

                    if ($previousStatus !== 'success' && $normalizedStatus === 'success') {
                        $quota = ParkingQuota::lockForUpdate()->first();

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
                });
            }, 1);

            // 2. Listen status ESP: online / offline
            $client->subscribe($deviceTopic, function (string $topic, string $message): void {
                $payload = json_decode($message, true);

                if (!is_array($payload)) {
                    Log::warning('Invalid device status payload.', ['message' => $message]);
                    return;
                }

                $deviceUid = 'ESP8266-GATE1-001';
                $status = $payload['status'] ?? null;

                if (!in_array($status, ['online', 'offline'], true)) {
                    Log::warning('Invalid device status.', ['payload' => $payload]);
                    return;
                }

                $data = [
                    'status' => $status,
                ];

                if ($status === 'online') {
                    $data['last_online_at'] = now();
                }

                IotDevice::where('device_uid', $deviceUid)->update($data);
            }, 1);

            $this->info("Subscribed to {$accessTopic}");
            $this->info("Subscribed to {$deviceTopic}");

            $client->loop(true);
        } catch (\Throwable $exception) {
            Log::warning('MQTT listener disconnected. Reconnecting...', [
                'error' => $exception->getMessage(),
            ]);

            sleep(2);
        }
    }
})->purpose('Listen to MQTT access and device status');
