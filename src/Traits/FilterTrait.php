<?php

namespace Aqqo\OData\Traits;

use Aqqo\OData\Utils\StringUtils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

trait FilterTrait
{
    public function addFilters()
    {
        $filter_query = $this->request->input('$filter');
        if (!empty($filter_query)) {
            $this->appendQuery($filter_query, $this->subject);
        }
    }

    /**
     * @param string $filter
     * @param Builder $builder
     * @param string $statement
     * @return Builder
     */
    public function appendQuery(string $filter, Builder $builder, string $statement = 'where')
    {
        if (str_contains($filter, '(') || str_contains($filter, ')')) {
            $builder->where(function (Builder $q) use ($filter, $statement) {
                foreach (StringUtils::splitODataExpression($filter) as $value) {
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
                        return $this->appendQuery($value, $q, $statement);
                    });

                }
            });
        } else {
            if (str_starts_with($filter, '(') && str_ends_with($filter, ')')) {
                $filter = substr($filter, 1, -1);
            }
            $grouped_filters = preg_split('/(?<=\s)(and|or)(?=\s)/', $filter, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $istatement = 'where';
            foreach ($grouped_filters as $gf) {
                if (in_array($gf = trim($gf), ['and', 'or'])) {
                    if ($gf === 'or') {
                        $istatement = 'orWhere';
                    }
                    continue;
                }
                [$column, $operator, $value] = preg_split('/(?<=\s)(eq|ne|ge|gt|le|lt|)(?=\s)/', $gf, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                $value = trim($value, " '");

                if ($column === 'any'|| $column === 'all' || str_contains($column, '/')) {
                    $this->applyRelationshipCondition($builder, $column, $operator, $value);
                } else {
                    $builder->{$istatement}(trim($column), $this->mapOperator($operator), $value);
                }
            }
        }

        return $builder;
    }


    /**
     * Handle conditions on related models (e.g., Customer/Name or OrderItems/any).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @return void
     */
    protected function applyRelationshipCondition(Builder $builder, $column, $operator, $value)
    {
        if ($column === 'any' || $column === 'all') {
            $value = str_replace(['(', ')'],'', $value);
            [$relation, $value] = explode(',', $value);

            if (method_exists($this->subject->getModel(), $relation)) {
                if ($column === 'all') {
                    $builder->whereDoesntHave($relation, function (Builder $q) use ($value) {
                        [$column, $operator, $val] = explode(' ', $value);
                        return $q->where(trim($column), $this->mapOperator($operator), $val);
                    });
                } else {
                    $builder->whereHas($relation, function (Builder $q) use ($value) {
                        [$column, $operator, $val] = explode(' ', $value);
                        return $q->where(trim($column), $this->mapOperator($operator), $val);
                    });
                }
            }
        } else {
            $segments = explode('/', $column);
            $relation = $segments[0];
            $relatedField = $segments[1];
            // Regular relationship condition
            $builder->whereHas($relation, function ($q) use ($relatedField, $operator, $value) {
                $q->where(trim($relatedField), $this->mapOperator($operator), $value);
            });
        }
    }

    private function captureGroupsWithOperators($input)
    {
        $result = [];

        $paranthesis = '/\(([^()]*|(?R))*\)/';
        preg_match($paranthesis, $input, $result);
        if (!empty($result[0])) {
            $group_1 = $result[0];
            $remaining = str_replace($group_1, '', $input);

            $operators = [' and ', ' or '];
            foreach ($operators as $operator) {
                if (str_starts_with($remaining, $operator)) {
                    $remaining = str_replace($operator, '', $remaining);
                    return [
                        $this->ltrimandrtrim($group_1),
                        $this->ltrimandrtrim($remaining),
                        $this->ltrimandrtrim($operator),
                    ];
                }
            }
        }
        return [
            $input,
            null,
            null
        ];
    }

    private function ltrimandrtrim(string $input)
    {
        $input = trim($input);
        $input = ltrim($input, '(');
        return rtrim($input, ')');
    }
}
