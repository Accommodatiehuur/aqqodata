<?php

use function Aqqo\OData\Tests\Feature\createQueryFromParams;

it('Eq operator', function () {
    expect(\Aqqo\OData\Utils\OperatorUtils::mapOperator('eq'))->toEqual('=');
});