<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    public $timestamps = false;

    protected $table = 'Groups';
    protected $primaryKey = 'GroupID';

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'FacultyID', 'FacultyID');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'GroupID', 'GroupID');
    }
}
