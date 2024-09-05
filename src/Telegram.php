<?php

namespace Core;

class Telegram
{
  protected BlackOutNotify $blackOutNotify;

  public function __construct(BlackOutNotify $blackOutNotify)
  {
    $this->blackOutNotify = $blackOutNotify;
  }

  public function run(array $result): void
  {
    if ($this->CheckStatus($result)) 
    {
      $this->sendTelegramMessage();
    }
  }

  private function CheckStatus(array $status): bool
  {
    $currentStatus = $this->getCurrentStatus();

    if ($this->isStatusSame($status['online'], $currentStatus['online'])) 
    {
      return false;
    }

    $this->updateStatus($status);
    return true;
  }

  private function getCurrentStatus(): array
  {
    return $this->blackOutNotify->extractData('data_json', ['online']);
  }

  protected function isStatusSame($newStatus, $oldStatus): bool
  {
    return $newStatus === $oldStatus;
  }

  protected function updateStatus(array $status): void
  {
    $currentJsonData = $this->blackOutNotify->loadJsonData();
    $currentJsonData['data_json']['online'] = $status['online'];

    $newJsonData = $this->json_encode($currentJsonData);
    file_put_contents('data.json', $newJsonData);
  }

  protected function sendTelegramMessage(): void
  {
    $arr = $this->blackOutNotify->extractData('data_json', ['bot_token', 'chat_id']);
    $url = $this->UrlSendMessage($arr['bot_token']);
    $message = $this->formatMessage();
    $postData = ['chat_id' => $arr['chat_id'], 'text' => $message];

    $this->blackOutNotify->sendCurlRequest($url, [], 'POST', $postData);
  }

  public function json_encode(array $data): string
  {
    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  private function formatMessage(): string
  {
    $status = $this->blackOutNotify->extractData('data_json', ['online']);
    $status = reset($status);

    if ($status) 
    {
      return '🔋⚡ Відновлення електропостачання ' . '🕘 ' . date('H:i');
    } else 
    {
      return '🔌⚠️ Вимкнення електропостачання ' . '🕘 ' . date('H:i');
    }
  }

  private function UrlSendMessage(string $bot_token): string
  {
    return "https://api.telegram.org/bot{$bot_token}/sendMessage";
  }
}
