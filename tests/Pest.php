<?php

namespace Aqqo\OData\Tests\Feature;

use Aqqo\OData\Query;
use Aqqo\OData\Tests\Testcase;
use Aqqo\OData\Tests\Testclasses\TestModel;
use Illuminate\Http\Request;


uses(Testcase::class)->in(__DIR__);

function createQueryFromParams(string $select = "", string $filter = "", string $expand = "", ?int $skip = null, ?int $top = null, string $model = null): Query
{
    $model ??= TestModel::class;

    $request = new Request([
        '$select' => $select,
        '$filter' => $filter,
        '$expand' => $expand,
        '$skip' => $skip,
        '$top' => $top,
    ]);

    return Query::for($model, $request);
}