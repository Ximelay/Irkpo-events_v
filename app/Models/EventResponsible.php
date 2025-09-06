<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventResponsible extends Model
{
    protected $table = 'EventResponsibles';

    public $timestamps = false;
    protected $primaryKey = 'ResponsibleID';

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'EventID', 'EventID');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'UserID', 'UserID');
    }
}
