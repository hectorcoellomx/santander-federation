<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

class CampusDigitalAPI
{
    private $authUrl = 'https://sso.universia.net/auth/realms/CampusIdentityAPI/protocol/openid-connect/token';
    private $apiUrl = 'https://api-manager.universia.net/campuspack-third-parties-hooks/api';
    
    private $clientId = 'YourClientId';
    private $clientSecret = 'YourClientSecret';
    private $publicKey = '-----BEGIN PUBLIC KEY-----
<YourPublicKeyHere>
-----END PUBLIC KEY-----';
    
    private $accessToken;

    public function authenticate()
    {
        $client = new Client();

        $basicAuth = base64_encode("{$this->clientId}:{$this->clientSecret}");

        $response = $client->post($this->authUrl, [
            'headers' => [
                'Authorization' => "Basic {$basicAuth}",
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'grant_type' => 'client_credentials',
            ],
        ]);

        $body = json_decode($response->getBody(), true);
        $this->accessToken = $body['access_token'];
    }

    public function createFederatedUser($userData)
    {
        $client = new Client();

        // Crear el JWE cifrado
        $encryptedData = $this->encryptUserData($userData);

        $response = $client->post("{$this->apiUrl}/user", [
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'encryptedUserData' => $encryptedData,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    private function encryptUserData($userData)
    {
        $payload = [
            'data' => $userData,
            'iat' => time(),
            'exp' => time() + 3600, // Token válido por 1 hora
        ];

        return JWT::encode($payload, $this->publicKey, 'RS256');
    }
}

// Ejemplo 

try {
    $api = new CampusDigitalAPI();
    $api->authenticate();

    $userData = [
        'name' => 'Hector Coello',
        'email' => 'a081078@unach.mx',
        'idNumber' => '100348',
    ];

    $result = $api->createFederatedUser($userData);

    echo "Usuario federado creado exitosamente:\n";
    print_r($result);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
