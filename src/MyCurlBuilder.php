<?php

namespace Curl;

class MyCurlBuilder
{
    private $headers = [
        "Accept-Language: ja,en-US;q=0.7,en;q=0.3",
        "Proxy-Connection:",
    ];
    private $blob_accept_header =
        "Accept: audio/webm,audio/ogg,audio/wav,audio/*;q=0.9,application/ogg;q=0.7,video/*;q=0.6,*/*;q=0.5";
    private $curl_options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER =>         true,
        CURLINFO_HEADER_OUT =>    true,
    ];

    private $blob_contents =     false;
    private $target_url =        null;
    private $resbody_file_path = null;
    private $ua_fetch_mode =     false;

    public function __construct($target_url)
    {
        $this->target_url = $target_url;
    }

    public function build()
    {
        return new MyCurl($this);
    }

    public function setBlobHeader()
    {
        $this->headers[] = $this->blob_accept_header;
        $this->blob_contents =true;
        return $this;
    }

    public function setPostData($post_params)
    {
        $this->curl_options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post_params)
        ] + (array) $this->curl_options;

        return $this;
    }

    public function setAddtionalHeaders($add_headers)
    {
        $this->headers = array_merge($this->headers, $add_headers);
        $this->blob_contents = true;
        return $this;
    }

    public function setFilePointerMode($file_dest)
    {
        /* CURLOPT_FILEが先、CURLOPT_RETURNTRANSFERが後じゃないとメモリ爆食いする
         * CURLOPT_HEADERをfalseにしないとレスポンスヘッダも吐かれる */
        $resbody_file_path = $file_dest;
        return $this;
    }

    public function enableUAFetchMode()
    {
        $this->ua_fetch_mode = true;
        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getBlobAcceptHeader()
    {
        return $this->blob_accept_header;
    }

    public function getCurlOptions()
    {
        return $this->curl_options;
    }

    public function getBlobContents()
    {
        return $this->blob_contents;
    }

    public function getTargetUrl()
    {
        return $this->target_url;
    }

    public function getResbodyFilePath()
    {
        return $this->resbody_file_path;
    }

    public function getUAFetchMode()
    {
        return $this->ua_fetch_mode;
    }
}
