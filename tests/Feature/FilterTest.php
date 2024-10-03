<?php

namespace Aqqo\OData\Tests\Feature;

it('Run filter', function (?string $filter, string $result) {
    $query = createQueryFromParams(filter: $filter);
    expect($query->toSql())->toEqual($result);
})->with([
    "Without filters" => ["", "select * from `test_models` limit 100 offset 0"],
    "Simple name filter" => ["name eq 'Test' and test gt 12", "select * from `test_models` where `name` = 'Test' and `test` > '12' limit 100 offset 0"],
    "Two filters" => ["name eq 'Test' or name eq 'Aqqo'", "select * from `test_models` where `name` = 'Test' or `name` = 'Aqqo' limit 100 offset 0"],
    "Grouped filter" => ["(start_datetime_utc gt '2024-05-13T06:00:00+00:00' or start_datetime_utc lt '2024-05-13T06:00:00+00:00') and end_datetime_utc lt '2024-05-19T15:00:00+00:00'", "select * from `test_models` where ((`start_datetime_utc` > '2024-05-13T06:00:00+00:00' or `start_datetime_utc` < '2024-05-13T06:00:00+00:00') and (`end_datetime_utc` < '2024-05-19T15:00:00+00:00')) limit 100 offset 0"]
]);