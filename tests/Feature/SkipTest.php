<?php

namespace Aqqo\OData\Tests\Feature;

it('Simple skip', function (?int $skip, string $result) {
    $query = createQueryFromParams(skip: $skip);
    expect($query->toSql())->toEqual($result);
})->with([
    "No skip" => [null, "select `name` as `name`, `description` as `description`, `test` as `test`, `dbcol` as `odatacol`, `start_datetime_utc` as `start_datetime_utc`, `end_datetime_utc` as `end_datetime_utc` from `test_models` limit 100 offset 0"],
    "Skip 10" => [10, "select `name` as `name`, `description` as `description`, `test` as `test`, `dbcol` as `odatacol`, `start_datetime_utc` as `start_datetime_utc`, `end_datetime_utc` as `end_datetime_utc` from `test_models` limit 100 offset 10"],
    "Skip 50" => [50, "select `name` as `name`, `description` as `description`, `test` as `test`, `dbcol` as `odatacol`, `start_datetime_utc` as `start_datetime_utc`, `end_datetime_utc` as `end_datetime_utc` from `test_models` limit 100 offset 50"],
]);