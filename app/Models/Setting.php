<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['map_center_lat', 'map_center_lng', 'map_zoom', 'footer_links'];

    protected function casts(): array
    {
        return [
            'map_center_lat' => 'float',
            'map_center_lng' => 'float',
            'map_zoom'       => 'integer',
            'footer_links'   => 'array',
        ];
    }

    public static function instance(): self
    {
        return static::firstOrCreate([], [
            'map_center_lat' => 50.9333,
            'map_center_lng' => 10.5511,
            'map_zoom'       => 6,
        ]);
    }
}
