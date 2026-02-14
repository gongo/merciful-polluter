<?php
namespace Gongo\MercifulPolluter\Test;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a mock object with specified methods.
     * Compatible with PHPUnit 4.x through 11.x
     *
     * @param string $className
     * @param array $methods
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createMockWithMethods($className, array $methods)
    {
        $builder = $this->getMockBuilder($className);

        // PHPUnit 10+ removed setMethods(), use onlyMethods() instead
        // onlyMethods() was added in PHPUnit 8.0
        if (method_exists($builder, 'onlyMethods')) {
            return $builder->onlyMethods($methods)->getMock();
        }

        // PHPUnit 4.x - 7.x uses setMethods()
        return $builder->setMethods($methods)->getMock();
    }
}
