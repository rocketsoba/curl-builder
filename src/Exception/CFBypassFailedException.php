<?php

namespace \Curl\Exception;

use \Exception;

class CFBypassFailedException extends Exception
{
    public function __construct($message = "")
    {
        parent::__construct("Bypassing Cloudflare was failed.");
    }
}
