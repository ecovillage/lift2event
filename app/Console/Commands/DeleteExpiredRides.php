<?php

namespace App\Console\Commands;

use App\Services\RideDataRetentionCleaner;
use Illuminate\Console\Command;

class DeleteExpiredRides extends Command
{
    protected $signature = 'app:delete-expired-rides {--dry-run : Nur anzeigen, was gelöscht würde, ohne tatsächlich zu löschen}';

    protected $description = 'Löscht Veranstaltungen (und deren Mitfahrten) nach Ablauf der konfigurierten Aufbewahrungsfrist';

    public function handle(RideDataRetentionCleaner $cleaner): void
    {
        $result = $cleaner->run(dryRun: $this->option('dry-run'));

        if ($this->option('dry-run')) {
            $this->info("Würde {$result['events']} Veranstaltung(en) mit {$result['rides']} Mitfahrt(en) löschen.");

            return;
        }

        $this->info("{$result['events']} Veranstaltung(en) mit {$result['rides']} Mitfahrt(en) gelöscht.");
    }
}
