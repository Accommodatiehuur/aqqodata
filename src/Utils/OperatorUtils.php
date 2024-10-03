<?php

namespace Aqqo\OData\Utils;

class OperatorUtils
{
    /**
     * Map OData operators to Laravel operators.
     *
     * @param  string  $odataOperator
     * @return string
     */
    public static function mapOperator($odataOperator)
    {
        $map = [
            'eq' => '=',
            'ne' => '!=',
            'ge' => '>=',
            'gt' => '>',
            'le' => '<=',
            'lt' => '<',
            'and' => 'AND',
            'or' => 'OR',
            'not' => 'NOT',
            'add' => '+',
            'sub' => '-',
            'mul' => '*',
            'div' => '/',
            'mod' => '%',
            'startswith' => 'LIKE',
            'endswith' => 'LIKE',
            'substring' => 'SUBSTRING',
            'length' => 'LENGTH',
            'indexof' => 'LOCATE',
            'tolower' => 'LOWER',
            'toupper' => 'UPPER',
            'trim' => 'TRIM',
            'concat' => 'CONCAT',
            'year' => 'YEAR',
            'month' => 'MONTH',
            'day' => 'DAY',
            'hour' => 'HOUR',
            'minute' => 'MINUTE',
            'second' => 'SECOND',
            'now' => 'NOW()',
            'round' => 'ROUND',
            'floor' => 'FLOOR',
            'ceiling' => 'CEILING'
        ];

        return $map[$odataOperator] ?? '=';
    }
}