<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrderProds;

class OrderModel extends Model
{ 
    use HasFactory;
    protected $table = 'orders';
    protected $fillable = ["order_id", 'entity_id',"status","email","firstname","lastname","totale","street","city","post_code","state","country","comment", "order_date"];


    public function products(): HasMany
    {
        return $this->hasMany(OrderProds::class, 'order_id', 'order_id');
    }
}
