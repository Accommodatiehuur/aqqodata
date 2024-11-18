<?php

namespace Aqqo\OData\Tests\Feature;
it('Simple top', function (?int $top, string $result) {
    $query = createQueryFromParams(top: $top);
    expect($query->toSql())->toEqual($result);
})->with([
    "No top" => [null, 'select * from "test_models" limit 100 offset 0'],
    "Top 10" => [10, 'select * from "test_models" limit 10 offset 0'],
    "Top 1000" => [1000, 'select * from "test_models" limit 1000 offset 0'],
    "Top 10000" => [10000, 'select * from "test_models" limit 1000 offset 0'],
]);
