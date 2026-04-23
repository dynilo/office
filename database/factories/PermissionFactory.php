<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            Permission::VIEW_ADMIN,
            Permission::MANAGE_AGENTS,
            Permission::MANAGE_TASKS,
            Permission::VIEW_AUDIT,
        ]).'.'.fake()->unique()->numberBetween(100, 999);

        return [
            'name' => $name,
            'label' => str($name)->replace(['.', '_'], ' ')->title()->toString(),
        ];
    }
}
