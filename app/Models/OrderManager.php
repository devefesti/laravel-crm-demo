<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderManager extends Model
{
    use HasFactory;



    public static function getOrderStatus($id)
    {
      
        return Order::where('order_id', $id)->value('status');

    }
}
