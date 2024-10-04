<?php

namespace Aqqo\OData\Traits;

trait SkipTrait
{
    /**
     * @return void
     */
    public function addSkip(): void
    {
        $skip = $this->request?->input('$skip', 0) ?? 0;

        // Set skip to 0 when; skip isset, but is lower than 0 or doesn't contain a numeric value
        if (!is_integer($skip) || $skip < 0) {
            $skip = 0;
        }
        $this->subject->skip($skip);
    }
}
