<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            Role::SUPER_ADMIN,
            Role::ADMIN,
            Role::OPERATOR,
            Role::OBSERVER,
        ]).'-'.fake()->unique()->numberBetween(100, 999);

        return [
            'name' => $name,
            'label' => str($name)->replace('_', ' ')->title()->toString(),
        ];
    }
}
