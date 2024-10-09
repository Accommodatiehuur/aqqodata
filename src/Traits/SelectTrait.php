<?php

namespace Aqqo\OData\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrManyThrough;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait SelectTrait
{
    /**
     * @return void
     */
    public function addSelect(): void
    {
        $select = $this->request?->input('$select');

        if (!empty($select)) {
            $selects = [];
            foreach (explode(',', $select) as $item) {
                if ($this->isPropertySelectable($item)) {
                    $selects[] = $item;
                }
            }
            $this->subject->select($selects);
        }
    }

    /**
     * @param Builder|Relation $parent
     * @param string $relation
     * @return void
     */
    public function addSelectForExpand(Builder|Relation $parent, string $relation)
    {
        if ($parent->getQuery()->columns !== null) {
            $relationshipBinding = $parent->getRelation($relation);

            if ($relationshipBinding instanceof HasOneOrMany || $relationshipBinding instanceof HasOneOrManyThrough) {
                $parent->addSelect("{$parent->getModel()->getTable()}.{$relationshipBinding->getForeignKeyName()}");
            }

            if ($relationshipBinding instanceof BelongsTo || $relationshipBinding instanceof BelongsToMany) {
                // TODO
            }
        }
    }
}
