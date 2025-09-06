<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public $timestamps = false;

    protected $table = 'Attendance';
    protected $primaryKey = 'AttendanceID';

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'RegistrationID', 'RegistrationID');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'VerifiedBy', 'UserID');
    }
}
