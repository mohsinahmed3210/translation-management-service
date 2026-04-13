<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 10)->index();
            $table->string('key')->index();
            $table->longText('value');
            $table->string('group', 100)->default('general')->index();
            $table->timestamps();

            // Composite unique: one key per locale
            $table->unique(['locale', 'key']);

            // Value is indexed via a separate fulltext migration for MySQL
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
