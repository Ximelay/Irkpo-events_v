<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationType extends Model
{
    protected $table = 'NotificationTypes';
    public $timestamps = false;
    protected $primaryKey = 'TypeID';

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'NotificationTypeID', 'TypeID');
    }
}
