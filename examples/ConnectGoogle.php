<?php

date_default_timezone_set("Asia/Tokyo");
ini_set("arg_separator.output", "&");
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);
ini_set('xdebug.var_display_max_depth', -1);

require_once __DIR__ . "/../vendor/autoload.php";

use Rocketsoba\Curl\MyCurl;
use Rocketsoba\Curl\MyCurlBuilder;

$curl1 =new MyCurlBuilder("https://www.google.com");
$curl1 = $curl1->build();
echo $curl1->getReqHead() . PHP_EOL;
echo $curl1->getReshead() . PHP_EOL;
