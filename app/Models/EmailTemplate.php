<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'subject_en',
        'subject_ur',
        'body_en',
        'body_ur',
        'available_variables',
    ];

    protected $casts = [
        'available_variables' => 'array',
    ];

    public function subjectFor(string $locale): string
    {
        return $locale === 'ur' ? $this->subject_ur : $this->subject_en;
    }

    public function bodyFor(string $locale): string
    {
        return $locale === 'ur' ? $this->body_ur : $this->body_en;
    }
}
