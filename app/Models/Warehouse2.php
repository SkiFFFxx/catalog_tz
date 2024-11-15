<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse2 extends Model
{
    use HasFactory;

    protected $table = 'warehouse_2';

    protected $fillable = ['product_id', 'quantity'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}