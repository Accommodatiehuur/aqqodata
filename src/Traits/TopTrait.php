<?php

namespace Aqqo\OData\Traits;

use Illuminate\Support\Facades\Config;

trait TopTrait
{
    /**
     * Apply the $top query parameter constraint to the query.
     *
     * @return void
     */
    public function addTop(): void
    {
        $min_top = Config::integer('odata.top.min', 1);
        $default_top = Config::integer('odata.top.default', 100);
        $max_top = Config::integer('odata.top.max', 1000);

        $top = $this->request?->integer('$top', $default_top) ?? $default_top;
        if (!is_integer($top) || $top === 0) {
            $top = $default_top;
        }

        $top = max($min_top, min($top, $max_top));
        $this->subject->limit($top);
    }
}
