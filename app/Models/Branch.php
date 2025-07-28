<?php

namespace App\Models;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $table = 'mt_branch';
    protected $guarded = ['id'];

    public function users()
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    public function menus()
    {
        return $this->hasMany(Menu::class, 'branch_id');
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'branch_id');
    }

    public function tables()
    {
        return $this->hasMany(Table::class, 'branch_id');
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class, 'branch_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'branch_id');
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class, 'branch_id');
    }


}
