<?php

namespace Aqqo\OData\Traits;

use Aqqo\OData\Utils\OperatorUtils;
use Aqqo\OData\Utils\StringUtils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\AssignOp\Mod;

trait FilterTrait
{
    /**
     * @return void
     */
    public function addFilters(): void
    {
        $filter = $this->request?->input('$filter');
        if (!empty($filter)) {
            $this->appendQuery(strval($filter), $this->subject);
        }
    }

    /**
     * @param string $filter
     * @param Builder<Model> $builder
     * @param string $statement
     * @return Builder<Model>
     */
    public function appendQuery(string $filter, Builder $builder, string $statement = 'where'): Builder
    {
        $expressions = StringUtils::splitODataExpression($filter);

        if ((str_contains($filter, '(') || str_contains($filter, ')')) && !(count($expressions) == 1 && (str_starts_with($expressions[0], 'any(') || str_starts_with($expressions[0], 'all(')))) {
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
                        if ($column && $operator && $value) {
                            $builder->{$istatement}($column, $operator, $value);
                        }

                    }
                }
            }
        }

        return $builder;
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

            if (method_exists($this->subject->getModel(), $relation)) {
                if ($column === 'all') {
                    $builder->whereDoesntHave($relation, function (Builder $q) use ($value) {
                        [$column, $operator, $val] = $this->splitInput($value, inverse_operator: true);
                        if ($column && $operator && $val) {
                            return $q->where($column, $operator, $val);
                        }
                    });
                } else {
                    $builder->whereHas($relation, function (Builder $q) use ($value) {
                        [$column, $operator, $val] = $this->splitInput($value);
                        if ($column && $operator && $val) {
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
        $split = preg_split('/(?<=\s)(eq|ne|ge|gt|le|lt|)(?=\s)/', $input, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        return [
            trim($split[0] ?? ''),
            $inverse_operator ? OperatorUtils::inverseOperator($split[1] ?? '') : OperatorUtils::mapOperator($split[1] ?? ''),
            trim($split[2] ?? '', " '")
        ];
    }
}
