<?php

namespace Core;

class GetAccessToken
{

  protected BlackOutNotify $blackOutNotify;
  protected Telegram $telegram;

  public function __construct(BlackOutNotify $blackOutNotify, Telegram $telegram)
  {
    $this->blackOutNotify = $blackOutNotify;
    $this->telegram = $telegram;
  }

  public static function run(BlackOutNotify $blackOutNotify, Telegram $telegram): void
  {
      $instance = new self($blackOutNotify, $telegram);

      if ($instance->compareTime()) {
        $instance->getNewAccessToken();
      }
  }

  protected function compareTime(): bool
  {
    $extractData = $this->blackOutNotify->extractData('data_json', ['update_token_timestamp']);

    $oldTimestamp = reset($extractData);
    $newTimestamp = $this->blackOutNotify->getTime();

    // Интервал, который нужно проверить (1 час 50 минут в миллисекундах)
    $intervalToCheck = (1 * 60 * 60 * 1000) + (50 * 60 * 1000);

    $timeDifference = abs($newTimestamp - $oldTimestamp);

    if($timeDifference < $intervalToCheck) return false;

    return true;
  }

  public function getNewAccessToken()
  {
    $data = $this->blackOutNotify->extractData('data_json', ['url', 'client_id', 'device_id', 'secret']);

    $timestamp = $this->blackOutNotify->getTime();

    $url = $this->buildTokenUrl($data);

    $sign = $this->blackOutNotify->generateSign($url, $timestamp, 'GET', $data['client_id'], $data['secret'], null);

    $data =['client_id' => $data['client_id'], 'sign' => $sign, 't' => $timestamp];

    $headers = $this->blackOutNotify->buildCurlHeaders($data);

    $response = $this->blackOutNotify->sendCurlRequest($url, $headers);

    $result = $this->blackOutNotify->fetchJson($response);

    $newArr = $this->updateDataJson($result);

    $newJsonData = $this->telegram->json_encode($newArr);

    file_put_contents('data.json', $newJsonData);
  }

  private function updateDataJson (array $arr): array
  {

    $data = $this->blackOutNotify->loadJsonData();

    $data['data_json']['access_token'] = $arr['result']['access_token'];
    $data['data_json']['update_token_timestamp'] = $arr['t'];

    return $data;
  }

  private function buildTokenUrl(array $data): string
  {
    return $data['url'] . "/v1.0/token?grant_type=1";
  }
}
