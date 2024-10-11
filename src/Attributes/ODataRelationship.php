<?php

namespace Aqqo\OData\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ODataRelationship
{
    public function __construct(
        private string $name,
        private ?string $description = null,
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}