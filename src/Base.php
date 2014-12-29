<?php
namespace Gongo\MercifulPolluter;

class Base
{
    /**
     * @see http://php.net/manual/en/reserved.variables.php
     */
    private static $ignoringVariableNames = array(
        'GLOBALS',
        '_SERVER',
        '_GET',
        '_POST',
        '_FILES',
        '_REQUEST',
        '_SESSIONIDENV',
        '_COOKIE',
        'php_errormsg',
        'HTTP_RAW_POST_DATA',
        'http_response_header',
        'argc',
        'argv',
    );

    protected function ignoringVariable($theKey)
    {
        return in_array($theKey, self::$ignoringVariableNames);
    }
}
