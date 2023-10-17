<?php

namespace NormanHuth\ConsoleApp;

use Illuminate\Container\Container as IlluminateContainer;

class Container extends IlluminateContainer
{
    /**
     * Determine if the application is running unit tests.
     *
     * @return bool
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function runningUnitTests()
    {
        return false;
    }
}
