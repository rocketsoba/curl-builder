<?php

namespace Curl;

use \Curl\FetchUserAgent;

/**
 * UserAgentの保存、読込をするクラス
 */
class StoreUserAgent
{
    /**
     * UserAgentを保存
     *
     * @param string $target_path 保存先
     * @param array $ua_info jsonにして保存される配列
     * @return string
     */
    public static function store($target_path, $ua_info)
    {
        $ua_json = json_encode($ua_info, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $write_status = file_put_contents($target_path, $ua_json);

        return $write_status;
    }

    /**
     * UserAgentの読込
     *
     * @param string $target_path 読込先
     * @return array
     */
    public static function load($target_path)
    {
        if (!file_exists($target_path)) {
            return false;
        }

        $ua_info = json_decode(file_get_contents($target_path), true);

        return $ua_info["useragent"];
    }
}
