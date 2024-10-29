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

            $db_column = $instance->getSource() ?? $instance->getName();
            $odata_column = $instance->getName();

            // Support for dynamic resolver.
            if (empty($instance->getSource()) && $reflectionClass->hasMethod('oData' . ucfirst(strtolower($instance->getName())) . 'Resolver')) {
                $db_column = $builder->getModel()->{'oData' . ucfirst($instance->getName()) . 'Resolver'}();
            }

            if ($instance->isSelectable()) {
                $this->selectables[$shortName][$odata_column] = $db_column;
            }

            if ($instance->isFilterable()) {
                $this->filterables[$shortName][$odata_column] = $db_column;
            }

            if ($instance->isSearchable()) {
                $this->searchables[$shortName][$odata_column] = $db_column;
            }

            if ($instance->isOrderable()) {
                $this->orderables[$shortName][$odata_column] = $db_column;
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
     * @return string|bool
     */
    protected function isPropertySelectable(string $property, string|null $className = null): string|bool
    {
        return $this->isProperty($this->selectables, $property, $className);
    }

    /**
     * @param string $property
     * @param string|null $className
     * @return string|bool
     */
    protected function isPropertyFilterable(string $property, string|null $className = null): string|bool
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
     * @param array $array
     * @param string $property
     * @param string|null $className
     * @return string|bool
     */
    private function isProperty(array $array, string $property, string|null $className = null): string|bool
    {
        $className ??= $this->subjectModelReflectionClass->getShortName();
        if (empty($array)) {
            return true;
        } else if (str_contains($property, '.')) {
            [$className, $property] = array_slice(explode('.', $property), -2, 2);
        }
        $className = strtolower($className);
        return $array[$className][$property] ?? false;
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

    /**
     * @param string|null $className
     * @return array<int, string>|string[]
     */
    protected function getSearchables(string|null $className = null): array
    {
        $className ??= $this->subjectModelReflectionClass->getShortName();
        return $this->searchables[strtolower($className)] ?? [];
    }
}
