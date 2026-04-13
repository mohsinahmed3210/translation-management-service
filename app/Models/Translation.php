<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'locale',
        'key',
        'value',
        'group',
    ];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'translation_tag');
    }

    /**
     * Scope to filter by locale.
     */
    public function scopeForLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope to search by tag names.
     */
    public function scopeWithTags($query, array $tags)
    {
        return $query->whereHas('tags', function ($q) use ($tags) {
            $q->whereIn('name', $tags);
        });
    }

    /**
     * Scope for full-text search on key or value.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('key', 'like', "%{$term}%")
              ->orWhere('value', 'like', "%{$term}%");
        });
    }
}
