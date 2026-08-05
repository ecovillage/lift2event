<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(LocationObserver::class)]
class Location extends Model
{
    use HasFactory;

    protected $fillable = ['address', 'latitude', 'longitude', 'country_code', 'street', 'house_number', 'postal_code', 'city', 'display_name'];

    // Without these casts, Eloquent's dirty-check falls back to a raw string
    // comparison of the decimal(10,7) column value. Frontend round-trips via
    // parseFloat() drop trailing zeros (e.g. "48.1091200" -> 48.10912), which
    // made wasChanged(['latitude', 'longitude']) report a change even when the
    // coordinates were identical, wiping the location_routes cache for no reason.
    protected $casts = [
        'latitude'  => 'decimal:7',
        'longitude' => 'decimal:7',
    ];
}
