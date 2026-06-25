<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Location;
use App\Models\Ride;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class E2eSeeder extends Seeder
{
    public const ADMIN_EMAIL      = 'admin@lift2event.test';
    public const USER_EMAIL       = 'user@lift2event.test';
    public const UNAPPROVED_EMAIL = 'unapproved@lift2event.test';
    public const PASSWORD         = 'testpassword';
    public const ADMIN_EVENT_SLUG = 'aabbccdd00001234';
    public const USER_EVENT_SLUG  = 'bbccddee00001234';

    public function run(): void
    {
        Setting::firstOrCreate([], [
            'map_center_lat' => 50.9333,
            'map_center_lng' => 10.5511,
            'map_zoom'       => 6,
        ]);

        $admin = User::create([
            'name'     => 'Admin Test',
            'email'    => self::ADMIN_EMAIL,
            'password' => bcrypt(self::PASSWORD),
            'is_admin' => true,
            'approved' => true,
        ]);

        $user = User::create([
            'name'     => 'Regular User',
            'email'    => self::USER_EMAIL,
            'password' => bcrypt(self::PASSWORD),
            'is_admin' => false,
            'approved' => true,
        ]);

        User::create([
            'name'     => 'Unapproved User',
            'email'    => self::UNAPPROVED_EMAIL,
            'password' => bcrypt(self::PASSWORD),
            'is_admin' => false,
            'approved' => false,
        ]);

        // Admin's event (approved creator → publicly visible)
        $adminLocation = Location::create([
            'address'      => 'Alexanderplatz 1, 10178 Berlin, Deutschland',
            'latitude'     => 52.5219,
            'longitude'    => 13.4132,
            'country_code' => 'DE',
        ]);

        $adminEvent = Event::create([
            'name'          => 'Testveranstaltung Berlin',
            'slug'          => self::ADMIN_EVENT_SLUG,
            'start_at'      => '2026-08-15 10:00:00',
            'end_at'        => '2026-08-17 18:00:00',
            'location_id'   => $adminLocation->id,
            'created_by_id' => $admin->id,
        ]);

        // Pre-seeded ride so the public page always has something to show
        $rideLocation = Location::create([
            'address'      => 'Hauptbahnhof, 80335 München, Deutschland',
            'latitude'     => 48.1401,
            'longitude'    => 11.5600,
            'country_code' => 'DE',
        ]);

        Ride::create([
            'event_id'        => $adminEvent->id,
            'location_id'     => $rideLocation->id,
            'type'            => 'offer',
            'direction'       => 'both-ways',
            'outbound_at'     => '2026-08-15 08:00:00',
            'return_at'       => '2026-08-17 19:00:00',
            'seats'           => 3,
            'name'            => 'Max Muster',
            'email'           => 'max@example.com',
            'contact_methods' => ['email'],
            'edit_token'      => bin2hex(random_bytes(32)),
            'confirmed_at'    => now(),
        ]);

        // Regular user's event (approved creator → publicly visible)
        $userLocation = Location::create([
            'address'      => 'Marktplatz 1, 70173 Stuttgart, Deutschland',
            'latitude'     => 48.7758,
            'longitude'    => 9.1829,
            'country_code' => 'DE',
        ]);

        Event::create([
            'name'          => 'Testveranstaltung Stuttgart',
            'slug'          => self::USER_EVENT_SLUG,
            'start_at'      => '2026-09-01 09:00:00',
            'end_at'        => '2026-09-03 17:00:00',
            'location_id'   => $userLocation->id,
            'created_by_id' => $user->id,
        ]);
    }
}
