<?php

namespace Aqqo\OData;

use Aqqo\OData\Traits\AttributesTrait;
use Aqqo\OData\Traits\SelectTrait;
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
     * @return Collection<int, TModelClass>
     */
    public function get(): Collection
    {
        try {
            return $this->subject->get();
        } catch (\Exception $e) {
            abort(400);
        }
    }

    /**
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->getResponse();
    }
}
