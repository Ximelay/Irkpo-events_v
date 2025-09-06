<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationStatus extends Model
{
    protected $table = 'RegistrationStatuses';

    public $timestamps = false;
    protected $primaryKey = 'StatusID';

    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class, 'StatusID', 'StatusID');
    }
}
