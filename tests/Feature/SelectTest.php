<?php

namespace Aqqo\OData\Tests\Feature;

it('Run filter', function (?string $select, string $result) {
    $query = createQueryFromParams(select: $select);
    expect($query->toSql())->toEqual($result);
})->with([
    "Simple select" => ["name", 'select * from "test_models" limit 100 offset 0'],
    "Empty select" => ["", 'select * from "test_models" limit 100 offset 0'],
    "Non existing select" => ["name2", 'select * from "test_models" limit 100 offset 0'],
    "Multiple selects" => ["name, start_datetime_utc", 'select * from "test_models" limit 100 offset 0'],
    "Multiple selects without space" => ["name,start_datetime_utc", 'select * from "test_models" limit 100 offset 0'],
]);
