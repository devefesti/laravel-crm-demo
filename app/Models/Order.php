<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $table = 'orders';
    protected $fillable = ['id','order_id', 'entity_id','status','email','firstname','lastname','totale','street','city','post_code','state','country', 'comment', 'order_date'];

}
