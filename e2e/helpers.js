import { execSync } from 'child_process';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';
import { PASSWORD } from './fixtures.js';

const PROJECT = resolve(dirname(fileURLToPath(import.meta.url)), '..');

export function resetDb() {
    execSync(
        'docker compose -f docker-compose.yml -f docker-compose.e2e.yml exec -T app_e2e bash -c "php artisan migrate:fresh && php artisan db:seed --class=E2eSeeder"',
        { cwd: PROJECT, stdio: 'pipe', timeout: 90_000 }
    );
}

export async function loginAs(page, email, password = PASSWORD) {
    await page.goto('/login');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.getByRole('button', { name: 'Anmelden' }).click();
    await page.waitForURL('**/backend/**');
}

/** Formats a date `offsetDays` from now as a MySQL DATETIME string (UTC). */
export function mysqlDateTime(offsetDays) {
    const d = new Date(Date.now() + offsetDays * 24 * 60 * 60 * 1000);
    return d.toISOString().slice(0, 19).replace('T', ' ');
}

/** Overwrites an event's end_at (e.g. to move it into/past the retention window). */
export function setEventEndAt(slug, mysqlDateTimeString) {
    execSync(
        `docker compose -f docker-compose.yml -f docker-compose.e2e.yml exec -T app_e2e php artisan tinker --execute="\\App\\Models\\Event::where('slug','${slug}')->update(['end_at' => '${mysqlDateTimeString}']);"`,
        { cwd: PROJECT, stdio: 'pipe', timeout: 30_000 }
    );
}

/** Mock the geocoding endpoint for the duration of this page context. */
export async function mockGeocode(page, results = null) {
    const defaults = [{
        place_id:     1,
        display_name: 'Musterstraße 1, 12345 Musterstadt, Deutschland',
        lat:          '52.5200',
        lon:          '13.4050',
        address:      { country_code: 'de' },
    }];
    await page.route('**/api/geocode/search**', route =>
        route.fulfill({ json: results ?? defaults })
    );
}
