<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Rocketsoba\Curl\MyCurl;
use Rocketsoba\Curl\MyCurlBuilder;

$curl1 =new MyCurlBuilder("https://www.google.com");
$curl1 = $curl1->build();
echo $curl1->getReqHead() . PHP_EOL;
echo $curl1->getReshead() . PHP_EOL;
