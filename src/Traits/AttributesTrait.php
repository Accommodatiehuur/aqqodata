<?php

namespace Aqqo\OData\Traits;

use Aqqo\OData\Attributes\ODataProperty;
use Aqqo\OData\Attributes\ODataRelationship;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait AttributesTrait
{
    /** @var array<string, array<int, string>> */
    private $selectables = [];

    /** @var array<string, array<int, string>> */
    private $filterables = [];

    /** @var array<string, array<int, string>> */
    private $searchables = [];

    /** @var array<string, array<int, string>> */
    private $orderables = [];

    /** @var array<string, array<string, string>> */
    private $expandables = [];

    /**
     * @return void
     * @throws \ReflectionException
     */
    protected function handleAttributes(): void
    {
        $this->handleModel($this->subject);
    }

    /**
     * @param Builder<Model> $builder
     * @return void
     * @throws \ReflectionException
     */
    private function handleModel(Builder $builder): void
    {
        $reflectionClass = new \ReflectionClass($builder->getModel());
        $shortName = strtolower($reflectionClass->getShortName());

        foreach ($reflectionClass->getAttributes(
            ODataProperty::class,
            \ReflectionAttribute::IS_INSTANCEOF
        ) as $attribute) {
            /** @var ODataProperty $instance */
            $instance = $attribute->newInstance();

            if ($instance->isSelectable()) {
                $this->selectables[$shortName][] = $instance->getName();
            }

            if ($instance->isFilterable()) {
                $this->filterables[$shortName][] = $instance->getName();
            }

            if ($instance->isSearchable()) {
                $this->searchables[$shortName][] = $instance->getName();
            }

            if ($instance->isOrderable()) {
                $this->orderables[$shortName][] = $instance->getName();
            }
        }

        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $reflectionAttributes = $reflectionMethod->getAttributes(ODataRelationship::class, \ReflectionAttribute::IS_INSTANCEOF);
            $relationshipInstance = $reflectionAttributes ? Arr::first($reflectionAttributes)?->newInstance() : null;
            if ($relationshipInstance) {
                /** @var ODataRelationship $relationshipInstance */
                $this->expandables[$shortName][strtolower($relationshipInstance->getName())] = $reflectionMethod->getName();

                $model = $builder->getModel()->{$reflectionMethod->getName()}()->getModel();
                $reflection = new \ReflectionClass($model);

                if (!array_key_exists(strtolower($reflection->getShortName()), $this->expandables)) {
                    $this->handleModel($model->newQuery());
                }
            }
        }
    }

    /**
     * @param string $property
     * @param string|null $className
     * @return bool
     */
    protected function isPropertySelectable(string $property, string|null $className = null): bool
    {
        return $this->isProperty($this->selectables, $property, $className);
    }

    /**
     * @param string $property
     * @param string|null $className
     * @return bool
     */
    protected function isPropertyFilterable(string $property, string|null $className = null): bool
    {
        return $this->isProperty($this->filterables, $property, $className);
    }

    /**
     * @param string $property
     * @param string|null $className
     * @return bool
     */
    protected function isPropertySearchable(string $property, string|null $className = null): bool
    {
        return $this->isProperty($this->searchables, $property, $className);
    }

    /**
     * @param string $property
     * @param string|null $className
     * @return bool
     */
    protected function isPropertyOrderable(string $property, string|null $className = null): bool
    {
        return $this->isProperty($this->orderables, $property, $className);
    }

    /**
     * @param array<string, array<int, string>> $array
     * @param string $property
     * @param string|null $className
     * @return bool
     */
    private function isProperty(array $array, string $property, string|null $className = null): bool
    {
        $className ??= $this->subjectModelReflectionClass->getShortName();
        if (empty($array)) {
            return true;
        } else if (str_contains($property, '.')) {
            [$className, $property] = array_slice(explode('.', $property), -2, 2);
        }
        $className = strtolower($className);
        return
            !isset($array[$className])
            ||
            in_array($property, $array[$className]);
    }

    /**
     * @param string $property
     * @param string|null $className
     * @return false|string
     */
    protected function isPropertyExpandable(string $property, string|null $className = null): false|string
    {
        $className ??= $this->subjectModelReflectionClass->getShortName();
        if (empty($this->expandables)) {
            return $property;
        } else if (str_contains($property, '.')) {
            [$className, $property] = array_slice(explode('.', $property), -2, 2);
        }
        return $this->expandables[strtolower(Str::singular($className))][strtolower($property)] ?? false;
    }
}
