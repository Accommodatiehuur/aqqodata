<?php

namespace Aqqo\OData\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrManyThrough;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of Model
 * @template TRelatedModel of Model
 */
trait SelectTrait
{
    /**
     * @return void
     */
    public function addSelect(): void
    {
        $select = $this->request?->input('$select');

        if (!empty($select)) {
            $this->appendSelectQuery($select, $this->subject);
        }
    }

    /**
     * Append select clauses to the builder or relation.
     *
     * @param string $select
     * @param Builder<TModelClass> $builder
     * @return void
     */
    public function appendSelectQuery(string $select, Builder $builder): void
    {
        $selects = [];
        if (!empty($select)) {
            $shortName = (new \ReflectionClass($builder->getModel()))->getShortName();
            foreach (explode(',', $select) as $item) {
                if ($this->isPropertySelectable(trim($item), $shortName)) {
                    $selects[] = trim($item);
                }
            }
        }

        if (!empty($selects)) {
            $builder->select($selects);
        }
    }

    /**
     * @param Builder<TModelClass> $parent
     * @param string $relation
     * @return void
     */
    public function addSelectForExpand(Builder $parent, string $relation): void
    {
        if ($parent->getQuery()->columns !== null) {
            $relationshipBinding = $parent->getRelation($relation);

            if ($relationshipBinding instanceof HasOneOrMany || $relationshipBinding instanceof HasOneOrManyThrough) {
                $parent->addSelect("{$parent->getModel()->getTable()}.{$relationshipBinding->getLocalKeyName()}");
            }

            if ($relationshipBinding instanceof BelongsTo || $relationshipBinding instanceof BelongsToMany) {
                // TODO
            }
        }
    }
}
