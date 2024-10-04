<?php

namespace Aqqo\OData\Tests\Feature;

it('Run filter', function (?string $filter, string $result) {
    $query = createQueryFromParams(filter: $filter);
    expect($query->toSql())->toEqual($result);
})->with([
    "Without filters" => ["", "select * from `test_models` limit 100 offset 0"],
    "Simple name filter" => ["name eq 'Test' and test gt 12", "select * from `test_models` where `name` = 'Test' and `test` > '12' limit 100 offset 0"],
    "Two filters" => ["name eq 'Test' or name eq 'Aqqo'", "select * from `test_models` where `name` = 'Test' or `name` = 'Aqqo' limit 100 offset 0"],
    "Grouped filter" => ["(start_datetime_utc gt '2024-05-13T06:00:00+00:00' or start_datetime_utc lt '2024-05-13T06:00:00+00:00') and end_datetime_utc lt '2024-05-19T15:00:00+00:00'", "select * from `test_models` where ((`start_datetime_utc` > '2024-05-13T06:00:00+00:00' or `start_datetime_utc` < '2024-05-13T06:00:00+00:00') and (`end_datetime_utc` < '2024-05-19T15:00:00+00:00')) limit 100 offset 0"],
    "Simple any filter" => ["any(relatedModels, name eq 'Aqqo')", "select * from `test_models` where exists (select * from `related_models` where `test_models`.`id` = `related_models`.`test_model_id` and `name` = 'Aqqo') limit 100 offset 0"],
    "Simple any filter but not expandable" => ["any(relatedModel, name eq 'Aqqo')", "select * from `test_models` where exists (select * from `related_models` where `test_models`.`id` = `related_models`.`test_model_id` and `name` = 'Aqqo') limit 100 offset 0"],
    "Two filters with any filter" => ["name eq 'Aqqo' and any(relatedModels, name eq 'Aqqo')", "select * from `test_models` where ((`name` = 'Aqqo') and (exists (select * from `related_models` where `test_models`.`id` = `related_models`.`test_model_id` and `name` = 'Aqqo'))) limit 100 offset 0"],
    "Two filters with any filter but inversed" => ["any(relatedModels, name eq 'Aqqo') and name eq 'Aqqo'", "select * from `test_models` where ((exists (select * from `related_models` where `test_models`.`id` = `related_models`.`test_model_id` and `name` = 'Aqqo')) and (`name` = 'Aqqo')) limit 100 offset 0"],
    "Simple all filter" => ["all(relatedModels, cost gt 10)", "select * from `test_models` where not exists (select * from `related_models` where `test_models`.`id` = `related_models`.`test_model_id` and `cost` <= '10') limit 100 offset 0"],
    "Two filters with all filter" => ["name eq 'Aqqo' and all(relatedModels, cost gt 10)", "select * from `test_models` where ((`name` = 'Aqqo') and (not exists (select * from `related_models` where `test_models`.`id` = `related_models`.`test_model_id` and `cost` <= '10'))) limit 100 offset 0"],
    "Two filters with all filter but inversed" => ["all(relatedModels, cost gt 10) and name eq 'Aqqo'", "select * from `test_models` where ((not exists (select * from `related_models` where `test_models`.`id` = `related_models`.`test_model_id` and `cost` <= '10')) and (`name` = 'Aqqo')) limit 100 offset 0"],
]);