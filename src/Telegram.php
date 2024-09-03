<?php

namespace Core;

class Telegram
{
  protected BlackOutNotify $BlackOutNotify;

  public function __construct()
  {
    $this->BlackOutNotify = new BlackOutNotify();
  }

  public function run(array $result): void
  {
    if ($this->CheckStatus($result)) {
      $this->sendTelegramMessage();
    }
  }

  protected function CheckStatus(array $status): bool
  {
    $currentStatus = $this->getCurrentStatus();

    if ($this->isStatusSame($status['online'], $currentStatus['online'])) {
      $this->handleSameStatus();
      return false;
    }

    $this->updateStatus($status);
    echo '<h2>UPDATE STATUS</h2>';
    return true;
  }

  protected function getCurrentStatus(): array
  {
    return $this->BlackOutNotify->extractData('status', ['online']);
  }
  protected function isStatusSame($newStatus, $oldStatus): bool
  {
    return $newStatus === $oldStatus;
  }

  protected function handleSameStatus(): void
  {
    exit('<h2>STATUS SAME</h2>');
  }
  protected function updateStatus(array $status): void
  {
    $currentJsonData = $this->BlackOutNotify->loadJsonData();
    $currentJsonData['status']['online'] = $status['online'];

    $newJsonData = $this->json_encode($currentJsonData);
    file_put_contents('data.json', $newJsonData);
  }

  protected function sendTelegramMessage(): void
  {
    $arr = $this->BlackOutNotify->extractData('telegram', ['bot_token', 'chat_id']);

    $url = "https://api.telegram.org/bot{$arr['bot_token']}/sendMessage";

    $postData = [
      'chat_id' => $arr['chat_id'],
      'text' => 'Привет я BlackOutNotify_bot'
    ];

    $response = $this->BlackOutNotify->sendCurlRequest($url, [], 'POST', $postData);

    echo '<h3>' . $response . '</h3>';
  }

  public function json_encode(array $data): string
  {
    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }
}
