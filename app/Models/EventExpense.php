<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventExpense extends Model
{
    protected $table = 'EventExpenses';

    public $timestamps = false;
    protected $primaryKey = 'ExpenseID';

    protected $fillable = [
        'ItemName',
        'Amount',
        'CategoryID'
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'EventID', 'EventID');
    }
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'CategoryID', 'CategoryID');
    }
    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'PurchasedBy', 'UserID');
    }
}
