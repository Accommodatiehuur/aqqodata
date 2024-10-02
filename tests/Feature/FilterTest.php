<?php

namespace Aqqo\OData\Tests\Feature;

use Aqqo\OData\Tests\Testclasses\TestModel;

beforeEach(function () {
    $this->models = TestModel::factory()->count(5)->create();
});

it('can have a simple filter', function () {
    $query = createQueryFromFilterRequest("name eq 'Test' and test gt 12 and any(relatedModels,Name eq 'John Belushi')");

    dump($query->toSql());
//    expect($query->toSql())->toHaveCount(1);
});