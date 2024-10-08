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
     */
    protected function handleAttributes(): void
    {
        $this->handleModel($this->subject);
    }

    /**
     * @param Builder<Model> $builder
     * @param string $parent
     * @return void
     * @throws \ReflectionException
     */
    private function handleModel(Builder $builder, string $parent = ''): void
    {
        $reflectionClass = new \ReflectionClass($builder->getModel());

        foreach ($reflectionClass->getAttributes(
            ODataProperty::class,
            \ReflectionAttribute::IS_INSTANCEOF
        ) as $attribute) {
            /** @var ODataProperty $instance */
            $instance = $attribute->newInstance();

            if ($instance->getSelectable()) {
                $this->selectables[strtolower($reflectionClass->getShortName())][] = $instance->getName();
            }

            if ($instance->getFilterable()) {
                $this->filterables[strtolower($reflectionClass->getShortName())][] = $instance->getName();
            }

            if ($instance->getSearchable()) {
                $this->searchables[strtolower($reflectionClass->getShortName())][] = $instance->getName();
            }

            if ($instance->getOrderable()) {
                $this->orderables[strtolower($reflectionClass->getShortName())][] = $instance->getName();
            }
        }

        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $reflectionAttributes = $reflectionMethod->getAttributes(ODataRelationship::class, \ReflectionAttribute::IS_INSTANCEOF);
            $relationshipInstance = $reflectionAttributes ? Arr::first($reflectionAttributes)?->newInstance() : null;
            if ($relationshipInstance) {
                /** @var ODataRelationship $relationshipInstance */
                $this->expandables[strtolower($reflectionClass->getShortName())][strtolower($relationshipInstance->getName())] = "{$parent}" . $reflectionMethod->getName();

                $model = $builder->getModel()->{$reflectionMethod->getName()}()->getModel();
                $reflection = new \ReflectionClass($model);

                if (!array_key_exists(strtolower($reflection->getShortName()), $this->expandables)) {
                    $this->handleModel($model->newQuery(), $reflectionMethod->getShortName() . ".");
                }
            }
        }
    }

    /**
     * @param string $property
     * @return bool
     */
    protected function isPropertySelectable(string $property, string|null $className = null): bool
    {
        $className ??= $this->subjectModelReflectionClass->getShortName();
        if (empty($this->selectables)) {
            return true;
        } else if (str_contains($property, '.')) {
            [$className, $property] = array_slice(explode('.', $property), -2, 2);
        }
        $className = strtolower($className);
        return
            !isset($this->selectables[$className])
            ||
            in_array($property, $this->selectables[$className] ?? []);
    }

    /**
     * @param string $property
     * @return bool
     */
    protected function isPropertyFilterable(string $property, string|null $className = null): bool
    {
        $className ??= $this->subjectModelReflectionClass->getShortName();
        if (empty($this->filterables)) {
            return true;
        } else if (str_contains($property, '.')) {
            [$className, $property] = array_slice(explode('.', $property), -2, 2);
        }
        $className = strtolower($className);
        return
            !isset($this->filterables[$className])
            ||
            in_array($property, $this->filterables[$className] ?? []);
    }

    /**
     * @param string $property
     * @return bool
     */
    protected function isPropertySearchable(string $property, string|null $className = null): bool
    {
        $className ??= $this->subjectModelReflectionClass->getShortName();
        if (empty($this->searchables)) {
            return true;
        } else if (str_contains($property, '.')) {
            [$className, $property] = array_slice(explode('.', $property), -2, 2);
        }
        $className = strtolower($className);
        return
            !isset($this->searchables[$className])
            ||
            in_array($property, $this->searchables[$className] ?? []);
    }

    /**
     * @param string $property
     * @return bool
     */
    protected function isPropertyOrderable(string $property, string|null $className = null): bool
    {
        $className ??= $this->subjectModelReflectionClass->getShortName();
        if (empty($this->orderables)) {
            return true;
        } else if (str_contains($property, '.')) {
            [$className, $property] = array_slice(explode('.', $property), -2, 2);
        }
        $className = strtolower($className);
        return
            !isset($this->orderables[$className])
            ||
            in_array($property, $this->orderables[$className] ?? []);
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
