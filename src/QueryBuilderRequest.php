<?php

namespace Aqqo\OData;

use Illuminate\Http\Request;

class QueryBuilderRequest extends Request
{
    public static function fromRequest(Request $request): self
    {
        return static::createFrom($request, new static());
    }
}
