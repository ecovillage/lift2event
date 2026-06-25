<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(LocationObserver::class)]
class Location extends Model
{
    use HasFactory;

    protected $fillable = ['address', 'latitude', 'longitude', 'country_code'];
}
