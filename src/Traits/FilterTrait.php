<?php

namespace Aqqo\OData\Traits;

use Aqqo\OData\Utils\OperatorUtils;
use Aqqo\OData\Utils\StringUtils;
use Illuminate\Database\Eloquent\Builder;

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
        $expressions = StringUtils::splitODataExpression($filter);

        if ((str_contains($filter, '(') || str_contains($filter, ')')) && !(count($expressions) == 1 && (str_starts_with($expressions[0], 'any(') || str_starts_with($expressions[0], 'all(')))) {
            $builder->where(function (Builder $q) use ($filter, $statement, $expressions) {
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

                if (str_starts_with($gf, 'all(') || str_starts_with($gf, 'any(')) {
                    preg_match('/(any|all)\((.+)\)/', $gf, $matches);
                    $this->applyRelationshipCondition($builder, $matches[1], '', $matches[2]);
                } else {
                    [$column, $operator, $value] = preg_split('/(?<=\s)(eq|ne|ge|gt|le|lt|)(?=\s)/', $gf, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                    $value = trim($value, " '");
                    $builder->{$istatement}(trim($column), OperatorUtils::mapOperator($operator), $value);
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
                        [$column, $operator, $value] = preg_split('/(?<=\s)(eq|ne|ge|gt|le|lt|)(?=\s)/', $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                        $value = trim($value, " '");

                        return $q->where(trim($column), OperatorUtils::inverseOperator($operator), $value);
                    });
                } else {
                    $builder->whereHas($relation, function (Builder $q) use ($value) {
                        preg_match('/(\S+)\s+(\S+)\s+\'([^\']+)\'/', $value, $matches);
                        if ($matches[1] && $matches[2] && $matches[3]) {
                            return $q->where(trim($matches[1]), OperatorUtils::mapOperator($matches[2]), $matches[3]);
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
}
