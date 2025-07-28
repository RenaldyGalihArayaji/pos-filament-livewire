<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'mt_category';
    protected $guarded = ['id'];

    public function menus()
    {
        return $this->hasMany(Menu::class, 'category_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }


}
