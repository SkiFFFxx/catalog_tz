<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'from_warehouse', 'to_warehouse', 'quantity', 'action', 'details']; // Поля для массового заполнения


    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
