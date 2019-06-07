<?php

namespace Curl;

use \Curl\MyCurl;
use \Curl\MyCurlBuilder;
use \DomParserWrapper\DomParserAdapter;
use \CloudflareBypass\CFBypass;
use \Curl\Exception\CFBypassFailedException;

/**
 * UserAgentを取得するクラス
 */
class FetchUserAgent
{
    /**
     * UserAgentのリストを取得するサイトのURL
     * @var string $ua_resource_url
     */
    private $ua_resource_url = "https://techblog.willshouse.com/2012/01/03/most-common-user-agents/";
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
     * UserAgentのリスト
     * @var array $ua_list
     */
    private $ua_list = [];

    /**
     * FetchUserAgentのコンストラクタ
     *
     * Cloudflareのバイパスが必要であれば実行し、その後DOM走査
     * UserAgentのリストを取得する
     *
     * @todo ロジックの分離、リファクタリング
     * @throws CFBypassFailedException バイパスを実行したがバイパスページが再度読み込まれてしまう場合
     */
    public function __construct()
    {
        list($this->html_result, $this->http_code) = $this->fetch($this->ua_resource_url);

        if (CFBypass::isBypassable($this->html_result, $this->http_code)) {
            list($this->html_result, $this->http_code) = $this->execBypass($this->html_result, $this->ua_resource_url);
            if (CFBypass::isBypassable($this->html_result, $this->http_code)) {
                throw new CFBypassFailedException();
            }
        }

        $this->ua_list = $this->getUAList($this->html_result);
    }

    /**
     * Cloudflareのバイパスを実行する
     *
     * @todo URI取得とfetchを分離するべき？
     * @param string $raw_html
     * @param string $target_url
     */
    public function execBypass($raw_html, $target_url)
    {
        $input_value = CFBypass::bypass($raw_html, $target_url);
        $form_list = [
            "s" => $input_value[0],
            "jschl_vc" => $input_value[1],
            "pass" => $input_value[2],
            "jschl_answer" => $input_value[3],
        ];
        $parsed_url = parse_url($target_url);
        $constructed_uri = $parsed_url["scheme"] . "://" . $parsed_url["host"] . "/cdn-cgi/l/chk_jschl?" .
                           http_build_query($form_list);
        sleep(5);

        return $this->fetch($constructed_uri);
    }

    /**
     * 一番使われている(現在の最新版と考える)FirefoxのUserAgnetを取得する
     *
     * @return string
     */
    public function getMostUsedFirefoxUA()
    {
        $target_ua = "";
        foreach ($this->ua_list as $idx1 => $val1) {
            if (strpos($val1["name"], "Firefox") !== false) {
                $target_ua = $val1["name"];
                break;
            }
        }

        return $target_ua;
    }

    /**
     * 与えられたHTMLからUserAgentがある要素を走査し、配列に格納する
     *
     * @todo throw
     * @param string $raw_html
     * @return array
     */
    public function getUAList($raw_html)
    {
        try {
            $dom = new DomParserAdapter($raw_html);
            $dom->findOne("table")->findMany("tr");
            foreach ($dom as $idx1 => $val1) {
                try {
                    $ua_percent_elem = clone $val1;
                    $ua_name_elem = clone $val1;

                    $ua_percent_elem->findOne("td.percent");
                    $ua_percent = $ua_percent_elem->plaintext;
                    $ua_name_elem->findOne("td.useragent");
                    $ua_name = $ua_name_elem->plaintext;

                    $ua_list[] = [
                        "percent" => $ua_percent,
                        "name" => $ua_name,
                    ];
                } catch (\Exception $exception) {
                    /* 要素が存在しないときは握りつぶす */
                }
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
        }

        return $ua_list;
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
