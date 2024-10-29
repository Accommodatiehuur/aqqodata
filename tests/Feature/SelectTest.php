<?php

namespace Aqqo\OData\Tests\Feature;

it('Run filter', function (?string $select, string $result) {
    $query = createQueryFromParams(select: $select);
    expect($query->toSql())->toEqual($result);
})->with([
    "Simple select" => ["name", "select `name` from `test_models` limit 100 offset 0"],
    "Empty select" => ["", "select `name` as `name`, `description` as `description`, `test` as `test`, `dbcol` as `odatacol`, `start_datetime_utc` as `start_datetime_utc`, `end_datetime_utc` as `end_datetime_utc` from `test_models` limit 100 offset 0"],
    "Non existing select" => ["name2", "select `name` as `name`, `description` as `description`, `test` as `test`, `dbcol` as `odatacol`, `start_datetime_utc` as `start_datetime_utc`, `end_datetime_utc` as `end_datetime_utc` from `test_models` limit 100 offset 0"],
    "Multiple selects" => ["name, start_datetime_utc", "select `name`, `start_datetime_utc` from `test_models` limit 100 offset 0"],
    "Multiple selects without space" => ["name,start_datetime_utc", "select `name`, `start_datetime_utc` from `test_models` limit 100 offset 0"],
]);