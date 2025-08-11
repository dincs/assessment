<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    //
    use HasFactory, SoftDeletes;
    protected $table = 'product';
    protected $fillable = [
        'name',
        'category_id',
        'description',
        'price',
        'stock',
        'enabled',
    ];
    protected $casts = ['enabled' => 'boolean', 'price' => 'decimal:2'];
    public $timestamps = true;
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
