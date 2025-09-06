<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EventRegistration extends Model
{
    public $timestamps = false;

    protected $table = 'EventRegistrations';
    protected $primaryKey = 'RegistrationID';

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'EventID', 'EventID');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'UserID', 'UserID');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(RegistrationStatus::class, 'StatusID', 'StatusID');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'RegistrationID', 'RegistrationID');
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class, 'RegistrationID', 'RegistrationID');
    }
}
