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
        $min_top = Config::get('odata.top.min', '1');
        $default_top = Config::get('odata.top.default', '100');
        $max_top = Config::get('odata.top.max', '1000');

        $top_query = $this->request->input('$top', $default_top);

        // First validate if we have a numeric value, otherwise set default
        if (is_numeric($top_query)) {
            // If top is lower than min, set the min
            if ($top_query < $min_top) {
                $top_query = $min_top;
            }

            // If top is higher than max, set the max
            if ($top_query > $max_top) {
                $top_query = $max_top;
            }
        } else {
            $top_query = $default_top;
        }

        $this->applyTopToQuery($top_query);

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
        $this->subject->take($top);
    }
}
