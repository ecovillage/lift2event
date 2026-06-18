<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Location;
use App\Models\Ride;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedDemoData extends Command
{
    protected $signature = 'app:seed-demo-data';

    protected $description = 'Legt Demo-Daten an: Admin, zwei Nutzer, Veranstaltungen und Mitfahrten';

    private const CONTACT_METHODS = ['email', 'phone', 'signal', 'telegram', 'whatsapp', 'sms', 'call'];

    public function handle(): void
    {
        Setting::instance();

        $admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin@lift2event.test',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'approved' => true,
        ]);

        $user1 = User::create([
            'name'     => 'Anna Müller',
            'email'    => 'anna@lift2event.test',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'approved' => true,
        ]);

        $user2 = User::create([
            'name'     => 'Ben Schmidt',
            'email'    => 'ben@lift2event.test',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'approved' => true,
        ]);

        $this->info("Nutzer angelegt: {$admin->email}, {$user1->email}, {$user2->email}");

        $locationBeetzendorf = Location::create([
            'address'      => 'Beetzendorf, Sachsen-Anhalt',
            'latitude'     => 52.6951,
            'longitude'    => 11.0906,
            'country_code' => 'DE',
        ]);

        $locationBerlin = Location::create([
            'address'      => 'Berlin, Deutschland',
            'latitude'     => 52.5200,
            'longitude'    => 13.4050,
            'country_code' => 'DE',
        ]);

        $locationParis = Location::create([
            'address'      => 'Paris, Frankreich',
            'latitude'     => 48.8566,
            'longitude'    => 2.3522,
            'country_code' => 'FR',
        ]);

        // User 1: 1 Veranstaltung in Beetzendorf mit 1 Mitfahrt
        $eventBeetzendorf = Event::create([
            'name'          => 'Treffen Beetzendorf',
            'start_at'      => now()->addMonths(2)->setTime(10, 0),
            'end_at'        => now()->addMonths(2)->addDays(2)->setTime(18, 0),
            'location_id'   => $locationBeetzendorf->id,
            'created_by_id' => $user1->id,
        ]);

        $rideLocation1 = Location::create([
            'address'      => 'Magdeburg, Sachsen-Anhalt',
            'latitude'     => 52.1205,
            'longitude'    => 11.6276,
            'country_code' => 'DE',
        ]);

        Ride::create([
            'event_id'       => $eventBeetzendorf->id,
            'location_id'    => $rideLocation1->id,
            'type'           => 'offer',
            'direction'      => 'both-ways',
            'outbound_at'    => now()->addMonths(2)->setTime(9, 0),
            'return_at'      => now()->addMonths(2)->addDays(2)->setTime(19, 0),
            'seats'          => 3,
            'name'           => 'Klaus Werner',
            'email'          => 'klaus@example.com',
            'contact_methods' => $this->randomContactMethods(),
        ]);

        $this->info("Veranstaltung Beetzendorf mit 1 Mitfahrt angelegt.");

        // User 2: 2 Veranstaltungen – Berlin (7 Gesuche + 8 Angebote) und Paris (leer)
        $eventBerlin = Event::create([
            'name'          => 'Berliner Konzert',
            'start_at'      => now()->addMonths(3)->setTime(18, 0),
            'end_at'        => now()->addMonths(3)->setTime(23, 0),
            'location_id'   => $locationBerlin->id,
            'created_by_id' => $user2->id,
        ]);

        $this->seedRides($eventBerlin->id, 'request', 7);
        $this->seedRides($eventBerlin->id, 'offer', 8);

        $this->info("Veranstaltung Berlin mit 7 Gesuchen und 8 Angeboten angelegt.");

        $eventParis = Event::create([
            'name'          => 'Paris Festival',
            'start_at'      => now()->addMonths(4)->setTime(14, 0),
            'end_at'        => now()->addMonths(4)->addDays(3)->setTime(22, 0),
            'location_id'   => $locationParis->id,
            'created_by_id' => $user2->id,
        ]);

        $this->info("Veranstaltung Paris angelegt.");

        $this->info('Demo-Daten erfolgreich angelegt.');
        $this->table(
            ['Rolle', 'Name', 'E-Mail', 'Passwort'],
            [
                ['Admin', $admin->name, $admin->email, 'password'],
                ['Nutzer', $user1->name, $user1->email, 'password'],
                ['Nutzer', $user2->name, $user2->email, 'password'],
            ]
        );
    }

    private function seedRides(int $eventId, string $type, int $count): void
    {
        $startLocations = [
            ['Wolfsburg', 52.4227, 10.7865, 'DE'],
            ['Hamburg', 53.5753, 10.0153, 'DE'],
            ['München', 48.1351, 11.5820, 'DE'],
            ['Köln', 50.9333, 6.9500, 'DE'],
            ['Frankfurt', 50.1109, 8.6821, 'DE'],
            ['Stuttgart', 48.7758, 9.1829, 'DE'],
            ['Dresden', 51.0504, 13.7373, 'DE'],
            ['Leipzig', 51.3397, 12.3731, 'DE'],
            ['Hannover', 52.3759, 9.7320, 'DE'],
            ['Düsseldorf', 51.2217, 6.7762, 'DE'],
            ['Breslau', 51.1079, 17.0385, 'PL'],
            ['Wien', 48.2082, 16.3738, 'AT'],
            ['Prag', 50.0755, 14.4378, 'CZ'],
            ['Amsterdam', 52.3676, 4.9041, 'NL'],
            ['Brüssel', 50.8503, 4.3517, 'BE'],
        ];

        $names = [
            'Marie König', 'Thomas Bauer', 'Julia Hoffmann', 'Stefan Richter',
            'Laura Schäfer', 'Markus Koch', 'Sandra Wagner', 'Michael Becker',
            'Sabine Zimmermann', 'Andreas Schulz', 'Petra Krause', 'Daniel Wolf',
            'Monika Neumann', 'Christian Braun', 'Claudia Hartmann',
        ];

        $directions = ['both-ways', 'outbound-only', 'return-only'];

        for ($i = 0; $i < $count; $i++) {
            [$city, $lat, $lng, $cc] = $startLocations[$i % count($startLocations)];

            $location = Location::create([
                'address'      => $city,
                'latitude'     => $lat + (rand(-100, 100) / 10000),
                'longitude'    => $lng + (rand(-100, 100) / 10000),
                'country_code' => $cc,
            ]);

            Ride::create([
                'event_id'        => $eventId,
                'location_id'     => $location->id,
                'type'            => $type,
                'direction'       => $directions[array_rand($directions)],
                'outbound_at'     => now()->addMonths(3)->setTime(rand(6, 14), 0),
                'return_at'       => now()->addMonths(3)->setTime(rand(18, 23), 0),
                'seats'           => rand(1, 5),
                'name'            => $names[$i % count($names)],
                'email'           => 'user' . ($i + 1) . '@example.com',
                'contact_methods' => $this->randomContactMethods(),
            ]);
        }
    }

    private function randomContactMethods(): array
    {
        $methods = self::CONTACT_METHODS;
        shuffle($methods);
        $count = rand(1, 3);
        return array_slice($methods, 0, $count);
    }
}
