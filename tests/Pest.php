<?php

namespace Aqqo\OData\Tests\Feature;

use Aqqo\OData\Query;
use Aqqo\OData\Tests\Testcase;
use Aqqo\OData\Tests\Testclasses\TestModel;
use Illuminate\Http\Request;


uses(Testcase::class)->in(__DIR__);

function createQueryFromParams(string $select = "", string $filter = "", string $expand = "", string $search = "", ?int $skip = null, ?int $top = null, ?bool $count = null, string $orderby = "", string $model = null): Query
{
    $model ??= TestModel::class;

    $request = new Request([
        '$select' => $select,
        '$filter' => $filter,
        '$search' => $search,
        '$expand' => $expand,
        '$skip' => $skip,
        '$top' => $top,
        '$count' => $count,
        '$orderby' => $orderby,
    ]);

    return Query::for($model, $request);
}
