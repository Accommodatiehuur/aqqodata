<?php

namespace Aqqo\OData;

use Aqqo\OData\Exceptions\QueryException;
use Aqqo\OData\Traits\AttributesTrait;
use Aqqo\OData\Traits\SearchTrait;
use Aqqo\OData\Traits\SelectTrait;
use Aqqo\OData\Utils\ClassUtils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Aqqo\OData\Traits\ExpandTrait;
use Aqqo\OData\Traits\FilterTrait;
use Aqqo\OData\Traits\SkipTrait;
use Aqqo\OData\Traits\TopTrait;
use Aqqo\OData\Traits\ResponseTrait;

/**
 * @template TModelClass of Model
 * @template TRelatedModel of Model
 */
class Query implements \JsonSerializable
{
    /** @use SelectTrait<TModelClass, TRelatedModel> */
    use SelectTrait;
    /** @use FilterTrait<TModelClass, TRelatedModel> */
    use FilterTrait;
    /** @use ExpandTrait<TModelClass, TRelatedModel> */
    use ExpandTrait;
    /** @use SearchTrait<TModelClass, TRelatedModel> */
    use SearchTrait;
    /** @use SkipTrait */
    use SkipTrait;
    /** @use TopTrait */
    use TopTrait;
    /** @use ResponseTrait */
    use ResponseTrait;
    /** @use AttributesTrait */
    use AttributesTrait;

    /**
     * @var \ReflectionClass<TModelClass>
     */
    protected \ReflectionClass $subjectModelReflectionClass;

    /**
     * @param Builder<TModelClass> $subject
     * @param bool $select
     * @param bool $filter
     * @param bool $expand
     * @param bool $skip
     * @param bool $top
     * @param Request|null $request
     * @throws \ReflectionException
     */
    public function __construct(
        protected Builder $subject,
        protected bool            $select = true,
        protected bool            $filter = true,
        protected bool            $expand = true,
        protected bool            $search = true,
        protected bool            $skip = true,
        protected bool            $top = true,
        protected ?Request        $request = null
    )
    {
        $this->request = !is_null($this->request) ? Request::createFrom($this->request) : app(Request::class);
        $this->subjectModelReflectionClass = new \ReflectionClass($this->subject->getModel());

        $this->handleAttributes();

        if ($select) $this->addSelect();

        if ($filter) $this->addFilters();

        if ($expand) $this->addExpands();

        if ($this->search) $this->addSearch();

        if ($skip) $this->addSkip();

        if ($top) $this->addTop();
    }

    /**
     * @param Builder<TModelClass>|string $subject
     * @param Request|null $request
     * @return static
     * @throws \ReflectionException
     */
    public static function for(Builder|string $subject, ?Request $request = null): static
    {
        $subject = is_subclass_of($subject, Model::class) ? $subject::query() : $subject;
        return new static($subject, request: $request);
    }

    /**
     * @return $this
     */
    public function clone(): static
    {
        return clone $this;
    }

    public function __clone()
    {
        $this->subject = clone $this->subject;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->subject->{$name};
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value)
    {
        $this->subject->{$name} = $value;
    }

    /**
     * @return string
     */
    public function toSql(): string
    {
        return $this->subject->toRawSql();
    }

    /**
     * @return Collection<string, array<string,mixed>>
     * @throws QueryException
     */
    public function get(): Collection
    {
        try {
            return $this->resolveCollection($this->subject->get());
        } catch (\Exception $e) {
            throw new QueryException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->getResponse();
    }

    /**
     * @param Collection<int, TModelClass> $collection
     * @return Collection<string, array<string,mixed>>
     */
    private function resolveCollection(Collection $collection): Collection
    {
        $collection->transform(function ($item) {
            return $this->resolveModel($item);
        });
        return $collection;
    }

    /**
     * @param Model $item
     * @return array<string, mixed>
     */
    private function resolveModel(Model &$item): array
    {
        $attributes = [];
        foreach ($this->selects[ClassUtils::getShortName($item)] as $odata_column => $db_column) {
            $attributes[$odata_column] = $item->{$db_column};
        }
        $item->setRawAttributes($attributes);
        foreach ($item->getRelations() as $key => $relation) {
            if ($relation instanceof Collection) {
                $attributes[$key] = $this->resolveCollection($relation);
            } else if ($relation instanceof Model)  {
                $attributes[$key] = $this->resolveModel($relation);
            }
        }
        return $attributes;
    }
}
