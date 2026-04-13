<?php

namespace App\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedTranslationsCommand extends Command
{
    protected $signature = 'translations:seed
                            {--count=100000 : Number of translations to generate}
                            {--chunk=1000   : Insert chunk size}';

    protected $description = 'Populate the database with a large number of translations for performance testing';

    private array $locales = ['en', 'fr', 'es', 'de', 'it', 'pt', 'nl', 'pl', 'ru', 'ja'];
    private array $groups  = ['general', 'auth', 'validation', 'pagination', 'errors', 'emails', 'notifications'];
    private array $words   = [
        'button', 'title', 'label', 'placeholder', 'error', 'success', 'warning',
        'heading', 'footer', 'header', 'nav', 'menu', 'sidebar', 'modal', 'toast',
        'form', 'input', 'select', 'checkbox', 'radio', 'table', 'list', 'card',
    ];

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $chunk = (int) $this->option('chunk');

        $tagIds = Tag::pluck('id')->toArray();

        if (empty($tagIds)) {
            $this->error('No tags found. Run "php artisan db:seed --class=TagSeeder" first.');
            return self::FAILURE;
        }

        $this->info("Seeding {$count} translations in chunks of {$chunk}...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $now = now()->toDateTimeString();
        $inserted = 0;

        while ($inserted < $count) {
            $batch = [];
            $batchSize = min($chunk, $count - $inserted);

            for ($i = 0; $i < $batchSize; $i++) {
                $locale = $this->locales[array_rand($this->locales)];
                $group  = $this->groups[array_rand($this->groups)];
                $word   = $this->words[array_rand($this->words)];
                // Timestamp ensures key uniqueness within the loop
                $key    = "{$group}.{$word}_" . ($inserted + $i) . '_' . uniqid();

                $batch[] = [
                    'locale'     => $locale,
                    'key'        => $key,
                    'value'      => $this->randomSentence(),
                    'group'      => $group,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Bulk insert — much faster than using Eloquent one by one
            DB::table('translations')->insert($batch);

            // Attach random tags via the pivot table
            $insertedIds = DB::table('translations')
                ->orderByDesc('id')
                ->limit($batchSize)
                ->pluck('id');

            $pivotRows = [];
            foreach ($insertedIds as $translationId) {
                // Each translation gets 1–3 random tags
                $assignedTags = array_slice(
                    array_unique(array_map(fn () => $tagIds[array_rand($tagIds)], range(0, mt_rand(0, 2)))),
                    0
                );
                foreach ($assignedTags as $tagId) {
                    $pivotRows[] = ['translation_id' => $translationId, 'tag_id' => $tagId];
                }
            }

            if (!empty($pivotRows)) {
                DB::table('translation_tag')->insertOrIgnore($pivotRows);
            }

            $inserted += $batchSize;
            $bar->advance($batchSize);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done! {$count} translations seeded successfully.");

        return self::SUCCESS;
    }

    private function randomSentence(): string
    {
        $subjects = ['The user', 'Your account', 'This feature', 'The system', 'Your request'];
        $verbs    = ['has been', 'was successfully', 'could not be', 'is being', 'will be'];
        $objects  = ['updated', 'created', 'deleted', 'processed', 'saved', 'loaded', 'submitted'];

        return $subjects[array_rand($subjects)] . ' '
             . $verbs[array_rand($verbs)] . ' '
             . $objects[array_rand($objects)] . '.';
    }
}
