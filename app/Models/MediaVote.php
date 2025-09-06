<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaVote extends Model
{
    protected $table = 'MediaVotes';
    public $timestamps = false;
    protected $primaryKey = 'VoteID';

    public function media(): BelongsTo
    {
        return $this->belongsTo(EventMedium::class, 'MediaID', 'MediaID');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'UserID', 'UserID');
    }
}
