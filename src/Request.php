<?php
namespace Gongo\MercifulPolluter;

class Request extends Base
{
    /** @var bool */
    private $magicQuotesGpc = false;

    /**
     * Origins of the variables being injected in `injectToGlobal()`, e.g. array('id' => "$_GET['id']").
     *
     * It is not an argument of `injectToGlobal()` to keep compatibility with subclasses overriding it.
     *
     * @var array<string|int, string>
     */
    private $currentOrigins = array();

    /**
     * @return void
     */
    public function pollute()
    {
        if ($this->magicQuotesGpc) {
            $this->applyMagicQuotesGpc();
        }
        $this->injectFileToGlobal();
        $this->injectEGPCSToGlobal();
        $this->flushReport();
    }

    /**
     * @return void
     */
    public function enableMagicQuotesGpc()
    {
        $this->magicQuotesGpc = true;
    }

    /**
     * @return void
     */
    public function disableMagicQuotesGpc()
    {
        $this->magicQuotesGpc = false;
    }

    /**
     * Inject $_FILES to global variables.
     *
     * The naming rule when injected
     *
     *     $_FILES['upfile']['tmp_name'] => $upfile
     *     $_FILES['upfile']['size']     => $upfile_size
     *     $_FILES['upfile']['type']     => $upfile_type
     *
     * @return void
     */
    private function injectFileToGlobal()
    {
        foreach ($_FILES as $field => $info) {
            $values = array();
            $origins = array();

            /** @var array<string, mixed> $info */
            foreach ($info as $key => $value) {
                if ($key === 'tmp_name') {
                    $name = $field;
                } else {
                    $name = "{$field}_{$key}";
                }
                $values[$name] = $value;
                $origins[$name] = $this->formatOrigin('$_FILES', array($field, $key));
            }

            $this->injectToGlobalWithOrigins($values, $origins);
        }
    }

    /**
     * Inject `EGPCS` to global variables.
     *
     * `EGPCS` means $_ENV, $_GET, $_POST, $_COOKIE and $_SERVER.
     *
     * @return void
     */
    private function injectEGPCSToGlobal()
    {
        $injectedFlag = array(
            'e' => false,
            'g' => false,
            'p' => false,
            'c' => false,
            's' => false
        );

        foreach ($this->getInjectVariables() as $name) {
            if (!isset($injectedFlag[$name]) || $injectedFlag[$name]) {
                continue;
            }

            switch ($name) {
                case 'e':
                    $this->injectSuperGlobalToGlobal($_ENV, '$_ENV');
                    break;
                case 'g':
                    $this->injectSuperGlobalToGlobal($_GET, '$_GET');
                    break;
                case 'p':
                    $this->injectSuperGlobalToGlobal($_POST, '$_POST');
                    break;
                case 'c':
                    $this->injectSuperGlobalToGlobal($_COOKIE, '$_COOKIE');
                    break;
                case 's':
                    $this->injectSuperGlobalToGlobal($_SERVER, '$_SERVER');
                    break;
            }

            $injectedFlag[$name] = true;
        }
    }

    /**
     * @return string[]
     */
    protected function getInjectVariables()
    {
        return str_split(
            strtolower(ini_get('variables_order')) // @phpstan-ignore argument.type
        );
    }

    /**
     * Recursively applies `addslashes` to each element of the array recursive.
     *
     * This method is **bang** .
     *
     * @param mixed[] $theVariables
     * @return void
     */
    private function addSlashesRecursive(&$theVariables)
    {
        array_walk_recursive(
            $theVariables,
            function (&$value) {
                $value = addslashes($value);
            }
        );
    }

    /**
     * @param mixed[] $theVariables
     * @param string $theSuperGlobal e.g. '$_GET'
     * @return void
     */
    private function injectSuperGlobalToGlobal(array $theVariables, $theSuperGlobal)
    {
        $origins = array();
        foreach (array_keys($theVariables) as $key) {
            $origins[$key] = $this->formatOrigin($theSuperGlobal, array($key));
        }

        $this->injectToGlobalWithOrigins($theVariables, $origins);
    }

    /**
     * @param mixed[] $theVariables
     * @param array<string|int, string> $theOrigins
     * @return void
     */
    private function injectToGlobalWithOrigins(array $theVariables, array $theOrigins)
    {
        $this->currentOrigins = $theOrigins;
        $this->injectToGlobal($theVariables);
        $this->currentOrigins = array();
    }

    /**
     * Subclasses overriding this without calling the parent are not reported.
     *
     * @param mixed[] $theVariables
     * @return void
     */
    protected function injectToGlobal(array $theVariables)
    {
        foreach ($theVariables as $name => $value) {
            if ($this->ignoringVariable($name)) {
                continue;
            }

            if (isset($GLOBALS[$name]) && is_array($GLOBALS[$name]) && is_array($value)) {
                $GLOBALS[$name] = array_replace_recursive($GLOBALS[$name], $value);
            } else {
                $GLOBALS[$name] = $value;
            }

            $this->collectInjectedVariable(
                $name,
                isset($this->currentOrigins[$name]) ? $this->currentOrigins[$name] : null
            );
        }
    }

    /**
     * @return void
     */
    private function applyMagicQuotesGpc()
    {
        $this->addSlashesRecursive($_GET);
        $this->addSlashesRecursive($_POST);
        $this->addSlashesRecursive($_COOKIE);
        $this->addSlashesRecursive($_REQUEST);
    }
}
