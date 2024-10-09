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
     * @param Builder|Relation $builder
     * @param string $statement
     * @return void
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
                        if ($column && $operator && $value && $this->isPropertyFilterable($column, (new \ReflectionClass($builder))->getShortName())) {
                            $builder->{$istatement}($column, $operator, $value);
                        }
                    }
                }
            }
        }
    }


    /**
     * @param Builder<Model> $builder
     * @param string $column
     * @param string $operator
     * @param string $value
     * @return void
     */
    protected function applyRelationshipCondition(Builder $builder, string $column, string $operator, string $value): void
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
        if (str_starts_with($input, 'contains(') || str_starts_with($input, 'startswith(') || str_starts_with($input, 'endswith(')) {
            $pattern = '/\(([^)]+)\)/';
            preg_match($pattern, $input, $matches);

            if ($matches[1]) {
                $explode = explode('(', $input);
                $operator = $inverse_operator ? OperatorUtils::mapOperator($explode[0], true) : OperatorUtils::mapOperator($explode[0]);
                [$column, $value] = explode(',', $matches[1]);
            }
        } else {
            $split = preg_split('/(?<=\s)(eq|ne|ge|gt|le|lt|)(?=\s)/', $input, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $column = $split[0];
            $value = $split[2];
            $operator = $inverse_operator ? OperatorUtils::mapOperator($split[1] ?? '', true) : OperatorUtils::mapOperator($split[1] ?? '');
        }
        $value = trim($value ?? '', " '");


        return [
            trim($column ?? ''),
            $operator,
            $operator == 'LIKE' || $operator == 'NOT LIKE' ? "%{$value}%" : $value
        ];
    }
}
