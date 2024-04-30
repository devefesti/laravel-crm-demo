<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MagentoAttributeLabel extends Model
{
    use HasFactory;
    protected $table = 'mg_attribute_labels';
    protected $primaryKey = 'id';
    protected $fillable = ['attribute_id', 'value', 'option_id'];
}