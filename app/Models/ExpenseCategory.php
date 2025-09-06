<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    protected $table = 'ExpenseCategories';

    public $timestamps = false;
    protected $primaryKey = 'CategoryID';

    public function eventsExpenses(): HasMany
    {
        return $this->hasMany(EventExpense::class, 'CategoryID', 'CategoryID');
    }
}
