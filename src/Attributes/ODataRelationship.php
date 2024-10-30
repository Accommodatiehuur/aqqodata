<?php

namespace Aqqo\OData\Attributes;


#[\Attribute(\Attribute::TARGET_METHOD)]
class ODataRelationship
{
    public function __construct(
        private string $name,
        private ?string $description = null,
        private ?String $source = null
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

    public function getSource(): ?string
    {
        return $this->source;
    }
}