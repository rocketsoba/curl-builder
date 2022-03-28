<?php

namespace Rocketsoba\Curl;

/**
 * MyCurlのBuilderクラス
 *
 * Effective Java Builderのclassを分離したパターンを使用
 * (PHP5.6以下ではインナークラスがないため)
 */
class MyCurlBuilder
{
    /**
     * 基本のリクエストヘッダの配列
     * @var array $headers
     */
    private $headers = [
        "Accept-Language: ja,en-US;q=0.7,en;q=0.3",
        "Proxy-Connection:",
    ];
    /**
     * Blob用のリクエストヘッダ(Accept)
     * @var string $blob_accept_header
     */
    private $blob_accept_header =
        "Accept: audio/webm,audio/ogg,audio/wav,audio/*;q=0.9,application/ogg;q=0.7,video/*;q=0.6,*/*;q=0.5";
    /**
     * curl_setopt_arrayに渡す配列
     * @var array $curl_options
     */
    private $curl_options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER =>         true,
        CURLINFO_HEADER_OUT =>    true,
    ];
    /**
     * 接続するURL
     * @var string|null $target_url
     */
    private $target_url =        null;
    /**
     * ファイルを書き込むときのパス
     * @var string|null
     */
    private $resbody_file_path = null;
    /** @var bool $ua_fetch_mode */
    private $ua_fetch_mode =     false;
    /** @var bool $blob_contents */
    private $blob_contents =     false;
    /** @var bool $cookie_delete_flag */
    private $cookie_delete_flag = false;
    /** @var bool $retry_mode */
    private $retry_mode = false;
    /** @var int $retry_count */
    private $retry_count = 0;

    /**
     * MyCurlBuilderのコンストラクタ
     *
     * @param string $target_url 接続するURL
     */
    public function __construct($target_url)
    {
        $this->target_url = $target_url;
    }
    /**
     * Builderのビルド
     *
     * @return \Curl\MyCurl
     */
    public function build()
    {
        return new MyCurl($this);
    }

    /**
     * Blobを取得するモードにする
     *
     * @todo メソッド名のリファクタリング
     * @return $this
     */
    public function setBlobHeader()
    {
        $this->headers[] = $this->blob_accept_header;
        $this->blob_contents =true;
        return $this;
    }

    /**
     * HTTPメソッドをPOSTにし、
     * application/x-www-form-urlencoded形式で渡された連想配列を投げる
     *
     * @param array $post_params
     * @return $this
     */
    public function setPostData($post_params)
    {
        $this->curl_options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post_params)
        ] + (array) $this->curl_options;

        return $this;
    }

    public function setPlainPostData($plain_text)
    {
        $this->curl_options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $plain_text
        ] + (array) $this->curl_options;
        $this->setAddtionalHeaders(["Content-Type: application/json"]);

        return $this;
    }

    /**
     * リクエストヘッダを追加する
     *
     * @todo メソッド名のリファクタリング
     * @param array $add_headers
     * @return $this
     */
    public function setAddtionalHeaders($add_headers)
    {
        $this->headers = array_merge($this->headers, $add_headers);
        $this->blob_contents = true;
        return $this;
    }

    /**
     * ファイルを書き出すモードにする
     *
     * @todo メソッド名のリファクタリング
     * @param string $file_dest
     * @return $this
     */
    public function setFilePointerMode($file_dest)
    {
        /* CURLOPT_FILEが先、CURLOPT_RETURNTRANSFERが後じゃないとメモリ爆食いする
         * CURLOPT_HEADERをfalseにしないとレスポンスヘッダも吐かれる */
        $resbody_file_path = $file_dest;
        return $this;
    }

    /**
     * エラー時のリトライを有効にしてリトライ回数を指定する
     *
     * @param int $retry_count
     * @return $this
     */
    public function setRetryCount($retry_count = 0)
    {
        $this->retry_mode = true;
        $this->retry_count = $retry_count;
        return $this;
    }

    /**
     * UserAgentを取得するモードにする
     *
     * オブジェクト生成ループを防ぐために使う
     *
     * @return $this
     */
    public function enableUAFetchMode()
    {
        $this->ua_fetch_mode = true;
        return $this;
    }

    /**
     * cookieを削除する
     *
     * @return $this
     */
    public function deleteCookie()
    {
        $this->cookie_delete_flag = true;
        return $this;
    }

    /** @return array */
    public function getHeaders()
    {
        return $this->headers;
    }

    /** @return string */
    public function getBlobAcceptHeader()
    {
        return $this->blob_accept_header;
    }

    /** @return array */
    public function getCurlOptions()
    {
        return $this->curl_options;
    }

    /** @return string */
    public function getTargetUrl()
    {
        return $this->target_url;
    }

    /** @return string|null */
    public function getResbodyFilePath()
    {
        return $this->resbody_file_path;
    }

    /** @return bool */
    public function getBlobContents()
    {
        return $this->blob_contents;
    }

    /** @return bool */
    public function getUAFetchMode()
    {
        return $this->ua_fetch_mode;
    }

    /** @return bool */
    public function getCookieDeleteFlag()
    {
        return $this->cookie_delete_flag;
    }

    /** @return bool */
    public function getRetryMode()
    {
        return $this->retry_mode;
    }

    /** @return int */
    public function getRetryCount()
    {
        return $this->retry_count;
    }
}
