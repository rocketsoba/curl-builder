<?php

namespace Curl\Exception;

use \Exception;

/**
 * Cloudflareのバイパスに失敗したときの例外
 *
 * @todo @expectedExceptionMessageというアノテーションがPHPUnitにあるのでMessageさえ渡せば必要ない？
 */
class CFBypassFailedException extends Exception
{
    /**
     * CFBypassFailedExceptionのコンストラクタ
     *
     * @param string $message
     */
    public function __construct($message = "")
    {
        parent::__construct("Bypassing Cloudflare was failed.");
    }
}
