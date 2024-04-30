<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProds extends Model
{
    use HasFactory;
    protected $table = 'order_prods';
    protected $fillable = ['id','order_id','sku', 'quantity','product_name', 'item_id', 'product_type'];
}
