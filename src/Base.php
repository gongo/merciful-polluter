<?php
namespace Gongo\MercifulPolluter;

abstract class Base
{
    abstract public function pollute();

    protected function injectToGlobal(array $theVariables)
    {
        foreach ($theVariables as $name => $value) {
            $GLOBALS[$name] = $value;
        }
    }
}
