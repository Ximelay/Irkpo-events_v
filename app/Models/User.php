<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'Users';
    public $timestamps = false;
    protected $primaryKey = 'UserID';
    protected $fillable = [
        'FirstName',
        'LastName',
        'Email',
        'Phone',
        'TelegramID',
        'PasswordHash',
        'GroupID',
        'IsActive',
        'Salt',
        'RoleID',
        'GroupID',
        'IsActive'
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'RoleID', 'RoleID');
    }
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'GroupID', 'GroupID');
    }
    public function notification(): HasMany
    {
        return $this->hasMany(Notification::class, 'UserID', 'UserID');
    }
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'OrganizerID', 'UserID');
    }
    public function uploadedMedia(): HasMany
    {
        return $this->hasMany(EventMedium::class, 'UploadedBy', 'UserID');
    }
    public function votes(): HasMany
    {
        return $this->hasMany(MediaVote::class, 'UserID', 'UserID');
    }
    public function eventExpenses(): HasMany
    {
        return $this->hasMany(EventExpense::class, 'PurchasedBy', 'UserID');
    }
    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class, 'UserID', 'UserID');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'VerifiedBy', 'UserID');
    }

    public function userPoints(): HasMany
    {
        return $this->hasMany(UserPoint::class, 'UserID', 'UserID');
    }

    public function eventResponsibles(): HasMany
    {
        return $this->hasMany(User::class, 'UserID', 'UserID');
    }

    public function eventChecklist(): HasMany
    {
        return $this->hasMany(EventChecklist::class, 'AssignedTo', 'UserID');
    }
    protected function casts(): array
    {
        return [
            'IsActive' => 'boolean',
        ];
    }
}
