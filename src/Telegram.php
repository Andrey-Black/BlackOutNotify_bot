<?php

namespace Core;

use RuntimeException;

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
    if (!array_key_exists('online', $status)) {
      throw new RuntimeException('Missing "online" status in notifier payload.');
    }

    $currentStatus = $this->getCurrentStatus();

    if (array_key_exists('online', $currentStatus) && $this->isStatusSame($status['online'], $currentStatus['online'])) 
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

    $this->blackOutNotify->saveJsonData($currentJsonData);
  }

  protected function sendTelegramMessage(): void
  {
    $arr = $this->blackOutNotify->extractConfigData('data_json', ['bot_token', 'chat_id']);
    $url = $this->UrlSendMessage($arr['bot_token']);
    $message = $this->formatMessage();
    $postData = ['chat_id' => $arr['chat_id'], 'text' => $message];

    $this->blackOutNotify->sendCurlRequest($url, [], 'POST', $postData);
  }

  private function formatMessage(): string
  {
    $status = $this->blackOutNotify->extractData('data_json', ['online']);
    $isOnline = (bool)($status['online'] ?? false);

    if ($isOnline) 
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
