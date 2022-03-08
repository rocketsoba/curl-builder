<?php

namespace Rocketsoba\Curl;

use Exception;
use Rocketsoba\Curl\MyCurl;
use Rocketsoba\Curl\MyCurlBuilder;

/**
 * UserAgentを取得するクラス
 */
class FetchUserAgent
{
    /**
     * Chromeのバージョンを取得するサイトのURL
     * @var string $chrome_resource_url
     */
    private $chrome_resource_url = "https://chocolatey.org/packages/GoogleChrome";
    /**
     * 取得したHTML
     * @var string $html_result
     */
    private $html_result;
    /**
     * HTTPステータスコード
     * @var int $http_code
     */
    private $http_code;
    /**
     * 最新版Chromeのバージョン
     * @var string $chrome_version
     */
    private $chrome_version = '';

    /**
     * FetchUserAgentのコンストラクタ
     *
     * Chromeの最新バージョンを取得し
     * UserAgentの文字列を構築する
     *
     * @todo ロジックの分離、リファクタリング
     * @todo firefox版、モバイル版も追加
     * @todo 例外クラスを分離
     */
    public function __construct()
    {
        $chrome_chocolatey_html = $this->fetch($this->chrome_resource_url);
        $this->chrome_version = $this->scrapeLatestChromeVersion($chrome_chocolatey_html[0]);
    }

    /**
     * ChocolateyのChromeのHistoryを取得し最新バージョンを返す
     *
     * @param string $raw_html
     * @return string
     */
    public function scrapeLatestChromeVersion($raw_html)
    {
        try {
            if (! preg_match('/"Latest Version"\s*>\s*<span>Google Chrome ([0-9\.]+)/', $raw_html, $chrome_version)) {
                throw new Exception("invalid version string");
            }

            array_shift($chrome_version);
        } catch (\Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
        }

        return $chrome_version[0];
    }

    /**
     * 最新のChromeのUserAgnetを構築する
     *
     * @return string
     */
    public function createChromeUAString()
    {
        return "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/"
             . $this->chrome_version ." Safari/537.36";
    }

    /**
     * URLに接続し、取得したHTML、HTTPステータスコードをメンバ変数に格納する
     *
     * @param string $url
     * @return array
     */
    public function fetch($url)
    {
        $curl_object = new MyCurlBuilder($url);
        $curl_object = $curl_object->enableUAFetchMode()
                                   ->build()
                                   ->exec();
        $html_result = $curl_object->getResult();
        $http_code = $curl_object->getHttpCode();

        return [$html_result, $http_code];
    }
}
