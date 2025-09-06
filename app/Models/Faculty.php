<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    protected $table = 'Faculties';
    public $timestamps = false;
    protected $primaryKey = 'FacultyID';

    protected $fillable = [
        'FacultyName'
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'FacultyID', 'FacultyID');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class, 'FacultyID', 'FacultyID');
    }
}
