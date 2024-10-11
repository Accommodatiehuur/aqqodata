<?php

namespace Aqqo\OData\Traits;

use Aqqo\OData\Utils\OperatorUtils;
use Aqqo\OData\Utils\StringUtils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

trait FilterTrait
{
    /**
     * @return void
     */
    public function addFilters(): void
    {
        $filter = $this->request?->input('$filter');
        if (!empty($filter)) {
            $this->appendFilterQuery(strval($filter), $this->subject);
        }
    }

    /**
     * @param string $filter
     * @param Builder<Model>|Relation<Model> $builder
     * @param string $statement
     * @return void
     * @throws \ReflectionException
     */
    public function appendFilterQuery(string $filter, Builder|Relation $builder, string $statement = 'where'): void
    {
        $expressions = StringUtils::splitODataExpression($filter);

        if ((str_contains($filter, '(') || str_contains($filter, ')')) && count($expressions) > 1) {
            $builder->where(function (Builder $q) use ($statement, $expressions) {
                foreach ($expressions as $value) {
                    if (in_array($value = trim($value), ['and', 'or'])) {
                        if ($value === 'or') {
                            $statement = 'orWhere';
                        }
                        continue;
                    }

                    $q->{$statement}(function (Builder $q) use ($value, $statement) {
                        if (str_starts_with($value, '(') && str_ends_with($value, ')')) {
                            $value = substr($value, 1, -1);
                        }

                        $this->appendFilterQuery($value, $q, $statement);
                    });

                }
            });
        } else {
            if (str_starts_with($filter, '(') && str_ends_with($filter, ')')) {
                $filter = substr($filter, 1, -1);
            }
            $grouped_filters = preg_split('/(?<=\s)(and|or)(?=\s)/', $filter, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $istatement = 'where';
            if ($grouped_filters) {
                foreach ($grouped_filters as $gf) {
                    if (in_array($gf = trim($gf), ['and', 'or'])) {
                        if ($gf === 'or') {
                            $istatement = 'orWhere';
                        }
                        continue;
                    }

                    if (str_starts_with($gf, 'all(') || str_starts_with($gf, 'any(')) {
                        preg_match('/(any|all)\((.+)\)/', $gf, $matches);
                        if (isset($matches[1]) && isset($matches[2])) {
                            $this->applyRelationshipCondition($builder, $matches[1], '', $matches[2]);
                        }
                    } else {
                        [$column, $operator, $value] = $this->splitInput($gf);

                        if ($column && $operator && $value && $this->isPropertyFilterable($column, (new \ReflectionClass($builder->getModel()))->getShortName())) {
                            $builder->{$istatement}($column, $operator, $value);
                        }
                    }
                }
            }
        }
    }


    /**
     * @param Builder<Model>|Relation<Model> $builder
     * @param string $column
     * @param string $operator
     * @param string $value
     * @return void
     */
    protected function applyRelationshipCondition(Builder|Relation $builder, string $column, string $operator, string $value): void
    {
        if ($column === 'any' || $column === 'all') {
            $value = str_replace(['(', ')'],'', $value);
            [$relation, $value] = explode(',', $value);

            if ($expandable = $this->isPropertyExpandable($relation)) {
                if ($column === 'all') {
                    $builder->whereDoesntHave($expandable, function (Builder $q) use ($value, $expandable) {
                        [$column, $operator, $val] = $this->splitInput($value, inverse_operator: true);
                        if ($column && $operator && $val && $this->isPropertyFilterable("{$expandable}.{$column}")) {
                            return $q->where($column, $operator, $val);
                        }
                    });
                } else {
                    $builder->whereHas($expandable, function (Builder $q) use ($value, $expandable) {
                        [$column, $operator, $val] = $this->splitInput($value);
                        if ($column && $operator && $val && $this->isPropertyFilterable("{$expandable}.{$column}")) {
                            return $q->where($column, $operator, $val);
                        }
                    });
                }
            }
        } else {
            $segments = explode('/', $column);
            $relation = $segments[0];
            $relatedField = $segments[1];
            // Regular relationship condition
            $builder->whereHas($relation, function ($q) use ($relatedField, $operator, $value) {
                $q->where(trim($relatedField), OperatorUtils::mapOperator($operator), $value);
            });
        }
    }

    /**
     * @param string $input
     * @param bool $inverse_operator
     * @return array<int, string>
     */
    private function splitInput(string $input, bool $inverse_operator = false): array
    {
        // Define the regex pattern to match tokens:
        // 1. Functions and operators
        // 2. Parentheses and commas
        // 3. String literals enclosed in single quotes
        // 4. Numeric values
        // 5. Field names or identifiers
        $pattern = '/\b(contains|startswith|endswith|and|or|not|eq|ne|gt|ge|lt|le)\b|([(),])|\'([^\']*)\'|(\d+(\.\d+)?)|([A-Za-z_][A-Za-z0-9_]*)/i';

        // Perform global matching
        preg_match_all($pattern, $input, $matches, PREG_SET_ORDER);

        $tokens = [];
        foreach ($matches as $match) {
            if (!empty($match[1])) {
                // Functions and operators (e.g., contains, eq, and)
                $tokens[] = strtolower($match[1]);
            } elseif (!empty($match[2])) {
                // Parentheses and commas (e.g., (, ), ,)
                // Optionally, include them as tokens or skip
                // For splitting purposes, we'll skip them
                continue;
            } elseif (isset($match[3]) && $match[3] !== '') {
                // String literals without quotes
                $tokens[] = $match[3];
            } elseif (isset($match[4]) && $match[4] !== '') {
                // Numeric values
                $tokens[] = $match[4];
            } elseif (isset($match[6]) && $match[6] !== '') {
                // Field names or identifiers
                $tokens[] = $match[6];
            }
        }

        if (in_array($tokens[0], ['contains', 'startswith', 'endswith'])) {
            $tokens = [
                $tokens[1],
                $tokens[0],
                $tokens[2]
            ];
        }

        $column = $tokens[0];
        $operator = OperatorUtils::mapOperator($tokens[1], $inverse_operator);
        $value = OperatorUtils::getValueBasedOnOperator($tokens[1], $tokens[2]);


        return [
            $column,
            $operator,
            $value
        ];
    }
}
