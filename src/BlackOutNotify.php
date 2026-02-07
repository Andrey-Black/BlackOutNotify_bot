<?php

namespace Core;

use RuntimeException;

class BlackOutNotify
{
  private const ENV_KEY_MAP = [
    'url' => 'TUYA_API_URL',
    'client_id' => 'TUYA_CLIENT_ID',
    'secret' => 'TUYA_SECRET',
    'device_id' => 'TUYA_DEVICE_ID',
    'access_token' => 'TUYA_ACCESS_TOKEN',
    'chat_id' => 'TELEGRAM_CHAT_ID',
    'bot_token' => 'TELEGRAM_BOT_TOKEN',
  ];

  protected $notifier;

  public function __construct($notifier)
  {
    $this->notifier = $notifier;
  }

  public static function run($notifier): void
  {
    $instance = new self($notifier);
    $instance->handleDeviceStatus();
  }

  protected function handleDeviceStatus(): void
  {
    $deviceData = $this->fetchDeviceStatus();
    $result = $this->searchProperty($deviceData, 'online');
    $this->CheckStatus($result);
  }

  protected function CheckStatus(array $result): void
  {
    if (!is_object($this->notifier) || !method_exists($this->notifier, 'run')) {
      throw new RuntimeException('Notifier is not configured correctly.');
    }

    $this->notifier->run($result);
  }

  protected function fetchDeviceStatus(): array
  {
    $data = $this->extractConfigData('data_json', ['url', 'client_id', 'device_id', 'secret', 'access_token']);
    $url = $this->buildDeviceUrl($data);
    $timestamp = $this->getTime();
    $sign = $this->generateSign($url, $timestamp, 'GET', $data['client_id'], $data['secret'], $data['access_token']);
    $headersData = [
      'client_id' => $data['client_id'],
      'access_token' => $data['access_token'],
      'sign' => $sign,
      't' => $timestamp,
    ];
    $headers = $this->buildCurlHeaders($headersData);
    $response = $this->sendCurlRequest($url, $headers);

    return $this->fetchJson($response);
  }

  public function generateSign(
    string $url,
    int $timestamp,
    string $httpMethod,
    string $clientId,
    string $secret,
    ?string $accessToken = null
  ): string {
    $urlPath = parse_url($url, PHP_URL_PATH);

    if (!is_string($urlPath) || $urlPath === '') {
      throw new RuntimeException('Invalid URL path for signature generation.');
    }

    if ($accessToken === null) 
    {
      $urlPath = $urlPath . '?grant_type=1';
    }

    $stringToSign = "$httpMethod\n" . hash('sha256', '') . "\n\n$urlPath";
    $stringToSignForHmac = $clientId . ($accessToken ?? '') . $timestamp . $stringToSign;
    $hash = hash_hmac('sha256', $stringToSignForHmac, $secret, true);

    return strtoupper(bin2hex($hash));
  }

  public function loadJsonData(): array
  {
    $path = $this->dataFilePath();
    $jsonData = file_get_contents($path);

    if ($jsonData === false) {
      throw new RuntimeException("Cannot read file: {$path}");
    }

    $decodedData = json_decode($jsonData, true);

    if (!is_array($decodedData)) {
      throw new RuntimeException("Invalid JSON content in file: {$path}");
    }

    return $decodedData;
  }

  public function getTime(): int
  {
    return round(microtime(true) * 1000);
  }

  public function saveJsonData(array $data): void
  {
    $encodedData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if (!is_string($encodedData)) {
      throw new RuntimeException('Failed to encode JSON data.');
    }

    $bytesWritten = file_put_contents($this->dataFilePath(), $encodedData . PHP_EOL, LOCK_EX);

    if ($bytesWritten === false) {
      throw new RuntimeException('Failed to write data.json.');
    }
  }

  public function extractData(string $object, array $keys): array
  {
    $data = $this->loadJsonData();

    if (!isset($data[$object]) || !is_array($data[$object])) {
      throw new RuntimeException("Missing JSON object: {$object}");
    }

    $accessData = $data[$object];
    $result = [];

    foreach ($keys as $key) 
    {
      if (array_key_exists($key, $accessData)) 
      {
        $result[$key] = $accessData[$key];
      }
    }

    return $result;
  }

  public function extractConfigData(string $object, array $keys): array
  {
    $data = $this->loadJsonData();

    if (!isset($data[$object]) || !is_array($data[$object])) {
      throw new RuntimeException("Missing JSON object: {$object}");
    }

    $accessData = $data[$object];
    $result = [];

    foreach ($keys as $key) {
      $envKey = self::ENV_KEY_MAP[$key] ?? null;
      $envValue = $envKey !== null ? getenv($envKey) : false;

      if ($envValue !== false && $envValue !== '') {
        $result[$key] = (string) $envValue;
        continue;
      }

      if (array_key_exists($key, $accessData) && $accessData[$key] !== '' && $accessData[$key] !== null) {
        $result[$key] = $accessData[$key];
        continue;
      }

      if ($envKey !== null) {
        throw new RuntimeException("Missing config key '{$key}'. Set '{$envKey}' or fill data.json.");
      }

      throw new RuntimeException("Missing config key '{$key}'.");
    }

    return $result;
  }

  public function fetchJson(string $response): array
  {
    $decodedData = json_decode($response, true);

    if (!is_array($decodedData)) {
      throw new RuntimeException('API response is not valid JSON.');
    }

    return $decodedData;
  }

  public function searchProperty(array $arr, string $item): array
  {
    if (!isset($arr['result']) || !is_array($arr['result'])) {
      throw new RuntimeException('Missing "result" block in API response.');
    }

    if (!array_key_exists($item, $arr['result'])) {
      throw new RuntimeException("Property '{$item}' was not found in API response.");
    }

    return [$item => $arr['result'][$item]];
  }

  public function sendCurlRequest(string $url, array $headers = [], string $method = 'GET', array $data = []): string
  {
    $curl = curl_init();

    if ($curl === false) {
      throw new RuntimeException('Failed to initialize cURL.');
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 20);

    if ($method === 'POST' && !empty($data)) 
    {
      curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($response === false) {
      $error = curl_error($curl);
      curl_close($curl);
      throw new RuntimeException("cURL request failed: {$error}");
    }

    curl_close($curl);

    if ($httpCode >= 400) {
      throw new RuntimeException("HTTP request failed with status {$httpCode}.");
    }

    return $response;
  }

  private function buildDeviceUrl(array $data): string
  {
    return $data['url'] . "/v1.0/devices/{$data['device_id']}";
  }

  public function buildCurlHeaders(array $data): array
  {
    $defaultHeaders = ["sign_method" => "HMAC-SHA256"];

    $allHeaders = array_merge($defaultHeaders, $data);
    $formattedHeaders = [];

    foreach ($allHeaders as $key => $value) 
    {
      if (!is_null($value)) 
      {
        $formattedHeaders[] = "$key: $value";
      }
    }

    return $formattedHeaders;
  }

  private function dataFilePath(): string
  {
    return dirname(__DIR__) . '/data.json';
  }
}
