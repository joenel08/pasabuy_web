<?php
namespace App\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Fcm
{
    private static ?array $creds = null;
    private static ?string $accessToken = null;
    private static int $tokenExpiresAt = 0;

    private static function credentials(): array
    {
        if (self::$creds !== null) return self::$creds;
        $config = require __DIR__ . '/../../config/config.php';
        $path = $config['fcm']['service_account'];
        if (!is_file($path)) {
            throw new \RuntimeException("Service account missing: $path");
        }
        self::$creds = json_decode(file_get_contents($path), true);
        return self::$creds;
    }

    private static function accessToken(): string
    {
        if (self::$accessToken && time() < self::$tokenExpiresAt - 60) {
            return self::$accessToken;
        }

        $creds = self::credentials();
        $now   = time();
        $exp   = $now + 3600;

        $header  = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims  = [
            'iss'   => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $exp,
        ];

        $b64 = fn($d) => rtrim(strtr(base64_encode(json_encode($d)), '+/', '-_'), '=');
        $signingInput = $b64($header) . '.' . $b64($claims);
        openssl_sign($signingInput, $sig, $creds['private_key'], 'SHA256');
        $jwt = $signingInput . '.' . rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');

        $res = (new Client())->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ],
        ]);
        $json = json_decode((string) $res->getBody(), true);

        self::$accessToken   = $json['access_token'];
        self::$tokenExpiresAt = $now + (int) $json['expires_in'];
        return self::$accessToken;
    }

    public static function toUser(int $userId, string $title, string $body, array $data = []): void
    {
        $stmt = Database::pdo()->prepare("SELECT id, token FROM fcm_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if (!$rows) return;

        $creds   = self::credentials();
        $project = $creds['project_id'];
        $url     = "https://fcm.googleapis.com/v1/projects/{$project}/messages:send";
        $bearer  = self::accessToken();
        $client  = new Client(['timeout' => 10]);

        $dead = [];
        foreach ($rows as $r) {
            $payload = [
                'message' => [
                    'token'        => $r['token'],
                    'notification' => ['title' => $title, 'body' => $body],
                    'data'         => array_map('strval', $data), // FCM data values must be strings
                    'android'      => [
                        'priority'     => 'high',
                        'notification' => [
                            'channel_id' => 'pasabuy_high',
                            'sound'      => 'default',
                        ],
                    ],
                ],
            ];

            try {
                $client->post($url, [
                    'headers' => [
                        'Authorization' => "Bearer $bearer",
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => $payload,
                ]);
            } catch (GuzzleException $e) {
                $body = method_exists($e, 'getResponse') && $e->getResponse()
                    ? (string) $e->getResponse()->getBody()
                    : $e->getMessage();
                error_log('[FCM] failed: ' . $body);
                if (str_contains($body, 'UNREGISTERED') || str_contains($body, 'NOT_FOUND')) {
                    $dead[] = (int) $r['id'];
                }
            }
        }

        if ($dead) {
            $in = implode(',', array_fill(0, count($dead), '?'));
            Database::pdo()->prepare("DELETE FROM fcm_tokens WHERE id IN ($in)")->execute($dead);
        }
    }
}