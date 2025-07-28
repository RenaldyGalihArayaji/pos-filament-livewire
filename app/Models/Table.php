<?php

namespace App\Models;

use App\Models\Branch;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
     protected $table = 'mt_table';

    protected $guarded = ['id'];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'table_id');
    }

}
