<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    private array $tags = [
        ['name' => 'mobile',   'description' => 'Translations for mobile apps'],
        ['name' => 'desktop',  'description' => 'Translations for desktop apps'],
        ['name' => 'web',      'description' => 'Translations for web applications'],
        ['name' => 'ios',      'description' => 'iOS-specific translations'],
        ['name' => 'android',  'description' => 'Android-specific translations'],
        ['name' => 'email',    'description' => 'Email templates'],
        ['name' => 'admin',    'description' => 'Admin panel translations'],
        ['name' => 'public',   'description' => 'Public-facing translations'],
    ];

    public function run(): void
    {
        foreach ($this->tags as $tag) {
            Tag::firstOrCreate(['name' => $tag['name']], $tag);
        }
    }
}
