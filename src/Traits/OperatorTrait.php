<?php

namespace Aqqo\OData\Traits;

trait OperatorTrait
{
    /**
     * Map OData operators to Laravel operators.
     *
     * @param  string  $odataOperator
     * @return string
     */
    protected function mapOperator($odataOperator)
    {
        $map = [
            'eq' => '=',
            'ne' => '!=',
            'ge' => '>=',
            'gt' => '>',
            'le' => '<=',
            'lt' => '<',
            'startswith' => 'like',
            'endswith' => 'like',
            'substringof' => 'like',
        ];

        return $map[$odataOperator] ?? '=';
    }
}

