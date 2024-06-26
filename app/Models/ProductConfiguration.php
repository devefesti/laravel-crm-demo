<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductConfiguration extends Model
{
    use HasFactory;
    protected $table = 'prod_configurations';
    protected $primaryKey = 'id';
    protected $fillable = ['parent_sku', 'child_sku'];
}