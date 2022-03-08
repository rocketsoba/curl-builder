<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Rocketsoba\Curl\MyCurl;
use Rocketsoba\Curl\MyCurlBuilder;
use Rocketsoba\Curl\FetchUserAgent;

$lib = new FetchUserAgent();
echo $lib->createChromeUAString() . PHP_EOL;
