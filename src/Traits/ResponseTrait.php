<?php

namespace Aqqo\OData\Traits;

use Illuminate\Database\Eloquent\Collection;

trait ResponseTrait
{
    public function getResponse(): array
    {
        $query = clone $this->subject;
        $query->getQuery()->offset = null;
        $query->getQuery()->limit = null;
        $all_records_count = $query->count();

        $value = $this->subject->get();
        $count = $value->count();
        if (property_exists($model = $this->subject->getModel(), 'resource')) {
            if ($value instanceof Collection) {
                $value = $model->resource::collection($value);
            } else {
                $value = new $model->resource($value);
            }
        }

        $response = [
            "@context" => $_SERVER['HTTP_HOST'] . 'api/$metadata#' . $this->subject->getModel()->getTable(), // TODO decide whether we want to support $metadata endpoint
            "value" => $value
        ];

        if ($all_records_count > ($count + ($this->subject->getQuery()->offset ?? 0))) {
            $uri = $_SERVER['REQUEST_URI'];

            if ($this->subject->getQuery()->offset === 0) {
                $skip = $this->subject->getQuery()->limit;
                $response['@nextLink'] = $_SERVER['HTTP_HOST'] . $uri . "&\$skip={$skip}";
            } else {
                $skip = $this->subject->getQuery()->offset + $this->subject->getQuery()->limit;
                $uri = str_replace('$skip=' . $this->subject->getQuery()->offset, "\$skip={$skip}", $uri);
                $response['@nextLink'] = $_SERVER['HTTP_HOST'] . $uri;
            }
        }

        return $response;
    }
}