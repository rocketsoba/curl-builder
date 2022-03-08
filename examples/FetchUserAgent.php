<?php

date_default_timezone_set("Asia/Tokyo");
ini_set("arg_separator.output", "&");
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);
ini_set('xdebug.var_display_max_depth', -1);

require_once __DIR__ . "/../vendor/autoload.php";

use Rocketsoba\Curl\MyCurl;
use Rocketsoba\Curl\MyCurlBuilder;
use Rocketsoba\Curl\FetchUserAgent;

$lib = new FetchUserAgent();
echo $lib->createChromeUAString() . PHP_EOL;
