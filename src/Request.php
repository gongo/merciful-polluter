<?php
namespace Gongo\MercifulPolluter;

class Request extends Base
{
    private $magicQuotesGpc = false;
    
    public function pollute()
    {
        if ($this->magicQuotesGpc) {
            $this->applyMagicQuotesGpc();
        }
        $this->injectFileToGlobal();
        $this->injectEGPCSToGlobal();
    }

    public function enableMagicQuotesGpc()
    {
        $this->magicQuotesGpc = true;
    }

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
     */
    private function injectFileToGlobal()
    {
        foreach ($_FILES as $field => $info) {
            $values = array();

            foreach ($info as $key => $value) {
                if ($key === 'tmp_name') {
                    $name = $field;
                } else {
                    $name = "${field}_${key}";
                }
                $values[$name] = $value;
            }

            $this->injectToGlobal($values);
        }
    }

    /**
     * Inject `EGPCS` to global variables.
     *
     * `EGPCS` means $_ENV, $_GET, $_POST, $_COOKIE and $_SERVER.
     *
     * If beforehand call `enableMagicQuotesGpc()`,
     * applies `addslashes` $_GET, $_POST, $_COOKIE and those inject variables.
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
                    $this->injectToGlobal($_ENV);
                    break;
                case 'g':
                    $this->injectToGlobal($_GET);
                    break;
                case 'p':
                    $this->injectToGlobal($_POST);
                    break;
                case 'c':
                    $this->injectToGlobal($_COOKIE);
                    break;
                case 's':
                    $this->injectToGlobal($_SERVER);
                    break;
            }

            $injectedFlag[$name] = true;
        }
    }

    /**
     * @return  array
     */
    protected function getInjectVariables()
    {
        return str_split(strtolower(ini_get('variables_order')));
    }

    /**
     * Recursively applies `addslashes` to each element of the array recursive.
     *
     * This method is **bang** .
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
        }
    }

    private function applyMagicQuotesGpc()
    {
        $this->addSlashesRecursive($_GET);
        $this->addSlashesRecursive($_POST);
        $this->addSlashesRecursive($_COOKIE);
        $this->addSlashesRecursive($_REQUEST);
    }
}
