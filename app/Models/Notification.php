<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'Notifications';
    public $timestamps = false;
    protected $primaryKey = 'NotificationID';

    protected $fillable = [
        'Title',
        'Message',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'UserID', 'UserID');
    }
    public function relatedEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'RelatedEventID', 'EventID');
    }

    public function notificationType(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class, 'NotificationTypeID', 'TypeID');
    }

    protected function casts(): array
    {
        return [
            'IsRead' => 'boolean',
        ];
    }
}
