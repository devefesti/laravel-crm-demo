<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatorOrders extends Model
{
    use HasFactory;
    protected $table = 'orders_operators';
    protected $fillable = ['id','order_id', 'operator', 'date', 'report'];
}
