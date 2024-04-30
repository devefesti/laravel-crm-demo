<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    use HasFactory;
    protected $table = 'product_attributes';
    protected $primaryKey = 'id';
    protected $fillable = ['product_sku', 'attribute_id', 'value'];
}
