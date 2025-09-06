<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventChecklist extends Model
{
    protected $table = 'EventChecklists';

    public $timestamps = false;
    protected $primaryKey = 'ChecklistID';

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'EventID', 'EventID');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'AssignedTo', 'UserID');
    }

    protected function casts(): array
    {
        return [
            'IsCompleted' => 'boolean',
        ];
    }
}
