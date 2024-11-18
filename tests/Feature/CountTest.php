<?php

namespace Aqqo\OData\Tests\Feature;

it('Runs count', function (?bool $count, bool $should_have_count) {
    $query = createQueryFromParams(count: $count);
    print_r($query->getResponse());
    if ($should_have_count) {
        expect($query->getResponse())->toHaveKey('@count');
    } else {
        expect($query->getResponse())->not->toHaveKey('@count');
    }
})->with([
    "Without count" => [false, false],
    "With count" => [true, true],
]);

