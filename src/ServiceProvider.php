<?php

namespace Aqqo\OData;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('odata')
            ->hasConfigFile('odata');
    }

    public function registeringPackage()
    {
        $this->app->bind(QueryBuilderRequest::class, function ($app) {
            return QueryBuilderRequest::fromRequest($app['request']);
        });
    }

    public function provides(): array
    {
        return [
            QueryBuilderRequest::class,
        ];
    }

}
