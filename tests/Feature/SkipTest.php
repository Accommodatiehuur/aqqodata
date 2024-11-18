<?php

namespace Aqqo\OData\Tests\Feature;

it('Simple skip', function (?int $skip, string $result) {
    $query = createQueryFromParams(skip: $skip);
    expect($query->toSql())->toEqual($result);
})->with([
    "No skip" => [null, 'select * from "test_models" limit 100 offset 0'],
    "Skip 10" => [10, 'select * from "test_models" limit 100 offset 10'],
    "Skip 50" => [50, 'select * from "test_models" limit 100 offset 50'],
]);
