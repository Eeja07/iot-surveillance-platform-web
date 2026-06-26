<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmqxService
{
    protected $baseUrl;
    protected $apiKey;
    protected $apiSecret;
    protected $callbackBaseUrl;

    public function __construct()
    {
        $this->baseUrl = env('EMQX_API_URL', 'http://localhost:18083/api/v5');
        $this->apiKey = env('EMQX_API_KEY');
        $this->apiSecret = env('EMQX_API_SECRET');

        // PERBAIKAN: Selalu prioritaskan EMQX_CALLBACK_URL dari .env
        // agar EMQX container bisa reach Laravel via Docker internal hostname
        $this->callbackBaseUrl = rtrim(
            env('EMQX_CALLBACK_URL', 'http://laravel_web_dev:80'),
            '/'
        );
    }

    /**
     * Sinkronisasi total dengan urutan logika yang ketat:
     * 1. Auth -> 2. Authorization -> 3. Connector -> 4. Action -> 5. Rules
     */
    public function syncAll()
    {
        try {
            Log::info("EMQX_SYNC_STARTED: Menggunakan Callback -> " . $this->callbackBaseUrl);

            // 1. SETUP AUTHENTICATION
            $this->setupAuthentication();

            // 2. SETUP AUTHORIZATION (ACL)
            $this->setupAuthorization();

            // 3. SETUP CONNECTOR (WAJIB: Harus sukses sebelum lanjut ke Action)
            $connectorSuccess = $this->setupConnector();
            if (!$connectorSuccess) {
                throw new \Exception("Gagal menyiapkan Connector HTTP. EMQX tidak dapat menjangkau URL Laravel Anda.");
            }

            // 4. SETUP ACTIONS
            $this->setupAllActions();

            // 5. SETUP RULES
            $this->setupAllRules();

            Log::info("EMQX_SYNC_COMPLETE: Seluruh infrastruktur berhasil dikonfigurasi.");
            return true;
        } catch (\Exception $e) {
            Log::error("EMQX_SYNC_FAILED: " . $e->getMessage());
            throw $e;
        }
    }

    // ==========================================
    // 1. AUTHENTICATION
    // ==========================================
    protected function setupAuthentication()
    {
        $url = "{$this->baseUrl}/authentication";
        $isHttps = str_starts_with($this->callbackBaseUrl, 'https');

        $payload = [
            'backend' => 'http',
            'mechanism' => 'password_based',
            'method' => 'post',
            'url' => "{$this->callbackBaseUrl}/api/mqtt/auth",
            'headers' => ['content-type' => 'application/json'],
            'body' => ['username' => '${username}', 'password' => '${password}'],
            'enable' => true,
            'ssl' => [
                'enable' => $isHttps,
                'verify' => 'verify_none'
            ]
        ];

        $res = $this->post($url, $payload);
        if ($res->failed() && $res->status() !== 409) {
            Log::error("EMQX_AUTH_SETUP_ERROR: " . $res->body());
        }
        return $res->successful() || $res->status() == 409;
    }

    // ==========================================
    // 2. AUTHORIZATION
    // ==========================================
    protected function setupAuthorization()
    {
        $url = "{$this->baseUrl}/authorization/sources";
        $isHttps = str_starts_with($this->callbackBaseUrl, 'https');

        $payload = [
            'type' => 'http',
            'enable' => true,
            'method' => 'post',
            'url' => "{$this->callbackBaseUrl}/api/mqtt/acl",
            'headers' => ['content-type' => 'application/json'],
            'body' => ['username' => '${username}', 'topic' => '${topic}', 'action' => '${action}'],
            'ssl' => [
                'enable' => $isHttps,
                'verify' => 'verify_none'
            ]
        ];

        $res = $this->post($url, $payload);
        if ($res->failed()) {
            // Kalau sudah ada (409 atau error lain), coba update via PUT
            $this->put("{$url}/http", $payload);
        }
        return true;
    }

    // ==========================================
    // 3. CONNECTOR
    // ==========================================
    protected function setupConnector()
    {
        $id = "http:conn_laravel_http";
        $url = "{$this->baseUrl}/connectors";

        // Payload POST (create) — boleh ada name dan type
        $createPayload = [
            'type' => 'http',
            'name' => 'conn_laravel_http',
            'url' => $this->callbackBaseUrl,
            'headers' => [
                'content-type' => 'application/json',
                'accept' => 'application/json'
            ],
            'enable' => true,
            'connect_timeout' => '5s'
        ];

        // PERBAIKAN: Payload PUT (update) — TANPA name dan type
        // EMQX v5 menolak field ini saat update (BAD_REQUEST: unknown_fields)
        $updatePayload = [
            'url' => $this->callbackBaseUrl,
            'headers' => [
                'content-type' => 'application/json',
                'accept' => 'application/json'
            ],
            'enable' => true,
            'connect_timeout' => '5s'
        ];

        $check = $this->get("{$url}/{$id}");

        if ($check->successful()) {
            Log::info("EMQX_CONNECTOR: Mengupdate connector yang sudah ada.");
            $res = $this->put("{$url}/{$id}", $updatePayload);
        } else {
            Log::info("EMQX_CONNECTOR: Membuat connector baru.");
            $res = $this->post($url, $createPayload);
        }

        if ($res->failed()) {
            Log::error("EMQX_CONNECTOR_ERROR: " . $res->body());
            return false;
        }

        return true;
    }

    // ==========================================
    // 4. ACTIONS
    // ==========================================
    protected function setupAllActions()
    {
        $connector = "conn_laravel_http";

        $this->createAction("action_laravel_mqtt_image", "/api/mqtt/webhook", $connector);
        $this->createAction("action_laravel_ws_telemetry", "/api/ws-bridge/telemetry", $connector);
        $this->createAction("action_laravel_ws_image", "/api/ws-bridge/image", $connector);
        $this->createAction("action_laravel_ws_status", "/api/ws-bridge/status", $connector);
        $this->createAction("action_laravel_ws_ota_status", "/api/ws-bridge/ota-status", $connector);
        $this->createAction("action_laravel_ws_config_status", "/api/ws-bridge/config-status", $connector);

        Log::info("EMQX_ACTIONS_SETUP: Prosedur pendaftaran Action selesai.");
    }

    protected function createAction($name, $path, $connector)
    {
        $url = "{$this->baseUrl}/actions";

        $createPayload = [
            'type' => 'http',
            'name' => $name,
            'connector' => $connector,
            'parameters' => [
                'path' => $path,
                'method' => 'post',
                'headers' => ['content-type' => 'application/json', 'accept' => 'application/json'],
                'body' => json_encode([
                    'action' => 'message_publish',
                    'topic' => '${topic}',
                    'payload' => '${payload}',
                    'username' => '${username}'
                ])
            ],
            'enable' => true
        ];

        // PERBAIKAN: Payload PUT tanpa name dan type
        $updatePayload = [
            'connector' => $connector,
            'parameters' => [
                'path' => $path,
                'method' => 'post',
                'headers' => ['content-type' => 'application/json', 'accept' => 'application/json'],
                'body' => json_encode([
                    'action' => 'message_publish',
                    'topic' => '${topic}',
                    'payload' => '${payload}',
                    'username' => '${username}'
                ])
            ],
            'enable' => true
        ];

        $res = $this->post($url, $createPayload);
        if ($res->status() == 409 || ($res->failed() && str_contains($res->body(), 'already_exists'))) {
            $res = $this->put("{$url}/http:{$name}", $updatePayload);
        }

        if ($res->failed()) {
            Log::error("EMQX_ACTION_ERROR [{$name}]: " . $res->body());
        }

        return $res;
    }

    // ==========================================
    // 5. RULES
    // ==========================================
    protected function setupAllRules()
    {
        $this->createRule("rule_mqtt_image", 'SELECT * FROM "iot/camera/+/image"', ["http:action_laravel_mqtt_image"]);
        $this->createRule("rule_ws_telemetry", 'SELECT * FROM "ws/camera/+/telemetry"', ["http:action_laravel_ws_telemetry"]);
        $this->createRule("rule_ws_image", 'SELECT * FROM "ws/camera/+/image"', ["http:action_laravel_ws_image"]);
        $this->createRule("rule_ws_status", 'SELECT * FROM "ws/camera/+/status"', ["http:action_laravel_ws_status"]);
        $this->createRule("rule_ws_ota_status", 'SELECT * FROM "ws/camera/+/ota/status"', ["http:action_laravel_ws_ota_status"]);
        $this->createRule("rule_ws_config_status", 'SELECT * FROM "ws/camera/+/config/status"', ["http:action_laravel_ws_config_status"]);

        Log::info("EMQX_RULES_SETUP: Prosedur pendaftaran Rule selesai.");
    }

    protected function createRule($id, $sql, $actions)
    {
        $url = "{$this->baseUrl}/rules";
        $payload = [
            'id' => $id,
            'sql' => $sql,
            'actions' => $actions,
            'enable' => true
        ];

        $res = $this->post($url, $payload);
        if ($res->status() == 409 || ($res->failed() && str_contains($res->body(), 'already_exists'))) {
            $res = $this->put("{$url}/{$id}", $payload);
        }

        if ($res->failed()) {
            Log::error("EMQX_RULE_ERROR [{$id}]: " . $res->body());
        }

        return $res;
    }

    // ==========================================
    // HTTP HELPERS
    // ==========================================
    protected function post($url, $data)
    {
        return Http::withBasicAuth($this->apiKey, $this->apiSecret)->post($url, $data);
    }

    protected function put($url, $data)
    {
        return Http::withBasicAuth($this->apiKey, $this->apiSecret)->put($url, $data);
    }

    protected function get($url)
    {
        return Http::withBasicAuth($this->apiKey, $this->apiSecret)->get($url);
    }

    /**
     * Publish an MQTT message via EMQX REST API
     */
    public function publish($topic, $payload, $qos = 1, $retain = false)
    {
        $url = "{$this->baseUrl}/publish";
        $payloadData = [
            'topic' => $topic,
            'payload' => base64_encode(is_string($payload) ? $payload : json_encode($payload)),
            'qos' => $qos,
            'retain' => $retain,
            'payload_encoding' => 'base64'
        ];

        $res = $this->post($url, $payloadData);
        if ($res->failed()) {
            Log::error("EMQX_PUBLISH_FAILED [{$topic}]: " . $res->body());
        }
        return $res->successful();
    }
}
