<?php

namespace Aqqo\OData\Utils;

/**
 * @class OperatorUtils
 * The class below contains all static functions for operators. Especially mapping the OData operators to Eloquent/Mysql operators.
 */
class OperatorUtils
{
    /**
     * @var array<string, array<int, string>>
     */
    private static array $operatorMap = [
        'eq' => ['=', '!='],
        'ne' => ['!=', '='],
        'ge' => ['>=', '<'],
        'gt' => ['>', '<='],
        'le' => ['<=', '>'],
        'lt' => ['<', '>='],
        'and' => ['AND', 'OR'],
        'or' => ['OR', 'AND'],
        'not' => ['NOT', ''],  // No straightforward inverse for NOT
        'add' => ['+', '-'],
        'sub' => ['-', '+'],
        'mul' => ['*', '/'],
        'div' => ['/', '*'],
        'mod' => ['%', ''],  // No inverse for modulo
        'contains' => ['LIKE', 'NOT LIKE', '%{$value}%'],
        'startswith' => ['LIKE', 'NOT LIKE', '{$value}%'],
        'endswith' => ['LIKE', 'NOT LIKE', '%{$value}'],
        'substring' => ['SUBSTRING', ''],
        'length' => ['LENGTH', ''],
        'indexof' => ['LOCATE', ''],
        'tolower' => ['LOWER', ''],
        'toupper' => ['UPPER', ''],
        'trim' => ['TRIM', ''],
        'concat' => ['CONCAT', ''],
        'year' => ['YEAR', ''],
        'month' => ['MONTH', ''],
        'day' => ['DAY', ''],
        'hour' => ['HOUR', ''],
        'minute' => ['MINUTE', ''],
        'second' => ['SECOND', ''],
        'now' => ['NOW()', ''],
        'round' => ['ROUND', ''],
        'floor' => ['FLOOR', ''],
        'ceiling' => ['CEILING', '']
    ];

    /**
     * Map OData operators to Laravel operators.
     *
     * @param string $odataOperator
     * @param bool $inverse Return inverse operator if true
     * @return string
     */
    public static function mapOperator(string $odataOperator, bool $inverse = false): string
    {
        $map = self::$operatorMap[$odataOperator] ?? ['=', '='];
        return $inverse ? $map[1] : $map[0];
    }

    /**
     * @param string $odataOperator
     * @param string $value
     * @return string
     */
    public static function getValueBasedOnOperator(string $odataOperator, string $value): string
    {
        if (isset(self::$operatorMap[$odataOperator][2])) {
            return str_replace('{$value}', $value, self::$operatorMap[$odataOperator][2]);
        }
        return $value;
    }
}