<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['map_center_lat', 'map_center_lng', 'map_zoom', 'footer_links', 'ride_data_retention_days', 'organisation_name', 'rides_cleanup_next_due_at'];

    protected function casts(): array
    {
        return [
            'map_center_lat'            => 'float',
            'map_center_lng'            => 'float',
            'map_zoom'                  => 'integer',
            'footer_links'              => 'array',
            'ride_data_retention_days'  => 'integer',
            'rides_cleanup_next_due_at' => 'datetime',
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

    public static function organisationName(): string
    {
        $stored = static::instance()->organisation_name;
        if ($stored !== null && $stored !== '') {
            return $stored;
        }

        $host = (string) (parse_url(config('app.url'), PHP_URL_HOST) ?? '');
        $host = (string) preg_replace('/^www\./i', '', $host);
        $name = (string) preg_replace('/\.[^.]+$/', '', $host);
        $name = ucwords(str_replace('-', ' ', $name));

        return $name !== '' ? $name : 'Lift2Event';
    }
}
