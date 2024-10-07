<?php

namespace Aqqo\OData\Traits;

use Aqqo\OData\Utils\OperatorUtils;
use Illuminate\Database\Eloquent\Builder;
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
                preg_match_all('/[^,]+\([^)]*\)|[^,]+/', $expand_query, $matches);

                // Parse expand into individual relationships (e.g., 'Customer,OrderItems($expand=Product)')
                foreach ($matches[0] ?? [] as $expand) {

                    // Handle expand with filter: e.g., objects($filter=name eq 10)
                    if (str_contains($expand, '(')) {
                        preg_match('/([A-Za-z]+)\((.*)\)/', $expand, $matches);
                        if (isset($matches[1])) {
                            $this->handleExpandsDetails($this->subject, $expand, $matches[1]);
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
    private function handleExpandsDetails(Builder|Relation $builder, string $expand, string $relation)
    {
        $matches = [2 => $expand];
        if (str_contains($expand, '(')) {
            preg_match('/([A-Za-z]+)\((.*\))/', $expand, $matches);
        }

        if (isset($matches[2])) {
            $details = explode(';', $matches[2]);

            if ($expandable = $this->isPropertyExpandable($relation)) {
                $model = $this->getModel($builder, $expandable);

                $builder->with($expandable, function (Builder|Relation $builder) use ($model, $relation, $details) {
                    foreach ($details as $detail) {
                        [$key, $value] = explode('=', $detail, 2);
                        switch ($key) {
                            case '$filter':
                                [$column, $operator, $value] = $this->splitInput($value);
                                $builder->where($column, OperatorUtils::mapOperator($operator), $value);
                                break;

                            case '$select':
                                $selects = explode(',', $value);
                                foreach ($selects as &$select) {
                                    $select = "{$model->getTable()}.{$select}";
                                }
                                $builder->select($selects);
                                break;

                            case '$expand':
                                if (str_contains($value, '(')) {
                                    preg_match('/([A-Za-z]+)\((.*)\)/', $value, $matches2);

                                    if (isset($matches2[1]) && isset($matches2[2])) {
                                        $this->handleExpandsDetails($builder, $matches2[2], "{$relation}.{$matches2[1]}");
                                    }
                                } else {
                                    $value = rtrim($value, ')');
                                    if ($expandable = $this->isPropertyExpandable($value, (new \ReflectionClass($model))->getShortName())) {
                                        $builder->with($expandable);
                                    }
                                }
                                break;
                        }
                    }
                });
            }
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
