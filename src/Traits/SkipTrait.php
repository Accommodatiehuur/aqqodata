<?php
namespace Aqqo\OData\Traits;

trait Skip
{
    /**
     * @return $this
     */
    public function addSkip(): static
    {
        $skip_query = $this->request->input('$skip', 0);

        // Set skip to 0 when; skip isset, but is lower than 0 or doesn't contain a numeric value
        if (isset($skip_query)
            && (
                $skip_query < 0 || !is_numeric($skip_query)
            )
        ) {
            $skip_query = 0;
        }

        $this->applySkipToQuery($skip_query);

        return $this;
    }

    /**
     * @param int $skip skip is validated in the previous function and should always be an int
     * @return void
     */
    private function applySkipToQuery(int $skip): void
    {
        $this->subject->skip($skip);
    }
}
