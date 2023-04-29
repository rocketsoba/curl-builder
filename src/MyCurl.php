<?php

namespace Rocketsoba\Curl;

use Rocketsoba\Curl\FetchUserAgent;
use Rocketsoba\Curl\MyCurlBuilder;
use Exception;

/**
 * Curlのメインクラス
 *
 * BuilderでCurlの設定をした後、
 * 同期実行と結果取得ができるクラス
 */
class MyCurl
{
    /**
     * curlハンドル
     * @var resource|null $curl_hundle
     */
    private $curl_hundle = null;
    /**
     * ファイルポインタ
     * @var resource $fp
     */
    private $fp = null;
    /**
     * 通常時のAcceptヘッダ
     * @todo 未定義
     * @var string|null|array $normal_accept_header
     */
    private $normal_accept_header = null;
    /**
     * レスポンスボディ
     * @todo 変数名のリファクタリング
     * @var string|null $body
     */
    private $body = null;
    /**
     * レスポンスヘッダ
     * @todo 変数名のリファクタリング
     * @var string|null $reshead
     */
    private $reshead = null;
    /**
     * リクエストヘッダ
     * @todo 変数名のリファクタリング
     * @var string|null $reshead
     */
    private $reqhead = null;
    /**
     * HTTPステータスコード
     * @var int|null $http_code
     */
    private $http_code = null;
    /**
     * composerで管理しているプロジェクトのルートディレクトリ
     * @var string|null $composer_root
     */
    private $composer_root = null;

    private $headers;
    private $blob_accept_header;
    private $curl_options;
    private $blob_contents;
    private $target_url;
    private $resbody_file_path;
    private $ua_fetch_mode;
    private $cookie_delete_flag;
    private $retry_mode;
    private $retry_count;

    /**
     * ビルダーでsetされた変数をすべてこちらに移す
     *
     * @param \Curl\MyCurlBuilder $builder_object
     */
    public function __construct(MyCurlBuilder $builder_object)
    {
        $this->headers = $builder_object->getHeaders();
        $this->blob_accept_header = $builder_object->getBlobAcceptHeader();
        $this->curl_options = $builder_object->getCurlOptions();
        $this->blob_contents = $builder_object->getBlobContents();
        $this->target_url = $builder_object->getTargetUrl();
        $this->resbody_file_path = $builder_object->getResbodyFilePath();
        $this->ua_fetch_mode = $builder_object->getUAFetchMode();
        $this->cookie_delete_flag = $builder_object->getCookieDeleteFlag();
        $this->retry_mode = $builder_object->getRetryMode();
        $this->retry_count = $builder_object->getRetryCount();
    }

    /**
     * Builderパターン用のstaticメソッド
     *
     * @param string $target_url
     * @return \Curl\MyCurlBuilder
     */
    public static function createBuilder($target_url)
    {
        return new MyCurlBuilder($target_url);
    }

    /**
     * curlハンドルを生成し、設定を行う
     *
     * @todo リファクタリング
     */
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
        if ($this->cookie_delete_flag && file_exists($cookie_path)) {
            unlink($cookie_path);
        }

        if (!$this->ua_fetch_mode) {
            $useragent_path = $this->composer_root . "/.useragent.json";

            if (!($useragent = StoreUserAgent::load($useragent_path))) {
                $curl1 = new FetchUserAgent();
                $useragent = $curl1->createChromeUAString();
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

    /**
     * curlを実行する
     *
     * 非同期関数を使っていないので全体を取得するまで待たなければならない
     *
     * @return $this
     */
    public function exec()
    {
        $retry_count = 0;
        $result = false;
        if ($this->retry_mode) {
            $retry_count = $this->retry_count - 1;
        }

        foreach (range(0, $retry_count) as $val1) {
            $this->initialize();
            $result = curl_exec($this->curl_hundle);
            if ($result === false) {
                continue;
            }
            $curlinfo = curl_getinfo($this->curl_hundle);
            $this->http_code = $curlinfo["http_code"];
            $this->reqhead = $curlinfo["request_header"];
            $this->reshead = substr($result, 0, $curlinfo["header_size"]);
            $this->body = substr($result, $curlinfo["header_size"]);
            curl_close($this->curl_hundle);
            /**
             * curl_close()後にデストラクタが呼ばれないとCURLOPT_COOKIEJARにcookieが書き込まれない？
             */
            $this->curl_hundle = null;
            return $this;
        }

        if ($result === false) {
            throw new Exception("curl_errno: " . curl_errno($this->curl_hundle) . PHP_EOL . "curl_error: " . curl_error($this->curl_hundle));
        }
        return $this;
    }

    /**
     * レスポンスボディを返す
     *
     * exec()が実行されてなければ実行される
     *
     * @todo リファクタリング
     * @return string
     */
    public function getResult()
    {
        if (is_null($this->body)) {
            $this->exec();
        }
        return $this->body;
    }

    /**
     * レスポンスヘッダを返す
     *
     * exec()が実行されてなければ実行される
     *
     * @todo リファクタリング
     * @return string
     */
    public function getReshead()
    {
        if (is_null($this->reshead)) {
            $this->exec();
        }
        return $this->reshead;
    }

    /**
     * リクエストヘッダを返す
     *
     * exec()が実行されてなければ実行される
     *
     * @todo リファクタリング
     * @return string
     */
    public function getReqhead()
    {
        if (is_null($this->reqhead)) {
            $this->exec();
        }
        return $this->reqhead;
    }

    /**
     * HTTPステータスコード返す
     *
     * exec()が実行されてなければ実行される
     *
     * @return int
     */
    public function getHttpCode()
    {
        if (is_null($this->http_code)) {
            $this->exec();
        }
        return $this->http_code;
    }

    /**
     * .cookie.txtの場所を返す
     *
     * @return string
     */
    public function getCookieLocation()
    {
        if (is_null($this->composer_root)) {
            $this->initialize();
        }
        return $this->composer_root  . "/.cookie.txt";
    }

    /**
     * 保存されているすべてのcookieを返す
     *
     * @return array
     * @todo deleteの機能を実装する
     */
    public function getStoredCookiesAll()
    {
        $cookie_path = $this->getCookieLocation();
        if (!file_exists($cookie_path)) {
            return [];
        }

        $cookie_array = explode("\n", file_get_contents($cookie_path));
        $cookie_array = array_map(function ($value) {
            if (preg_match('/^# /', $value) || $value === "") {
                return false;
            }
            if (($result = preg_split('/\t/', $value)) === false || count($result) !== 7) {
                return false;
            }

            $cookie = [
                "domain" =>            str_replace('#HttpOnly_', '', $result[0]),
                "is_httponly" =>       strpos($result[0], '#HttpOnly_') !== false,
                "include_subdomain" => $result[1] === "TRUE" ? true : false,
                "path" =>              $result[2],
                "https_only" =>        $result[3] === "TRUE" ? true : false,
                "expires_at" =>        (int)$result[4],
                "name" =>              $result[5],
                "value" =>             $result[6],
            ];

            return $cookie;
        }, $cookie_array);
        $cookie_array = array_values(array_filter($cookie_array));

        return $cookie_array;
    }

    /**
     * $this->target_urlで使われるcookie一覧を返す
     *
     * @return string
     */
    public function getCookies()
    {
        $url_info = parse_url($this->target_url);
        $current_unixtime = time();
        $cookie_array = $this->getStoredCookiesAll();
        $cookie_array = array_map(function ($value) use ($url_info, $current_unixtime) {
            if ($value["expires_at"] < $current_unixtime && $value["expires_at"] !== 0) {
                return false;
            }

            if ($value["https_only"] && $url_info["scheme"] !== "https") {
                return false;
            }

            if ($value["include_subdomain"]) {
                if (strpos($url_info["host"], preg_replace('/^\./', '', $value["domain"])) === false) {
                    return false;
                }
            } else {
                if ($url_info["host"] !== $value["domain"]) {
                    return false;
                }
            }

            if (strpos($url_info["path"], $value["path"]) === false) {
                return false;
            }

            return $value;
        }, $cookie_array);
        $cookie_array = array_values(array_filter($cookie_array));

        return $cookie_array;
    }

    /**
     * $this->target_urlで使われるcookieの中で指定した名前を持つcookieを返す
     *
     * @return string
     */
    public function getCookieValueByName($name)
    {
        $cookie_array = $this->getCookies();
        $cookie_array = array_map(function ($value) use ($name) {
            if ($value["name"] !== $name) {
                return false;
            }

            return $value;
        }, $cookie_array);
        $cookie_array = array_values(array_filter($cookie_array));

        if (count($cookie_array) === 1) {
            return $cookie_array[0]["value"];
        } else {
            return "";
        }
    }
}
