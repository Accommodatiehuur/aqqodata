<?php

namespace Aqqo\OData;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QueryBuilderRequest extends Request
{
    public static function fromRequest(Request $request): self
    {
        return static::createFrom($request, new static());
    }

    protected function getRequestData(?string $key = null, $default = null)
    {
        return $this->input($key, $default);
    }
}
