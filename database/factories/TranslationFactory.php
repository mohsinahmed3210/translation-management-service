<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TranslationFactory extends Factory
{
    private static array $locales = ['en', 'fr', 'es', 'de', 'it', 'pt', 'nl', 'pl', 'ru', 'ja'];
    private static array $groups = ['general', 'auth', 'validation', 'pagination', 'errors', 'emails', 'notifications'];
    private static int $counter = 0;

    public function definition(): array
    {
        $locale = $this->faker->randomElement(self::$locales);
        $group = $this->faker->randomElement(self::$groups);

        // Use a counter to guarantee uniqueness across the (locale, key) composite
        self::$counter++;
        $key = $group . '.' . $this->faker->word() . '_' . self::$counter;

        return [
            'locale' => $locale,
            'key'    => $key,
            'value'  => $this->faker->sentence(),
            'group'  => $group,
        ];
    }
}
