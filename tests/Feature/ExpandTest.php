<?php

namespace Aqqo\OData\Tests\Feature;

it('Run filter', function (?string $expand, string $result) {
    $query = createQueryFromParams(expand: $expand);
    expect($query->toSql())->toEqual($result);
})->with([
    "Simple expand" => ["KnowledgebaseArticles", "select `name` as `name`, `description` as `description`, `test` as `test`, `dbcol` as `odatacol`, `start_datetime_utc` as `start_datetime_utc`, `end_datetime_utc` as `end_datetime_utc` from `test_models` limit 100 offset 0"],
    "Simple expands" => ["KnowledgebaseArticles,Relation2", "select `name` as `name`, `description` as `description`, `test` as `test`, `dbcol` as `odatacol`, `start_datetime_utc` as `start_datetime_utc`, `end_datetime_utc` as `end_datetime_utc` from `test_models` limit 100 offset 0"],
    "Simple expand 2" => ["KnowledgebaseArticles(\$select=knowledgebase_article_title_nl_NL,knowledgebase_article_uri,knowledgebase_article_body_nl_NL;),Relation2", "select `name` as `name`, `description` as `description`, `test` as `test`, `dbcol` as `odatacol`, `start_datetime_utc` as `start_datetime_utc`, `end_datetime_utc` as `end_datetime_utc` from `test_models` limit 100 offset 0"],
    "Simple expand 23" => ["KnowledgebaseArticles(\$select=knowledgebase_article_title_nl_NL,knowledgebase_article_uri,knowledgebase_article_body_nl_NL;)", "select `name` as `name`, `description` as `description`, `test` as `test`, `dbcol` as `odatacol`, `start_datetime_utc` as `start_datetime_utc`, `end_datetime_utc` as `end_datetime_utc` from `test_models` limit 100 offset 0"],
]);