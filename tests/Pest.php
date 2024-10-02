<?php

namespace Aqqo\OData\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TychoKamphuis\Podata\PodataQuery;
use TychoKamphuis\Podata\Tests\Testcase;
use TychoKamphuis\Podata\Tests\Testclasses\TestModel;


uses(Testcase::class)->in(__DIR__);

function createQueryFromFilterRequest(string $filter_query, string $model = null): PodataQuery
{
    $model ??= TestModel::class;

    $request = new Request([
        '$filter' => $filter_query,
    ]);

    return PodataQuery::for($model, $request);
}

function assertQueryExecuted(string $query)
{
    $queries = array_map(function ($queryLogItem) {
        return $queryLogItem['query'];
    }, DB::getQueryLog());

    expect($queries)->toContain($query);
}