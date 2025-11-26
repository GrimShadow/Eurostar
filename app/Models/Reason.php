<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reason extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    protected $casts = [
        'description' => 'array',
    ];

    /**
     * Get the description attribute, returning English description by default for backward compatibility
     */
    public function getDescriptionAttribute($value)
    {
        // Access raw attribute to get the actual stored value
        $rawValue = $this->attributes['description'] ?? null;

        if ($rawValue === null) {
            return '';
        }

        // If already an array (from cast), return English description
        if (is_array($rawValue)) {
            return $rawValue['en'] ?? '';
        }

        // If JSON string, decode and return English
        if (is_string($rawValue)) {
            $decoded = json_decode($rawValue, true);
            if (is_array($decoded)) {
                return $decoded['en'] ?? '';
            }
        }

        // Fallback to empty string
        return '';
    }

    /**
     * Get description for a specific language
     *
     * @param  string  $lang  Language code (en, nl, fr)
     */
    public function getDescriptionForLanguage(string $lang = 'en'): string
    {
        $descriptions = $this->attributes['description'] ?? null;

        if ($descriptions === null) {
            return '';
        }

        // If already decoded (from cast), use directly
        if (is_array($descriptions)) {
            return $descriptions[$lang] ?? '';
        }

        // If JSON string, decode first
        if (is_string($descriptions)) {
            $decoded = json_decode($descriptions, true);
            if (is_array($decoded)) {
                return $decoded[$lang] ?? '';
            }
        }

        return '';
    }

    /**
     * Get all descriptions as array
     */
    public function getAllDescriptions(): array
    {
        $descriptions = $this->attributes['description'] ?? null;

        if ($descriptions === null) {
            return [];
        }

        // If already decoded (from cast), return directly
        if (is_array($descriptions)) {
            return $descriptions;
        }

        // If JSON string, decode first
        if (is_string($descriptions)) {
            $decoded = json_decode($descriptions, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
