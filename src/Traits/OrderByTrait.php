<?php

namespace Aqqo\OData\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of Model
 */
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
    * @param Builder<TModelClass> $builder
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

