<?php

namespace Core;

class Telegram
{

  protected BlackOutNotify $fetchStatus;

  public function __construct()
  {
    $this->fetchStatus = new BlackOutNotify();
  }

  public function run (array $result): void
  {
    $this->CheckStatus($result);
    $this->sendTelegramMessage();
  }

  protected function CheckStatus (array $newStatus): bool
  {

    $currentStatus = $this->fetchStatus->extractData('status', ['online']);

    if($newStatus['online'] === $currentStatus['online'])
    {
      exit('<h2>STATUS SAME</h2>');
    }
    else
    {
      $currentJsonData = $this->fetchStatus->loadJsonData();

      $currentJsonData['status']['online'] = $newStatus['online'];

      $newJsonData = json_encode($currentJsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

      file_put_contents('data.json', $newJsonData);

      echo '<h2>UPDATE STATUS</h2>';

      return true;
    }

  }

  protected function sendTelegramMessage(): void
  {
    $arr = $this->fetchStatus->extractData('telegram', ['bot_token', 'chat_id']);

    $url = "https://api.telegram.org/bot{$arr['bot_token']}/sendMessage";

    $postData = [
      'chat_id' => $arr['chat_id'],
      'text' => 'Привет я BlackOutNotify_bot'
  ];

    $response = $this->fetchStatus->sendCurlRequest($url, [], 'POST', $postData);

    echo $response;
  }

}
