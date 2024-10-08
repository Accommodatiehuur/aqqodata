<?php

namespace Aqqo\OData\Traits;

use Aqqo\OData\Utils\OperatorUtils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\BelongsToRelationship;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;

trait ExpandTrait
{
    /**
     * Function gets the $expand from the query and processes this within the subject.
     * @return void
     */
    public function addExpands()
    {
        if ($this->request) {
            $expand_query = (string)($this->request->input('$expand'));

            if (!empty($expand_query)) {
                // Parse expand into individual relationships (e.g., 'Customer,OrderItems($expand=Product)')
                foreach (explode(',', $expand_query) as $expand) {

                    // Handle expand with filter: e.g., objects($filter=name eq 10)
                    if (str_contains($expand, '(')) {
                        preg_match('/([A-Za-z]+)\((.*)\)/', $expand, $matches);
                        if (isset($matches[1])) {
                            $this->handleExpandsDetails($this->subject, $matches[2], $matches[1]);
                        }
                    } else if ($expandable = $this->isPropertyExpandable($expand)) {
                        $this->subject->with($expandable);
                    }
                }
            }
        }
    }

    /**
     * Functions handles details on the $expand. All nested ($) are handled here.
     *
     * @param string $expand
     * @param string $relation
     * @return void
     */
    private function handleExpandsDetails(Builder|Relation $builder, string $details, string $relation)
    {
        if ($expandable = $this->isPropertyExpandable($relation)) {
            $model = $this->getModel($builder, $expandable);

            $builder->with($expandable, function (Builder|Relation $builder) use ($model, $relation, $details) {
                $shortName = (new \ReflectionClass($model))->getShortName();
                foreach (preg_split('/;(?![^(]*\))/', $details) as $detail) {
                    [$key, $value] = explode('=', $detail, 2);
                    switch ($key) {
                        case '$filter':
                            [$column, $operator, $value] = $this->splitInput($value);
                            if ($this->isPropertyFilterable($column, $shortName)) {
                                $builder->where($column, OperatorUtils::mapOperator($operator), $value);
                            }
                            break;

                        case '$select':
                            $selects = [];
                            foreach (explode(',', $value) as &$select) {
                                if ($this->isPropertySelectable($select, $shortName)) {
                                    $select = "{$model->getTable()}.{$select}";
                                }
                            }

                            if ($builder instanceof HasOneOrMany || $builder instanceof HasOneOrManyThrough) {
                                $selects[] = "{$model->getTable()}.{$builder->getForeignKeyName()}";
                            }

                            if ($builder instanceof BelongsTo || $builder instanceof BelongsToMany) {
                                // TODO
                            }

                            $builder->select($selects);
                            break;

                        case '$expand':
                            if (str_contains($value, '(')) {
                                preg_match('/([A-Za-z]+)\((.*)\)/', $value, $matches);
                                if (isset($matches[1]) && isset($matches[2])) {
                                    $this->handleExpandsDetails($builder, $matches[2], "{$relation}.{$matches[1]}");
                                }
                            } else {
                                $value = rtrim($value, ')');
                                if ($expandable = $this->isPropertyExpandable($value, $shortName)) {
                                    $builder->with($expandable);
                                }
                            }
                            break;
                    }
                }
            });
        }
    }

    /**
     * @param Builder|Relation $builder
     * @param string $expand
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function getModel(Builder|Relation $builder, string $expand)
    {
        $model = $builder->getModel();
        foreach (explode('.', $expand) as $item) {
            $model = $model->$item()->getModel();
        }
        return $model;
    }
}
