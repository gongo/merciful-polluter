<?php
namespace Gongo\MercifulPolluter;

class Base
{
    /**
     * @see http://php.net/manual/en/reserved.variables.php
     * @var string[]
     */
    private static $ignoringVariableNames = array(
        'GLOBALS',
        '_SERVER',
        '_GET',
        '_POST',
        '_FILES',
        '_REQUEST',
        '_SESSION',
        '_ENV',
        '_COOKIE',
        'php_errormsg',
        'HTTP_RAW_POST_DATA',
        'http_response_header',
        'argc',
        'argv',
    );

    /** @var (callable(array<int, array{name: string, origin: string|null}>): void)|null */
    private $reporter = null;

    /** @var array<int, array{name: string, origin: string|null}> */
    private $injectedVariables = array();

    /**
     * Report injected variables as a E_USER_DEPRECATED per `pollute()`.
     *
     * It helps to find the code that may depend on `register_globals`.
     * Variables injected from $_SERVER and $_ENV are not reported, because they are too many.
     * Variables with invalid names (e.g. "a b", "<script>") are not reported, because code cannot read them as `$name`.
     * Variables with names longer than 64 bytes are only counted.
     *
     *     MercifulPolluter: 2 variables injected in /index.php (may depend on register_globals): $id from $_GET['id'], $name from $_POST['name']
     *
     * @return void
     */
    public function enableReport()
    {
        $this->reporter = function (array $theInjectedVariables) {
            $items = array();
            $tooLong = 0;
            foreach ($theInjectedVariables as $variable) {
                $origin = $variable['origin'];
                if ($origin !== null && (strpos($origin, '$_SERVER[') === 0 || strpos($origin, '$_ENV[') === 0)) {
                    continue;
                }
                // Names come from users and go to logs and screens (html_errors does not escape them).
                if (!preg_match('/\A[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*\z/', $variable['name'])) {
                    continue;
                }
                // Long names are counted but not listed, so that they cannot flood the logs.
                if (strlen($variable['name']) > 64) {
                    $tooLong++;
                    continue;
                }
                $items[] = $origin === null ? "\${$variable['name']}" : "\${$variable['name']} from {$origin}";
            }

            $count = count($items) + $tooLong;
            if ($count === 0) {
                return;
            }

            $limit = 10;
            $list = implode(', ', array_slice($items, 0, $limit));
            if (count($items) > $limit) {
                $list .= sprintf(' and %d more', count($items) - $limit);
            }
            if ($tooLong > 0) {
                $list .= sprintf(
                    '%s%d with names longer than 64 bytes (use setReporter() to see them)',
                    $list === '' ? '' : '; ',
                    $tooLong
                );
            }

            // SCRIPT_NAME may contain a part of the request path on some servers.
            $script = isset($_SERVER['SCRIPT_NAME']) && is_string($_SERVER['SCRIPT_NAME'])
                && preg_match('/\A[\w\/.\-]*\z/', $_SERVER['SCRIPT_NAME'])
                ? " in {$_SERVER['SCRIPT_NAME']}"
                : '';

            trigger_error(
                sprintf(
                    'MercifulPolluter: %d variable%s injected%s (may depend on register_globals): %s',
                    $count,
                    $count === 1 ? '' : 's',
                    $script,
                    $list
                ),
                E_USER_DEPRECATED
            );
        };
    }

    /**
     * Report injected variables with the specified callable.
     *
     * The callable is called once per `pollute()`, after all variables are injected.
     * It receives names as they are (not validated), and its exceptions are not caught.
     *
     *     $polluter->setReporter(function (array $injectedVariables) use ($logger) {
     *         foreach ($injectedVariables as $variable) {
     *             // $variable['name'] => 'id', $variable['origin'] => "$_GET['id']"
     *             $logger->info("\${$variable['name']} was injected from {$variable['origin']}");
     *         }
     *     });
     *
     * @param callable(array<int, array{name: string, origin: string|null}>): void $theReporter
     * @return void
     */
    public function setReporter(callable $theReporter)
    {
        $this->reporter = $theReporter;
    }

    /**
     * @return void
     */
    public function disableReport()
    {
        $this->reporter = null;
    }

    /**
     * Record an injected variable to report later in `flushReport()`.
     *
     * @param string|int $theName
     * @param string|null $theOrigin e.g. "$_GET['id']", or null if unknown
     * @return void
     */
    protected function collectInjectedVariable($theName, $theOrigin)
    {
        if ($this->reporter !== null) {
            $this->injectedVariables[] = array('name' => (string)$theName, 'origin' => $theOrigin);
        }
    }

    /**
     * Pass the collected variables to the reporter at once.
     *
     * Call this after all variables are injected, so that an error handler
     * converting E_USER_DEPRECATED to an exception cannot stop the injection halfway.
     *
     * @return void
     */
    protected function flushReport()
    {
        $injectedVariables = $this->injectedVariables;
        $this->injectedVariables = array();

        if ($this->reporter !== null && count($injectedVariables) > 0) {
            call_user_func($this->reporter, $injectedVariables);
        }
    }

    /**
     * @param string $theSuperGlobal e.g. '$_GET'
     * @param array<string|int> $theKeys e.g. array('id')
     * @return string e.g. "$_GET['id']"
     */
    protected function formatOrigin($theSuperGlobal, array $theKeys)
    {
        foreach ($theKeys as $key) {
            $theSuperGlobal .= "['" . addcslashes((string)$key, "\\'") . "']";
        }
        return $theSuperGlobal;
    }

    /**
     * @param string|int $theKey
     * @return bool
     */
    protected function ignoringVariable($theKey)
    {
        return in_array($theKey, self::$ignoringVariableNames);
    }
}
