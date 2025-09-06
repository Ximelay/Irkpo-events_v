<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPoint extends Model
{
    protected $table = 'UserPoints';

    public $timestamps = false;
    protected $primaryKey = 'PointID';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'UserID', 'UserID');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'EventID', 'EventID');
    }
}
