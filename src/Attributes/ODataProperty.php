<?php

declare(strict_types=1);

namespace Aqqo\OData\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ODataProperty
{
    public function __construct(
        protected string  $name,
        protected ?string $description = null,
        protected bool    $searchable = false,
        protected bool    $filterable = true,
        protected bool    $orderable = true,
    )
    {

    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
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
}
