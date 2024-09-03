<?php

require __DIR__ . '/vendor/autoload.php';

use Core\BlackOutNotify;
use Core\GetAccessToken;

GetAccessToken::run();

BlackOutNotify::run();
