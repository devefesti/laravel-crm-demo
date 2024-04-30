<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportOrder extends Model
{ 
    use HasFactory;
    protected $table = 'report_orders';
    protected $fillable = ['order_id', 'report'];
}