<?php

namespace Curl;

use \Curl\FetchUserAgent;

class MyCurl
{
    private $headers;
    private $blob_accept_header;
    private $curl_options;
    private $blob_contents;
    private $target_url;
    private $resbody_file_path;
    private $ua_fetch_mode;

    private $curl_hundle = null;
    private $normal_accept_header = null;
    private $fp = null;
    private $body = null;
    private $reqhead = null;
    private $reshead = null;
    private $http_code = null;
    private $composer_root = null;

    public static function createBuilder($target_url)
    {
        return new MyCurlBuilder($target_url);
    }

    public function __construct($builder_object)
    {
        $this->headers = $builder_object->getHeaders();
        $this->blob_accept_header = $builder_object->getBlobAcceptHeader();
        $this->curl_options = $builder_object->getCurlOptions();
        $this->blob_contents = $builder_object->getBlobContents();
        $this->target_url = $builder_object->getTargetUrl();
        $this->resbody_file_path = $builder_object->getResbodyFilePath();
        $this->ua_fetch_mode = $builder_object->getUAFetchMode();
    }

    public function initialize()
    {
        $this->curl_hundle = curl_init();
        if ($this->blob_contents == false) {
            $this->headers[] = $this->normal_accept_header;
            $this->curl_options[CURLOPT_ENCODING] = "gzip,deflate";
        }
        if (!is_null($this->resbody_file_path)) {
            $this->fp = fopen($this->resbody_file_path, "w");
            $this->curl_options =
                (array) $this->curl_options +
                [
                    CURLOPT_FILE => $this->fp
                ];
            $this->curl_options[CURLOPT_HEADER] = false;
        }

        $composer_autoloader_reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
        $this->composer_root = dirname(dirname(dirname($composer_autoloader_reflection->getFileName())));
        $cookie_path = $this->composer_root  . "/.cookie.txt";

        if (!$this->ua_fetch_mode) {
            $useragent_path = $this->composer_root . "/.useragent.json";

            if (!($useragent = StoreUserAgent::load($useragent_path))) {
                $curl1 = new FetchUserAgent();
                $useragent = $curl1->getMostUsedFirefoxUA();
                $useragent_info = [
                    "useragent" => $useragent,
                    "fetched-date" => date("Y-m-d H:i:s"),
                ];
                StoreUserAgent::store($useragent_path, $useragent_info);
            }

            $this->headers[] = "User-Agent: " . $useragent;
        }

        /*
         * curl_setoptに渡す配列のキーは定数であり、クォートで囲ってはいけない
         * また、array_mergeを使うとキーがリセットされるので+でarrayを結合する
         * */

        $this->curl_options = [
            CURLOPT_COOKIEFILE => $cookie_path,
            CURLOPT_COOKIEJAR => $cookie_path,
            CURLOPT_URL => $this->target_url,
            CURLOPT_HTTPHEADER => $this->headers,
        ] + (array) $this->curl_options;
        curl_setopt_array($this->curl_hundle, $this->curl_options);
    }

    /* 非同期関数を使っていないので全体を取得するまで待たなければならない */
    public function exec()
    {
        $this->initialize();
        $result = curl_exec($this->curl_hundle);
        $curlinfo = curl_getinfo($this->curl_hundle);
        $this->http_code = $curlinfo["http_code"];
        $this->reqhead = $curlinfo["request_header"];
        $this->reshead = substr($result, 0, $curlinfo["header_size"]);
        $this->body = substr($result, $curlinfo["header_size"]);
        curl_close($this->curl_hundle);
        return $this;
    }

    public function getResult()
    {
        if (is_null($this->body)) {
            $this->exec();
        }
        return $this->body;
    }

    public function getReshead()
    {
        if (is_null($this->reshead)) {
            $this->exec();
        }
        return $this->reshead;
    }

    public function getReqhead()
    {
        if (is_null($this->reqhead)) {
            $this->exec();
        }
        return $this->reqhead;
    }

    public function getHttpCode()
    {
        if (is_null($this->http_code)) {
            $this->exec();
        }
        return $this->http_code;
    }
}
