<?php

return [
    'host' => env('MQTT_HOST', '127.0.0.1'),
    'port' => (int) env('MQTT_PORT', 1883),
    'username' => env('MQTT_USERNAME'),
    'password' => env('MQTT_PASSWORD'),
    'client_id' => env('MQTT_CLIENT_ID'),
    'client_id_prefix' => env('MQTT_CLIENT_ID_PREFIX', 'laravel-gate-'),
    'topic_control' => env('MQTT_TOPIC_CONTROL', 'gate/control'),
    'topic_status' => env('MQTT_TOPIC_STATUS', 'gate/gate1/status'),
    'connect_timeout' => (int) env('MQTT_CONNECT_TIMEOUT', 2),
    'socket_timeout' => (int) env('MQTT_SOCKET_TIMEOUT', 2),
    'keep_alive' => (int) env('MQTT_KEEP_ALIVE', 10),
];
