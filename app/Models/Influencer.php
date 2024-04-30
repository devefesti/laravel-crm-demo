<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Influencer extends Model
{
    use HasFactory;
    protected $table = 'influencers';
    protected $primaryKey = 'id';
    protected $fillable = ['nome', 'cognome', 'email', 'telefono', 'materiale', 'descrizione', 'indirizzo', 'created_at', 'updated_at'];
}