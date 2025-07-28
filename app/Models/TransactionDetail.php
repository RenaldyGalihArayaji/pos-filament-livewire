<?php

namespace App\Models;

use App\Models\Menu;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    protected $table = 'dt_transaction_detail';
    protected $guarded = ['id'];


    public function transactions()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function menus()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }
}
