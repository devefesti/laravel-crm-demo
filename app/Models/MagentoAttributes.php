<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MagentoAttributes extends Model
{
    use HasFactory;
    protected $table = 'mg_attributes';
    protected $primaryKey = 'id';
    protected $fillable = ['attribute_id', 'attribute_code'];
}