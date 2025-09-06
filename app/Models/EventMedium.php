<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventMedium extends Model
{
    protected $table = 'EventMedia';
    public $timestamps = false;
    protected $primaryKey = 'MediaID';

    protected $fillable = [
        'Description',
        'FileURL'
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'EventID', 'EventID');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'UploadedBy', 'UserID');
    }

    public function mediaType(): BelongsTo
    {
        return $this->belongsTo(MediaType::class, 'MediaTypeID', 'MediaTypeID');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(MediaVote::class, 'MediaID', 'MediaID');
    }

    protected function casts(): array
    {
        return [
            'IsApproved' => 'boolean',
        ];
    }
}
