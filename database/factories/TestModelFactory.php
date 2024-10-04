<?php
namespace Aqqo\OData\Database\Factories;

use Aqqo\OData\Tests\Testclasses\TestModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestModel>
 */
class TestModelFactory extends Factory
{
    protected $model = \Aqqo\OData\Tests\Testclasses\TestModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
        ];
    }
}