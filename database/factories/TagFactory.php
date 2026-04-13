<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => $this->faker->unique()->randomElement([
                'mobile', 'desktop', 'web', 'ios', 'android',
                'email', 'sms', 'admin', 'public', 'internal',
            ]),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
