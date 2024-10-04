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

    /**
     * @return void
     */
    public function registeringPackage(): void
    {
        $this->app->bind(QueryBuilderRequest::class, function ($app) {
            return QueryBuilderRequest::fromRequest($app['request']);
        });
    }

    /**
     * @return array<class-string>
     */
    public function provides(): array
    {
        return [
            QueryBuilderRequest::class,
        ];
    }

}
