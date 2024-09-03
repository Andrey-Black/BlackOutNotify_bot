<?php

namespace Core;

class GetAccessToken
{

  protected $BlackOutNotify;
  protected $Telegram;

  public function __construct()
  {
    $this->BlackOutNotify = new BlackOutNotify();
    $this->Telegram = new Telegram();
  }

  public static function run(): void
  {
    if (self::compareTime()) {
      $instance = new self();
      $instance->getNewAccessToken();
    }
  }

  protected static function compareTime()
  {
    return true;
  }

  public function getNewAccessToken()
  {

    $data = $this->BlackOutNotify->extractData('access_data', ['url', 'client_id', 'device_id', 'secret']);

    $timestamp = $this->BlackOutNotify->getTime();

    $url = $this->buildTokenUrl($data);

    $sign = $this->BlackOutNotify->generateSign($url, $timestamp, 'GET', $data['client_id'], $data['secret'], null);

    $data =
      [
        'client_id' => $data['client_id'],
        'sign' => $sign,
        't' => $timestamp
      ];

    $headers = $this->BlackOutNotify->buildCurlHeaders($data);

    $response = $this->BlackOutNotify->sendCurlRequest($url, $headers);

    $result = $this->BlackOutNotify->fetchJson($response);

    $currentJsonData = $this->BlackOutNotify->loadJsonData();

    $currentJsonData['access_data']['access_token'] = $result['result']['access_token'];

    $newJsonData = $this->Telegram->json_encode($currentJsonData);

    file_put_contents('data.json', $newJsonData);
  }

  private function buildTokenUrl(array $data): string
  {
    return $data['url'] . "/v1.0/token?grant_type=1";
  }
}
