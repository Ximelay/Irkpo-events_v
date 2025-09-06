<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'Feedback';

    public $timestamps = false;
    protected $primaryKey = 'FeedbackID';
    protected $fillable = [
        'Comment',
        'Rating'
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'RegistrationID', 'RegistrationID');
    }
}
