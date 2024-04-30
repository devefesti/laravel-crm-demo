<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfluencerOrder extends Model
{
    use HasFactory;
    protected $table = 'orders_influencers';
    protected $primaryKey = 'id';
    protected $fillable = ['nome', 'cognome', 'email', 'stato', 'data', 'totale', 'nota'];
}