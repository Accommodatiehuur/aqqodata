<?php

namespace Aqqo\OData\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrManyThrough;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use PhpParser\Node\Expr\AssignOp\Mod;

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
     * @param string $select
     * @param Builder<Model>|Relation<Model> $builder
     * @return void
     */
    public function appendSelectQuery(string $select, Builder|Relation $builder): void
    {
        $selects = [];
        foreach (explode(',', $select) as $item) {
            if ($this->isPropertySelectable($item, (new \ReflectionClass($builder))->getShortName())) {
                $selects[] = $item;
            }
        }
        if (!empty($selects)) {
            $builder->select($selects);
        }
    }

    /**
     * @param Builder<Model>|Relation<Model> $parent
     * @param string $relation
     * @return void
     */
    public function addSelectForExpand(Builder|Relation $parent, string $relation)
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
