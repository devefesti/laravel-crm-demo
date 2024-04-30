<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigurableOption extends Model
{
    use HasFactory;
    protected $table = 'configurable_options';
    protected $primaryKey = 'id';
    protected $fillable = ['sku_configuration', 'attribute_id'];
}
