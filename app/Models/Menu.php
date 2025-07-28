<?php

namespace App\Models;

use App\Models\Branch;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\TransactionDetail;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'mt_menu';
    protected $guarded = ['id'];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function transactionDetails()
    {
        $this->hasMany(TransactionDetail::class);
    }

      public static function generateSlug($name)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;
        while (self::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }
        return $slug;
    }

     public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', '%' . $search . '%');
    }
}
