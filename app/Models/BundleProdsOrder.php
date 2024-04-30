<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BundleProdsOrder extends Model
{
    use HasFactory;
    protected $table = 'bundle_prods_order';
    protected $fillable = ['id', 'order_id', 'item_id', 'sku_parent', 'sku_option','option_name', 'quantity'];
}