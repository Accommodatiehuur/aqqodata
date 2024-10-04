<?php

namespace Aqqo\OData\Traits;

use Illuminate\Support\Facades\Config;

trait TopTrait
{
    /**
     * Apply the $top query parameter constraint to the query.
     *
     * @return $this
     */
    public function addTop(): static
    {
        $min_top = Config::integer('odata.top.min', 1);
        $default_top = Config::integer('odata.top.default', 100);
        $max_top = Config::integer('odata.top.max', 1000);

        $top = $this->request?->input('$top', $default_top) ?? $default_top;
        if (!is_integer($top)) {
            $top = $default_top;
        }

        // If top is lower than min, set the min
        if ($top < $min_top) {
            $top = $min_top;
        }

        // If top is higher than max, set the max
        if ($top > $max_top) {
            $top = $max_top;
        }

        $this->applyTopToQuery($top);
        return $this;
    }

    /**
     * Apply the top constraint to the query.
     *
     * @param int $top The number of records to retrieve from the query.
     *                 This value should always be an integer.
     *
     * @return void
     */
    private function applyTopToQuery(int $top): void
    {
        $this->subject->limit($top);
    }
}
