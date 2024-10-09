<?php

namespace Aqqo\OData\Traits;

use Aqqo\OData\Utils\OperatorUtils;
use Aqqo\OData\Utils\StringUtils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
                foreach (StringUtils::splitODataExpression($expand_query) as $expand) {

                    // Handle expand with filter: e.g., objects($filter=name eq 10)
                    if (str_contains($expand, '(')) {
                        preg_match('/([A-Za-z]+)\((.*)\)/', $expand, $matches);
                        if (isset($matches[1])) {
                            $this->handleExpandsDetails($this->subject, $matches[2], $matches[1]);
                        }
                    } else if ($expandable = $this->isPropertyExpandable($expand)) {
                        $this->addSelectForExpand($this->subject, $expandable);
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
    private function handleExpandsDetails(Builder|Relation $parentBuilder, string $details, string $relation)
    {
        if ($expandable = $this->isPropertyExpandable($relation)) {
            $model = $this->getModel($parentBuilder, $expandable);

            $this->addSelectForExpand($parentBuilder, $expandable);
            $parentBuilder->with($expandable, function (Builder|Relation $relationshipBuilder) use ($parentBuilder, $model, $relation, $details, $expandable) {
                foreach (StringUtils::getSortedDetails($details) as $detail) {
                    [$key, $value] = explode('=', $detail, 2);
                    switch ($key) {
                        case '$select':
                            if ($this->select) {
                                $this->addSelectForExpand($parentBuilder, $expandable);
                                $selects = explode(',', $value);
                                foreach ($selects as &$select) {
                                    $select = "{$model->getTable()}.{$select}";
                                }

                                if ($relationshipBuilder instanceof HasOneOrMany || $relationshipBuilder instanceof HasOneOrManyThrough) {
                                    $selects[] = "{$model->getTable()}.{$relationshipBuilder->getForeignKeyName()}";
                                }

                                if ($relationshipBuilder instanceof BelongsTo || $relationshipBuilder instanceof BelongsToMany) {
                                    // TODO
                                }

                                $relationshipBuilder->select($selects);
                            }
                            break;

                        case '$filter':
                            $this->appendFilterQuery($value, $relationshipBuilder);
                            break;

                        case '$expand':
                            if (str_contains($value, '(')) {
                                preg_match('/([A-Za-z]+)\((.*)\)/', $value, $matches);
                                if (isset($matches[1]) && isset($matches[2])) {
                                    $this->handleExpandsDetails($relationshipBuilder, $matches[2], "{$relation}.{$matches[1]}");
                                }
                            } else {
                                $value = rtrim($value, ')');
                                if ($expandable = $this->isPropertyExpandable($value, (new \ReflectionClass($model))->getShortName())) {
                                    $relationshipBuilder->with($expandable);
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
