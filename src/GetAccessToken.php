<?php

namespace Core;

use RuntimeException;

class GetAccessToken
{

  protected BlackOutNotify $blackOutNotify;

  public function __construct(BlackOutNotify $blackOutNotify)
  {
    $this->blackOutNotify = $blackOutNotify;
  }

  public static function run(BlackOutNotify $blackOutNotify): void
  {
      $instance = new self($blackOutNotify);

      if ($instance->compareTime()) {
        $instance->getNewAccessToken();
      }
  }

  protected function compareTime(): bool
  {
    $extractData = $this->blackOutNotify->extractData('data_json', ['update_token_timestamp']);

    $oldTimestamp = (int)($extractData['update_token_timestamp'] ?? 0);
    $newTimestamp = $this->blackOutNotify->getTime();

    if ($oldTimestamp <= 0) {
      return true;
    }

    $intervalToCheck = (1 * 60 * 60 * 1000) + (50 * 60 * 1000);
    $timeDifference = $newTimestamp - $oldTimestamp;

    return $timeDifference >= $intervalToCheck;
  }

  public function getNewAccessToken(): void
  {
    $data = $this->blackOutNotify->extractConfigData('data_json', ['url', 'client_id', 'secret']);

    $timestamp = $this->blackOutNotify->getTime();
    $url = $this->buildTokenUrl($data);
    $sign = $this->blackOutNotify->generateSign($url, $timestamp, 'GET', $data['client_id'], $data['secret'], null);
    $headersData = ['client_id' => $data['client_id'], 'sign' => $sign, 't' => $timestamp];
    $headers = $this->blackOutNotify->buildCurlHeaders($headersData);
    $response = $this->blackOutNotify->sendCurlRequest($url, $headers);
    $result = $this->blackOutNotify->fetchJson($response);
    $newArr = $this->updateDataJson($result);
    $this->blackOutNotify->saveJsonData($newArr);
  }

  private function updateDataJson(array $arr): array
  {
    if (!isset($arr['result']) || !is_array($arr['result']) || !isset($arr['result']['access_token'])) {
      throw new RuntimeException('Access token response does not contain result.access_token.');
    }

    if (!isset($arr['t'])) {
      throw new RuntimeException('Access token response does not contain timestamp field "t".');
    }

    $data = $this->blackOutNotify->loadJsonData();

    if (!isset($data['data_json']) || !is_array($data['data_json'])) {
      throw new RuntimeException('Missing "data_json" object in data.json.');
    }

    $data['data_json']['access_token'] = (string)$arr['result']['access_token'];
    $data['data_json']['update_token_timestamp'] = (int)$arr['t'];

    return $data;
  }

  private function buildTokenUrl(array $data): string
  {
    return $data['url'] . "/v1.0/token?grant_type=1";
  }
}
