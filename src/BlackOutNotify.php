<?php

namespace Core;

use Core\Telegram;

class BlackOutNotify
{

  public static function run(): void
  {
    $instance = new self();
    $instance->handleDeviceStatus();
  }

  protected function handleDeviceStatus(): void
  {
    $deviceData = $this->fetchDeviceStatus();

    $result = $this->searchProperty($deviceData, 'online');

    $this->notifyTelegram($result);
  }

  protected function fetchDeviceStatus(): array
  {
    $data = $this->extractData('access_data', ['url', 'client_id', 'device_id', 'secret', 'access_token']);

    $url = $this->buildDeviceUrl($data);

    $timestamp = $this->getTime();

    $sign = $this->generateSign($url, $timestamp, 'GET', $data['client_id'], $data['secret'], $data['access_token']);

    $headersData = [
      'client_id' => $data['client_id'],
      'access_token' => $data['access_token'],
      'sign' => $sign,
      't' => $timestamp
    ];

    $headers = $this->buildCurlHeaders($headersData);

    $response = $this->sendCurlRequest($url, $headers);

    return $this->fetchJson($response);
  }

  public function generateSign($url, $timestamp, $httpMethod, $clientId, $secret, $accessToken = null): string
  {

    $urlPath = parse_url($url, PHP_URL_PATH);

    if ($accessToken === null) {
      $urlPath = $urlPath . '?grant_type=1';
    }

    $stringToSign = "$httpMethod\n" . hash('sha256', '') . "\n\n$urlPath";

    $stringToSignForHmac = $clientId . ($accessToken ?? '') . $timestamp . $stringToSign;

    $hash = hash_hmac('sha256', $stringToSignForHmac, $secret, true);

    return strtoupper(bin2hex($hash));
  }

  public function loadJsonData(): array
  {
    return json_decode(file_get_contents('data.json'), true);
  }

  public function getTime(): int
  {
    return round(microtime(true) * 1000);
  }

  public function extractData(string $object, array $keys): array
  {
    $data = $this->loadJsonData();
    $accessData = $data[$object];
    $result = [];

    foreach ($keys as $key) {
      if (isset($accessData[$key])) {
        $result[$key] = $accessData[$key];
      }
    }

    return $result;
  }

  public function fetchJson(string $response): array
  {
    return json_decode($response, true);
  }

  public function searchProperty(array $arr, string $item): array
  {
    $resultArray = $arr['result'];

    foreach ($resultArray as $k => $v) {
      if ($k === $item) {
        return [$k => $v];
      }
    }
  }

  public function sendCurlRequest(string $url, array $headers = [], string $method = 'GET', array $data = []): string
  {
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    if ($method === 'POST' && !empty($data)) {
      curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;
  }

  protected function notifyTelegram(array $result): void
  {
    $telegram_bot = new Telegram();
    $telegram_bot->run($result);
  }

  private function buildDeviceUrl(array $data): string
  {
    return $data['url'] . "/v1.0/devices/{$data['device_id']}";
  }

  public function buildCurlHeaders(array $data): array
  {
    $defaultHeaders = [
      "sign_method" => "HMAC-SHA256"
    ];

    $allHeaders = array_merge($defaultHeaders, $data);

    $formattedHeaders = [];

    foreach ($allHeaders as $key => $value) {
      if (!is_null($value)) {
        $formattedHeaders[] = "$key: $value";
      }
    }

    return $formattedHeaders;
  }
}
