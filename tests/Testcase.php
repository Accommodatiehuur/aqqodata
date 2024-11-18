<?php

namespace Aqqo\OData\Tests;

use Aqqo\OData\ServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class Testcase extends \Orchestra\Testbench\TestCase
{
    use DatabaseMigrations;

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
    }

    protected function setUpDatabase(?Application $app): void
    {
        if (is_null($app)) {
            return;
        }

        $app['db']->connection()->getSchemaBuilder()->create('test_models', function ($table) {
            $table->increments('id');
        });
    }
}
