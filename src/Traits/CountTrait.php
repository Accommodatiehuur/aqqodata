<?php

namespace Aqqo\OData\Traits;

trait CountTrait
{
    protected bool $add_count = false;

    /**
     * Apply the $top query parameter constraint to the query.
     *
     * @return void
     */
    public function addCount(): void
    {
        $this->add_count = $this->request?->boolean('$count', false) ?? false;
    }
}

