<?php

namespace Tests\Unit;

use App\Models\Location;
use Tests\TestCase;

class LocationCoordinateCastTest extends TestCase
{
    /**
     * MySQL/MariaDB returns decimal(10,7) columns as zero-padded strings
     * (e.g. "48.1091200"). The edit form round-trips coordinates through JS
     * parseFloat(), which drops trailing zeros (48.10912). Without a
     * numeric/decimal cast on Location, Eloquent's dirty-check falls back to
     * strcmp() on those two representations and reports a change that never
     * happened, which made LocationObserver evict cached routes needlessly
     * on every event/ride save.
     *
     * This is regression-tested here rather than via a Feature/HTTP test
     * because the test suite runs against sqlite (see phpunit.xml), which
     * does not reproduce MySQL's zero-padded decimal string behaviour -
     * setRawAttributes() lets us simulate that representation directly.
     *
     * isDirty() is used rather than wasChanged() because wasChanged() only
     * reflects the $changes array populated by save(); isDirty() runs the
     * same originalIsEquivalent() comparison save() relies on to build that
     * array, without requiring an actual DB round-trip in the test.
     */
    public function test_precision_difference_from_db_round_trip_is_not_a_change(): void
    {
        $location = new Location();
        $location->setRawAttributes([
            'id'        => 1,
            'latitude'  => '48.1091200',
            'longitude' => '11.5820000',
        ], true);

        $location->latitude  = 48.10912;
        $location->longitude = 11.582;

        $this->assertFalse($location->isDirty(['latitude', 'longitude']));
    }

    public function test_an_actual_coordinate_change_is_still_detected(): void
    {
        $location = new Location();
        $location->setRawAttributes([
            'id'        => 1,
            'latitude'  => '48.1091200',
            'longitude' => '11.5820000',
        ], true);

        $location->latitude = 48.1351;

        $this->assertTrue($location->isDirty('latitude'));
    }
}
