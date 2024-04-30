<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlsOrders extends Model
{
    use HasFactory;
    protected $table = 'gls_orders';
    protected $primaryKey = 'id';
    protected $fillable = ['order_id', 'entity_id'];
}