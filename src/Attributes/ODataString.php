<?php
declare(strict_types=1);

namespace Aqqo\OData\Attributes;

use Attribute;
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ODataString extends ODataProperty
{
    public function getType(): string
    {
        return 'Edm.String';
    }
}