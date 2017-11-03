<?php

namespace Curl;

class MyCurl
{
    private $headers = [
        "Accept-Language: ja,en-US;q=0.7,en;q=0.3",
        "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:44.0) Gecko/20100101 Firefox/44.0",
        "Proxy-Connection:",
    ];
    private $blob_accept_header =
        "Accept: audio/webm,audio/ogg,audio/wav,audio/*;q=0.9,application/ogg;q=0.7,video/*;q=0.6,*/*;q=0.5";
    private $normal_accept_header = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
    private $curl_options = [
        CURLOPT_COOKIEFILE => __DIR__ .  "/cookie.txt",
        CURLOPT_COOKIEJAR => __DIR__ .  "/cookie.txt",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => true,
        CURLINFO_HEADER_OUT => true,
    ];

    private $blob_contents = false;
    private $curl_hundle =   null;
    private $target_url =    null;
    private $reqhead =       null;
    private $reshead =       null;
    private $body =          null;

    public function __construct(...$target_url)
    {
        if (count($target_url) == 1) {
            $this->target_url = $target_url[0];
        }
        return $this;
    }

    private function initialize()
    {
        $this->curl_hundle = curl_init();
        if ($this->blob_contents == false) {
            $this->headers[] = $this->normal_accept_header;
            $this->curl_options[CURLOPT_ENCODING] = "gzip,deflate";
        }

        /*
         * curl_setoptに渡す配列のキーは定数であり、クォートで囲ってはいけない
         * また、array_mergeを使うとキーがリセットされるので+でarrayを結合する
         * */
        $this->curl_options = [
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
        if (is_null($this->body)) {
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

    public function setURL($target_url)
    {
        if (is_string($target_url)) {
            $this->target_url = $target_url;
            return $this;
        } else {
            return -1;
        }
    }
    
    public function setBlobHeader()
    {
        $this->headers[] = $this->$blob_accept_header;
        return $this;
    }

    public function setPostData($post_params = null)
    {
        if (!is_array($post_params)) {
            return false;
        }
        
        $this->curl_options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post_params)
        ] + (array) $this->curl_options;
        
        return $this;
    }

    public function setAddtionalHeaders($add_headers)
    {
        if (is_array($add_headers)) {
            $this->headers = array_merge($this->headers, $add_headers);
            $this->blob_contents = true;
            return $this;
        } else {
            return -1;
        }
    }

    public function setProxySettings()
    {
        /* プロキシは一旦無効化*/
        
        /* curl_setopt($ch,CURLOPT_HTTPPROXYTUNNEL,true);
         * curl_setopt($ch,CURLOPT_PROXY,$tail[0]);
         * preg_match("/.+:([0-9]+)/",$tail[0],$port);
         * curl_setopt($ch,CURLOPT_PROXYTYPE,CURLPROXY_HTTP);
         * curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"GET");
         * curl_setopt($ch,CURLOPT_PROXYPORT,$port[1]);
         * curl_setopt($ch,CURLOPT_HTTPGET,true);*/
    }
}
