<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPack extends Model
{
    use HasFactory;
    protected $table = 'order_packs';
    protected $fillable = ["id","order_id","pack_id","qty", "order_type"];
}
