<?php

declare(strict_types=1);

namespace Aqqo\OData\Attributes;

abstract class ODataProperty
{


    public function __construct(
        protected ODataString  $name,
        protected ?ODataString $description = null,
        protected bool         $searchable = false,
        protected bool         $filterable = true,
        protected bool         $orderable = true,
    ) {}

    /**
     * @return ODataString
     */
    public function getName(): ODataString
    {
        return $this->name;
    }

    /**
     * @return ODataString|null
     */
    public function getDescription(): ?ODataString
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function getSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * @return bool
     */
    public function getFilterable(): bool
    {
        return $this->filterable;
    }

    /**
     * @return bool
     */
    public function getOrderable(): bool
    {
        return $this->orderable;
    }

    abstract public function getType(): string;

}
