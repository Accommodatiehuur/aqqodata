<?php
namespace Aqqo\OData\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TestModelFactory extends Factory
{
    protected $model = \Aqqo\OData\Tests\Testclasses\TestModel::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
        ];
    }
}