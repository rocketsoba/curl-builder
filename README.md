# curl-builder

PHP HTTP client class by curl with Effective Java builder pattern.

## Features

* Implemented by Effective Java builder pattern
* Easy HTTP GET and POST method
* Persistent Cookie
* Auto fetch of latest Chrome UserAgent

## Installation

```
# This library is not available on Packagist, so you need to add repository manually.
composer config repositories.curl-builder '{"type": "vcs", "url": "https://github.com/rocketsoba/curl-builder", "no-api": true}'
composer require rocketsoba/curl-builder
```

## Usage

```php
<?php

use Rocketsoba\Curl\MyCurlBuilder;

/**
 * HTTP GET
 */
// Create Builder and build
$curl_object = (new MyCurlBuilder("https://httpbin.org/get"))->build();

// Response body
echo $curl_object->getResult();
// Response header
echo $curl_object->getReshead();
// Request header
echo $curl_object->getReqhead();


/**
 * HTTP POST
 * If you specify POST data, HTTP method is automatically changed to POST.
 */
$curl_object = (new MyCurlBuilder("https://httpbin.org/post"))->setPostData(["test" => "hoge"])
                                                              ->build();

echo $curl_object->getResult();
echo $curl_object->getReshead();
echo $curl_object->getReqhead();
```