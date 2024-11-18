<?php

namespace Aqqo\OData\Traits;

use Illuminate\Database\Eloquent\Builder;

trait OrderByTrait
{
    /**
     * Apply the $orderby query parameter to the response.
     *
     * @return void
     */
    public function addOrderBy(): void
    {
        $orderby = $this->request?->input('$orderby');

        if ($orderby) {
            $this->appendOrderBy($orderby, $this->subject);
        }
    }

    /**
    * @param string $orderby
    * @param Builder $builder
    *
    * @return void
    */
    public function appendOrderBy(string $orderby, Builder $builder): void
    {
        foreach (explode(',', $orderby) as $order) {
            $order = explode(' ', trim($order));
            $builder->orderBy($order[0], $order[1] ?? 'asc');
        }
    }
}

