<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BundleOptions extends Model
{
    use HasFactory;
    protected $table = 'bundle_options';
    protected $primaryKey = 'id';
    protected $fillable = ['sku_bundle', 'sku_option','option_name', 'option_price', 'qty', 'can_change_qty'];
}
