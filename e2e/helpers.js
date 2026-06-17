import { execSync } from 'child_process';
import { PASSWORD } from './fixtures.js';

const PROJECT = '/home/martin/werkbank/lift2event';

export function resetDb() {
    execSync(
        'docker compose exec -T app bash -c "php artisan migrate:fresh && php artisan db:seed --class=E2eSeeder"',
        { cwd: PROJECT, stdio: 'pipe', timeout: 90_000 }
    );
}

export async function loginAs(page, email, password = PASSWORD) {
    await page.goto('/login');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.getByRole('button', { name: 'Anmelden' }).click();
    await page.waitForURL('**/admin/**');
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
