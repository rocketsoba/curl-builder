<?php

namespace Curl;

use \Curl\MyCurl;
use \Curl\MyCurlBuilder;
use \DomParserWrapper\DomParserAdapter;
use \CloudflareBypass\CFBypass;
use \Curl\Exception\CFBypassFailedException;

class FetchUserAgent
{
    private $ua_resource_url = "https://techblog.willshouse.com/2012/01/03/most-common-user-agents/";
    private $html_result;
    private $http_code;
    private $ua_list = [];

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

    public function fetch($url)
    {
        $curl_object = new MyCurlBuilder($url);
        $curl_object = $curl_object->build()->exec();
        $html_result = $curl_object->getResult();
        $http_code = $curl_object->getHttpCode();

        return [$html_result, $http_code];
    }
}
