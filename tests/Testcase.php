<?php

namespace Aqqo\OData\Tests;

use Aqqo\OData\ServiceProvider;

class Testcase extends \Orchestra\Testbench\TestCase
{

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }
}