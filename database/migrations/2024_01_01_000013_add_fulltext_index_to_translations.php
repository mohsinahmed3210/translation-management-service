<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a FULLTEXT index on translations.key + translations.value.
 * Only runs for MySQL — silently skipped for SQLite (used in tests).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE translations ADD FULLTEXT ft_translations_key_value (`key`, `value`)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE translations DROP INDEX ft_translations_key_value');
    }
};
