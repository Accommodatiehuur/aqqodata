<?php

namespace Aqqo\OData;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Aqqo\OData\Traits\ExpandTrait;
use Aqqo\OData\Traits\FilterTrait;
use Aqqo\OData\Traits\SkipTrait;
use Aqqo\OData\Traits\TopTrait;

class Query implements \ArrayAccess
{
    use FilterTrait;
    use ExpandTrait;
    use SkipTrait;
    use TopTrait;

    public function __construct(
        protected EloquentBuilder|Relation $subject,
        bool $filter = true,
        bool $expand = true,
        bool $skip = true,
        bool $top = true,
        protected ?Request $request = null
    ) {
        $this->request = $request
            ? QueryBuilderRequest::fromRequest($request)
            : app(QueryBuilderRequest::class);

        if ($filter) {
            // $this->getAnnotatedFilterableValues()
            $this->addFilters();
        }

        if ($expand) {
            $this->addExpands();
        }

        if ($skip) {
            $this->addSkip();
        }

        if ($top) {
            $this->addTop();
        }
    }

    public static function for(
        EloquentBuilder|Relation|string $subject,
        ?Request $request = null
    ): static {
        if (is_subclass_of($subject, Model::class)) {
            $subject = $subject::query();
        }

        return new static($subject, request: $request);
    }

    public function clone(): static
    {
        return clone $this;
    }

    public function __clone()
    {
        $this->subject = clone $this->subject;
    }

    public function __get($name)
    {
        return $this->subject->{$name};
    }

    public function __set($name, $value)
    {
        $this->subject->{$name} = $value;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->subject[$offset]);
    }

    public function offsetGet($offset): bool
    {
        return $this->subject[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->subject[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->subject[$offset]);
    }

    public function toSql(): string
    {
        return $this->subject->toRawSql();
    }

    public function get()
    {
        if (property_exists($model = $this->subject->getModel(), 'resource')) {
            // TODO
        }
        return $this->subject->get();
    }

    public function getResponse($odata_output = true)
    {
        // TODO
    }
}
