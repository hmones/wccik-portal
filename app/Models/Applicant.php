<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\ApplicantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Applicant extends Authenticatable
{
    /** @use HasFactory<ApplicantFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'name',
        'member_id',
        'email_verified_at',
        'last_signed_in_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_signed_in_at' => 'datetime',
    ];

    protected $hidden = [
        'remember_token',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function activeApplication(): ?Application
    {
        return $this->applications()
            ->whereIn('status', array_map(fn ($s) => $s->value, ApplicationStatus::activeStates()))
            ->latest('id')
            ->first();
    }
}
