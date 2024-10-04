<?php

namespace Aqqo\OData\Traits;

use Aqqo\OData\Attributes\ODataProperty;
use Aqqo\OData\Attributes\ODataRelationship;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

trait AttributesTrait
{
    /** @var array<int, string> */
    private $filterables = [];

    /** @var array<int, string> */
    private $searchables = [];

    /** @var array<int, string> */
    private $orderables = [];

    /** @var array<int, string> */
    private $expandables = [];
    /**
     * @return void
     */
    protected function handleAttributes(): void
    {
        $this->handleModel($this->subject);
    }

    private function handleModel(Builder $model, string $parent = ''): void
    {
        $reflectionClass = new \ReflectionClass($model);

        foreach ($reflectionClass->getAttributes(
            ODataProperty::class,
            \ReflectionAttribute::IS_INSTANCEOF
        ) as $attribute) {
            /** @var ODataProperty $instance */
            $instance = $attribute->newInstance();

            if ($instance->getFilterable()) {
                $this->filterables[] = "{$parent}{$instance->getName()}";
            }

            if ($instance->getSearchable()) {
                $this->searchables[] = "{$parent}{$instance->getName()}";
            }

            if ($instance->getOrderable()) {
                $this->orderables[] = "{$parent}{$instance->getName()}";
            }
        }

        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $reflectionAttributes = $reflectionMethod->getAttributes(ODataRelationship::class, \ReflectionAttribute::IS_INSTANCEOF);
            $relationshipInstance = $reflectionAttributes ? Arr::first($reflectionAttributes)->newInstance() : null;
            if ($relationshipInstance) {
                /** @var ODataRelationship $relationshipInstance */
                $this->expandables[] = "{$parent}{$relationshipInstance->getName()}";

                $parent .= "{$reflectionClass->getShortName()}.";
                $this->handleModel($model->$reflectionMethod()->newModelInstance(), $parent);
            }
        }
    }

    /**
     * @param string $property
     * @return bool
     */
    protected function isPropertyFilterable(string $property): bool
    {
        return empty($this->filterables) || in_array($property, $this->filterables);
    }

    /**
     * @param string $property
     * @return bool
     */
    protected function isPropertySearchable(string $property): bool
    {
        return empty($this->searchables) || in_array($property, $this->searchables);
    }

    /**
     * @param string $property
     * @return bool
     */
    protected function isPropertyOrderable(string $property): bool
    {
        return empty($this->orderables) || in_array($property, $this->orderables);
    }

    /**
     * @param string $property
     * @return bool
     */
    protected function isPropertyExpandable(string $property): bool
    {
        return empty($this->expandables) || in_array($property, $this->expandables);
    }
}