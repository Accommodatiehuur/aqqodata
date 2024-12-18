<?php

namespace Aqqo\OData\Traits;

use Aqqo\OData\Utils\StringUtils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;

/**
 * @template TModelClass of Model
 * @template TRelatedModel of Model
 */
trait ExpandTrait
{
    /**
     * Apply $expand parameters from the request to the Eloquent query builder.
     *
     * @return void
     * @throws ReflectionException
     */
    public function addExpands(): void
    {
        $expandQuery = $this->request?->input('$expand');

        if (empty($expandQuery)) {
            return;
        }

        // Split the expand query into individual expand expressions
        $expandExpressions = StringUtils::splitODataExpression((string) $expandQuery);

        foreach ($expandExpressions as $expand) {
            $expand = trim($expand);

            $this->processExpandExpression($expand, $this->subject);
        }
    }

    /**
     * Recursively process an expand expression and apply it to the builder.
     *
     * @param string  $expand
     * @param Builder $builder
     * @param string  $parentRelation
     *
     * @return void
     * @throws ReflectionException
     */
    private function processExpandExpression(string $expand, Builder $builder, string $parentRelation = ''): void
    {
        if (Str::contains($expand, '(')) {
            [$relation, $details] = $this->parseExpandWithDetails($expand);

            if ($relation && $details) {
                $fullRelation = $relation;

                $expandable = $this->isPropertyExpandable($relation, $parentRelation);

                if ($expandable) {
                    $this->addSelectForExpand($builder, $relation);

                    $builder->with([$fullRelation => function ($query) use ($details, $relation) {
                        $this->handleExpandDetails($query, $details, $relation);
                    }]);
                }
            }
        } else {
            $fullRelation = $expand;

            $expandable = $this->isPropertyExpandable($expand, $parentRelation);

            if ($expandable) {
                $this->addSelectForExpand($builder, $expand);

                $builder->with([$fullRelation => function ($query) {
                    $this->resolveToDefaultSelects($query);
                }]);
            }
        }
    }

    /**
     * Handle expand details such as $filter, $select, and nested $expand.
     *
     * @param Builder $builder
     * @param string  $details
     * @param string  $relation
     *
     * @return void
     * @throws ReflectionException
     */
    private function handleExpandDetails(Builder|Relation $builder, string $details, string $relation): void
    {
        // If $builder is a Relation, get the underlying Builder
        if ($builder instanceof Relation) {
            $builder = $builder->getQuery();
        }

        $parsedDetails = StringUtils::getSortedDetails($details);

        foreach ($parsedDetails as $detail) {
            // Check if the detail starts with a known key
            if (Str::startsWith(strtolower($detail), '$select=')) {
                $value = substr($detail, strlen('$select='));
                $this->handleSelect($builder, $value, $relation);
            } elseif (Str::startsWith(strtolower($detail), '$filter=')) {
                $value = substr($detail, strlen('$filter='));
                $this->handleFilter($builder, $value);
            } elseif (Str::startsWith(strtolower($detail), '$expand=')) {
                $value = substr($detail, strlen('$expand='));
                $this->processExpandExpression($value, $builder, $relation);
            } else {
                // Assume it's an implied $expand
                $this->processExpandExpression($detail, $builder, $relation);
            }
        }

        // If no $select is specified, apply default selects
        if (!collect($parsedDetails)->contains(function($detail) {
            return Str::startsWith(strtolower($detail), '$select=');
        })) {
            $this->resolveToDefaultSelects($builder);
        }
    }

    /**
     * Parse an expand expression that contains details (e.g., filters, selects).
     *
     * @param string $expand
     *
     * @return array{0: string|null, 1: string|null} [relation, details] or [null, null] if parsing fails
     */
    private function parseExpandWithDetails(string $expand): array
    {
        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\((.+)\)$/', $expand, $matches)) {
            return [trim($matches[1]), trim($matches[2])];
        }

        return [null, null];
    }

    /**
     * Parse a detail string into key and value.
     *
     * @param string $detail
     *
     * @return array{0: string, 1: string} [key, value]
     */
    private function parseDetail(string $detail): array
    {
        $parts = explode('=', $detail, 2);
        return [trim($parts[0]), trim($parts[1] ?? '')];
    }

    /**
     * Handle the $select part of an expand.
     *
     * @param Builder $builder
     * @param string  $value
     * @param string  $expandable
     *
     * @return void
     */
    private function handleSelect(Builder $builder, string $value, string $expandable): void
    {
        if ($this->select) {
            $this->addSelectForExpand($builder, $expandable);
            $this->appendSelectQuery($value, $builder);
        }
    }

    /**
     * Handle the $filter part of an expand.
     *
     * @param Builder $builder
     * @param string  $value
     *
     * @return void
     * @throws ReflectionException
     */
    private function handleFilter(Builder $builder, string $value): void
    {
        $this->appendFilterQuery($value, $builder);
    }

    /**
     * Handle the $orderby part of an expand.
     *
     * @param Builder $builder
     * @param string  $value
     *
     * @return void
     */
    private function handleOrderBy(Builder $builder, string $value): void
    {
        $this->appendOrderBy($value, $builder);
    }

    /**
     * Retrieve the related model for a given expandable relation.
     *
     * @param Builder $builder
     * @param string  $expandable
     *
     * @return Model
     * @throws ReflectionException
     */
    private function getRelatedModel(Builder $builder, string $expandable): Model
    {
        /** @var Model $model */
        $model = $builder->getModel();

        foreach (explode('.', $expandable) as $relation) {
            if (method_exists($model, $relation)) {
                $model = $model->$relation()->getRelated();
            } else {
                throw new \InvalidArgumentException("Relation '{$relation}' does not exist on model " . get_class($model));
            }
        }

        return $model;
    }
}
