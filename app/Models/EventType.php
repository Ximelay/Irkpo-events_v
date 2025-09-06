<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventType extends Model
{
    protected $table = 'EventTypes';
    public $timestamps = false;
    protected $primaryKey = 'TypeID';

    protected $fillable = [
        'TypeName'
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'TypeID', 'TypeID');
    }
}
