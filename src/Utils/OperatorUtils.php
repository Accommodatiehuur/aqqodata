<?php

namespace Aqqo\OData\Utils;

class OperatorUtils
{
    /**
     * Map OData operators to Laravel operators.
     *
     * @param string $odataOperator
     * @return string
     */
    public static function mapOperator(string $odataOperator): string
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

    /**
     * @param string $odataOperator
     * @return string
     */
    public static function inverseOperator(string $odataOperator): string
    {
        $inverseMap = [
            'eq' => '!=',          // Inverse of equality
            'ne' => '=',           // Inverse of not equal
            'ge' => '<',           // Inverse of greater than or equal
            'gt' => '<=',          // Inverse of greater than
            'le' => '>',           // Inverse of less than or equal
            'lt' => '>=',          // Inverse of less than
            'and' => 'OR',         // Inverse of logical AND
            'or' => 'AND',         // Inverse of logical OR
            'not' => '',           // Inverse of NOT, which is essentially the identity
            'add' => '-',          // Inverse of addition
            'sub' => '+',          // Inverse of subtraction
            'mul' => '/',          // Inverse of multiplication
            'div' => '*',          // Inverse of division
            'mod' => '',           // Inverse of modulo is not well defined, so omitted
            'startswith' => 'NOT LIKE', // Inverse of startswith
            'endswith' => 'NOT LIKE',   // Inverse of endswith
            'substring' => '',     // Inverse of substring is not straightforward, omitted
            'length' => '',        // Inverse of length is not straightforward, omitted
            'indexof' => '',       // Inverse of indexof is not straightforward, omitted
            'tolower' => '',       // Inverse of tolower is not straightforward, omitted
            'toupper' => '',       // Inverse of toupper is not straightforward, omitted
            'trim' => '',          // Inverse of trim is not straightforward, omitted
            'concat' => '',        // Inverse of concat is not straightforward, omitted
            'year' => '',          // Inverse of year is not straightforward, omitted
            'month' => '',         // Inverse of month is not straightforward, omitted
            'day' => '',           // Inverse of day is not straightforward, omitted
            'hour' => '',          // Inverse of hour is not straightforward, omitted
            'minute' => '',        // Inverse of minute is not straightforward, omitted
            'second' => '',        // Inverse of second is not straightforward, omitted
            'now' => '',           // Inverse of now is not applicable, omitted
            'round' => '',         // Inverse of round is not straightforward, omitted
            'floor' => '',         // Inverse of floor is not straightforward, omitted
            'ceiling' => ''        // Inverse of ceiling is not straightforward, omitted
        ];
        return $inverseMap[$odataOperator] ?? '=';
    }
}