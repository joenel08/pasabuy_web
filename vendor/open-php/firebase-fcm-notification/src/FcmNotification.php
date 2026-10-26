<?php

namespace OpenPhp\FirebaseFcm;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Firebase\JWT\JWT;

class FcmNotification
{
    private $serviceAccount;
    private $projectId;

    /**
     * @param string|array $serviceAccount Path to service account JSON file or array of credentials
     * @throws \Exception
     */
    public function __construct($serviceAccount)
    {
        if (is_string($serviceAccount)) {
            if (!file_exists($serviceAccount)) {
                throw new \Exception("Service account file not found: " . $serviceAccount);
            }
            $this->serviceAccount = json_decode(file_get_contents($serviceAccount), true);
        } else {
            $this->serviceAccount = $serviceAccount;
        }

        if (!isset($this->serviceAccount['project_id'])) {
            throw new \Exception("Invalid service account: project_id missing");
        }

        $this->projectId = $this->serviceAccount['project_id'];
    }

    /**
     * Send a notification
     * 
     * @param string $title
     * @param string $body
     * @param string $target Token or Topic
     * @param bool $isTopic True if target is a topic
     * @param string|null $imageUrl Optional image URL
     * @return array|null Response from FCM or null on error
     */
    public function send($title, $body, $target, $isTopic = false, $imageUrl = null)
    {
        $accessToken = $this->getAccessToken();
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $messageTargetKey = $isTopic ? 'topic' : 'token';

        $notification = [
            'title' => $title,
            'body'  => $body,
        ];

        if ($imageUrl) {
            $notification['image'] = $imageUrl;
        }

        $payload = [
            'message' => [
                $messageTargetKey => $target,
                'notification' => $notification,
            ],
        ];

        $client = new Client();

        try {
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            // Log or handle error appropriately. For now, echoing as per original code style or returning null.
            // Ideally, we should throw exception or return error structure.
            // Keeping it simple as per original requirements, but maybe cleaner to log.
             if ($e->hasResponse()) {
                $errorResponse = json_decode($e->getResponse()->getBody(), true);
                // You might want to return this error instead of echoing
                return ['error' => $errorResponse];
            }
            return ['error' => $e->getMessage()];
        }
    }

    private function getAccessToken()
    {
        $client = new Client();
        
        $response = $client->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $this->generateJWT(),
            ],
        ]);

        $body = json_decode($response->getBody(), true);
        return $body['access_token'];
    }

    private function generateJWT()
    {
        $now = time();
        $payload = [
            'iss' => $this->serviceAccount['client_email'],
            'sub' => $this->serviceAccount['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ];
        return JWT::encode($payload, $this->serviceAccount['private_key'], 'RS256');
    }
}
