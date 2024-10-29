<?php

namespace Aqqo\OData\Tests\Testclasses;

use Aqqo\OData\Attributes\ODataProperty;
use Aqqo\OData\Attributes\ODataRelationship;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

#[ODataProperty('name', searchable: true)]
#[ODataProperty('description', searchable: true)]
#[ODataProperty('column', source: 'source')]
#[ODataProperty('test')]
#[ODataProperty('start_datetime_utc')]
#[ODataProperty('end_datetime_utc')]
class TestModel extends Model
{
    use HasFactory;

    protected $guarded = [];

    #[ODataRelationship(name: 'relatedModels')]
    public function relatedModels(): HasMany
    {
        return $this->hasMany(RelatedModel::class);
    }

    #[ODataRelationship(name: 'related_model')]
    public function relatedModel(): BelongsTo
    {
        return $this->belongsTo(RelatedModel::class);
    }

    public function otherRelatedModels(): HasMany
    {
        return $this->hasMany(RelatedModel::class);
    }

    public function relatedThroughPivotModels(): BelongsToMany
    {
        return $this->belongsToMany(RelatedThroughPivotModel::class, 'pivot_models');
    }

    public function relatedThroughPivotModelsWithPivot(): BelongsToMany
    {
        return $this->belongsToMany(RelatedThroughPivotModel::class, 'pivot_models')
            ->withPivot(['location']);
    }

    public function morphModels(): MorphMany
    {
        return $this->morphMany(MorphModel::class, 'parent');
    }

    public function scopeNamed(Builder $query, string $name): Builder
    {
        return $query->where('name', $name);
    }

    public function scopeUser(Builder $query, self $user): Builder
    {
        return $query->where('id', $user->id);
    }

    public function scopeUserInfo(Builder $query, self $user, string $name): Builder
    {
        return $query
            ->where('id', $user->id)
            ->where('name', $name);
    }

    public function scopeCreatedBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('created_at', [
            Carbon::parse($from), Carbon::parse($to),
        ]);
    }
}