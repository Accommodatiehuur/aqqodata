<?php

namespace Aqqo\OData;

use Aqqo\OData\Traits\AttributesTrait;
use Aqqo\OData\Traits\SelectTrait;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Aqqo\OData\Traits\ExpandTrait;
use Aqqo\OData\Traits\FilterTrait;
use Aqqo\OData\Traits\SkipTrait;
use Aqqo\OData\Traits\TopTrait;
use Aqqo\OData\Traits\ResponseTrait;

class Query implements \JsonSerializable
{
    use SelectTrait;
    use FilterTrait;
    use ExpandTrait;
    use SkipTrait;
    use TopTrait;
    use ResponseTrait;
    use AttributesTrait;

    /**
     * @var \ReflectionClass<Model>
     */
    protected \ReflectionClass $subjectModelReflectionClass;

    /**
     * @param EloquentBuilder<Model> $subject
     * @param bool $select
     * @param bool $filter
     * @param bool $expand
     * @param bool $skip
     * @param bool $top
     * @param Request|null $request
     * @throws \ReflectionException
     */
    public function __construct(
        protected EloquentBuilder $subject,
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
     * @param EloquentBuilder<Model>|string $subject
     * @param Request|null $request
     * @return static
     * @throws \ReflectionException
     */
    public static function for(EloquentBuilder|string $subject, ?Request $request = null): static
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
     * @return Collection<int, Model>
     */
    public function get(): Collection
    {
        return $this->subject->get();
    }

    /**
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->getResponse();
    }
}
