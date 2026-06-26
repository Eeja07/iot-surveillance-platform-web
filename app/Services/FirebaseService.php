<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FirebaseService
{
    /**
     * Send push notification via FCM HTTP v1 API.
     *
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array|null $data
     * @return bool
     */
    public function sendNotification(string $token, string $title, string $body, ?array $data = null): bool
    {
        $projectId = config('services.firebase.project_id');
        if (!$projectId) {
            Log::error('FCM Project ID is not configured.');
            return false;
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::error('Failed to retrieve FCM Access Token.');
            return false;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $message = [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
        ];

        if ($data) {
            $message['data'] = array_map('strval', $data);
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post($url, ['message' => $message]);

            if ($response->successful()) {
                Log::info('FCM notification sent successfully.', ['token' => $token]);
                return true;
            }

            Log::error('FCM API returned error status ' . $response->status(), [
                'body' => $response->body(),
                'token' => $token
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('FCM API connection error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate Google OAuth2 Access Token using Service Account JSON.
     * Cache the token for 55 minutes to avoid generating on every request.
     *
     * @return string|null
     */
    protected function getAccessToken(): ?string
    {
        return Cache::remember('fcm_access_token', 3300, function () {
            $path = config('services.firebase.credentials_path');
            if (!file_exists($path)) {
                Log::error("Firebase credentials file not found at: {$path}");
                return null;
            }

            try {
                $creds = json_decode(file_get_contents($path), true);
                if (!$creds || !isset($creds['private_key']) || !isset($creds['client_email'])) {
                    Log::error('Invalid Firebase service account credentials structure.');
                    return null;
                }

                $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
                $now = time();
                $payload = $this->base64UrlEncode(json_encode([
                    'iss'   => $creds['client_email'],
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                    'aud'   => 'https://oauth2.googleapis.com/token',
                    'exp'   => $now + 3600,
                    'iat'   => $now,
                ]));

                $signatureInput = "{$header}.{$payload}";
                $privateKey = openssl_pkey_get_private($creds['private_key']);
                if (!$privateKey) {
                    Log::error('Invalid Firebase private key.');
                    return null;
                }

                if (!openssl_sign($signatureInput, $signature, $privateKey, 'SHA256')) {
                    Log::error('Failed to sign JWT with Firebase private key.');
                    return null;
                }

                $encodedSignature = $this->base64UrlEncode($signature);
                $assertion = "{$signatureInput}.{$encodedSignature}";

                $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $assertion,
                ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::error('Failed to retrieve OAuth2 token from Google', ['body' => $response->body()]);
                return null;
            } catch (\Exception $e) {
                Log::error('Exception generating FCM access token: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Base64URL encoding helper.
     */
    private function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
