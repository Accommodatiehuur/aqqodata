<?php

namespace Aqqo\OData\Traits;

use Aqqo\OData\Utils\OperatorUtils;
use Illuminate\Database\Eloquent\Builder;

trait ExpandTrait
{
    /**
     * Function gets the $expand from the query and processes this within the subject.
     * @return void
     */
    public function addExpands()
    {
        $expand_query = $this->request->input('$expand');

        if (!empty($expand_query)) {
            // Parse expand into individual relationships (e.g., 'Customer,OrderItems($expand=Product)')
            foreach (explode(',', $expand_query) as $expand) {
                // Handle expand with filter: e.g., objects($filter=name eq 10)
                if (str_contains($expand, '(')) {
                    preg_match('/([A-Za-z]+)\((.*)\)/', $expand, $matches);
                    $this->handleExpandsDetails($expand, $matches[1]);
                } else {
                    $this->subject->with($expand);
                }
            }
        }
    }

    /**
     * Functions handles details on the $expand. All nested ($) are handled here.
     *
     * @param string $expand
     * @param string $relation
     * @return void
     */
    private function handleExpandsDetails(string $expand, string $relation)
    {
        preg_match('/([A-Za-z]+)\((.*)\)/', $expand, $matches);
        $details = explode(';', $matches[2]);

        foreach ($details as $detail) {
            [$key, $value] = explode('=', $detail);

            switch ($key) {
                case '$filter':
                    [$column, $operator, $value] = explode(' ', $value);
                    $this->subject->whereHas($relation, function (Builder $query) use ($column, $operator, $value) {
                        $query->where($column, OperatorUtils::mapOperator($operator), $value);
                    });
                    break;

                case '$expand':
                    if (str_contains($expand, '(')) {
                        $this->handleExpandsDetails($value, "{$relation}.{$value}");
                    } else {
                        $this->subject->with("{$relation}.{$value}");
                    }
                    break;
            }
        }
    }
}
