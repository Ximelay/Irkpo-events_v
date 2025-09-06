<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaType extends Model
{
    protected $table = 'MediaTypes';
    public $timestamps = false;
    protected $primaryKey = 'MediaTypeID';

    public function eventMedias(): HasMany
    {
        return $this->hasMany(EventMedia::class, 'MediaTypeID', 'MediaTypeID');
    }
}
