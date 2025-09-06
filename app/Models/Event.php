<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $table = 'Events';
    public $timestamps = false;
    public $primaryKey = 'EventID';

    protected $fillable = [
        'Title',
        'Description',
        'TypeID',
        'StartDateTime',
        'EndDateTime',
        'Location',
        'OrganizerID',
        'MaxParticipants',
        'FacultyID',
        'Budget',
        'ImageURL'
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(EventType::class, 'TypeID', 'TypeID');
    }
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'OrganizerID', 'UserID');
    }
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'FacultyID', 'FacultyID');
    }
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'RelatedEventID', 'EventID');
    }
    public function media(): HasMany
    {
        return $this->hasMany(EventMedium::class, 'EventID', 'EventID');
    }
    public function eventExpenses(): HasMany
    {
        return $this->hasMany(EventExpense::class, 'EventID', 'EventID');
    }

    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class, 'EventID', 'EventID');
    }

    public function userPoints(): HasMany
    {
        return $this->hasMany(UserPoint::class, 'EventID', 'EventID');
    }

    public function eventResponsibles(): HasMany
    {
        return $this->hasMany(EventResponsible::class, 'EventID', 'EventID');
    }

    public function eventChecklist(): HasMany
    {
        return $this->hasMany(EventChecklist::class, 'EventID', 'EventID');
    }
}
