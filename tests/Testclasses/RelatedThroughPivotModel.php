<?php

namespace Aqqo\OData\Tests\Testclasses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RelatedThroughPivotModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function testModels(): BelongsToMany
    {
        return $this->belongsToMany(TestModel::class, 'pivot_models');
    }
}