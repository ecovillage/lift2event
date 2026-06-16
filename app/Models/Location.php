<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['address', 'latitude', 'longitude', 'country_code'];
}
