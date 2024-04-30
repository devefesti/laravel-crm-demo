<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProdAttribute extends Model
{
    use HasFactory;
    protected $table = 'order_prod_attributes';
    protected $fillable = ['order_id', 'item_id', 'sku', 'attribute_value'];
}

