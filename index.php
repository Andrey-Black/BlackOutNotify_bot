<?php

require __DIR__ . '/vendor/autoload.php';

use Core\BlackOutNotify;
use Core\GetAccessToken;
use Core\Telegram;

$blackOutNotify = new BlackOutNotify(null); // Передаем временное значение null для инициализации

$telegram = new Telegram($blackOutNotify);

$blackOutNotify = new BlackOutNotify($telegram);

GetAccessToken::run($blackOutNotify, $telegram);
BlackOutNotify::run($telegram);
