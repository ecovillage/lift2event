import { test, expect } from '@playwright/test';
import { resetDb, loginAs, mockGeocode } from './helpers.js';
import { ADMIN_EMAIL, USER_EMAIL, ADMIN_EVENT_SLUG } from './fixtures.js';

test.describe('Admin – Veranstaltungen', () => {
    test.beforeAll(() => resetDb());

    test('Admin sieht alle Veranstaltungen aller Nutzer', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/backend/events');
        await expect(page.getByText('Testveranstaltung Berlin')).toBeVisible();
        await expect(page.getByText('Testveranstaltung Stuttgart')).toBeVisible();
    });

    test('Normaler Nutzer sieht nur eigene Veranstaltungen', async ({ page }) => {
        await loginAs(page, USER_EMAIL);
        await page.goto('/backend/events');
        await expect(page.getByText('Testveranstaltung Stuttgart')).toBeVisible();
        await expect(page.getByText('Testveranstaltung Berlin')).not.toBeVisible();
    });

    test('Admin sieht Mitfahrten-Anzahl in der Tabelle', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/backend/events');
        // Admin event has 1 pre-seeded ride – use row-scoped cell to avoid matching "1" elsewhere
        const row = page.getByRole('row').filter({ hasText: 'Testveranstaltung Berlin' });
        await expect(row.getByRole('cell', { name: '1', exact: true })).toBeVisible();
    });

    test('Neue Veranstaltung anlegen', async ({ page }) => {
        await mockGeocode(page);
        await loginAs(page, ADMIN_EMAIL);
        await page.getByRole('link', { name: /Neue Veranstaltung/i }).click();
        await expect(page).toHaveURL(/\/backend\/events\/create/);

        // Fill the form
        await page.locator('input[type="text"]').first().fill('E2E-Test-Event');
        await page.locator('input[type="date"]').first().fill('2026-10-01');
        await page.locator('input[type="time"]').first().fill('10:00');
        await page.locator('input[type="date"]').last().fill('2026-10-03');
        await page.locator('input[type="time"]').last().fill('18:00');

        // Trigger address search and select suggestion
        const addressInput = page.locator('input[placeholder*="Adresse"]');
        await addressInput.fill('Muster');
        await page.waitForSelector('ul li');
        await page.locator('ul li').first().click();

        // "Ort wird angezeigt als" field must appear and be pre-filled
        const displayNameInput = page.getByLabel('Ort wird angezeigt als');
        await expect(displayNameInput).toBeVisible();
        await expect(displayNameInput).not.toHaveValue('');

        // Submit – a modal appears with the public link, close it to reach the list
        await page.getByRole('button', { name: 'Speichern' }).click();
        await expect(page.getByRole('heading', { name: /Veranstaltung angelegt/i })).toBeVisible();
        await page.getByRole('button', { name: /Schlie/i }).click();
        await expect(page).toHaveURL(/\/backend\/events$/);
        await expect(page.getByText('E2E-Test-Event')).toBeVisible();
    });

    test('Anzeigename-Feld erscheint erst nach Ortsauswahl und lässt sich überschreiben', async ({ page }) => {
        await mockGeocode(page);
        await loginAs(page, ADMIN_EMAIL);
        await page.getByRole('link', { name: /Neue Veranstaltung/i }).click();

        // Field must not be visible before a location is chosen
        await expect(page.getByLabel('Ort wird angezeigt als')).not.toBeVisible();

        // Select a location
        const addressInput = page.locator('input[placeholder*="Adresse"]');
        await addressInput.fill('Muster');
        await page.waitForSelector('ul li');
        await page.locator('ul li').first().click();

        // Field must appear and be pre-filled with the formatted location string
        const displayNameInput = page.getByLabel('Ort wird angezeigt als');
        await expect(displayNameInput).toBeVisible();
        await expect(displayNameInput).toHaveValue('Musterstraße 1, 12345 Musterstadt, Deutschland');

        // User can overwrite the value
        await displayNameInput.fill('Musterstadt (Festsaal)');
        await expect(displayNameInput).toHaveValue('Musterstadt (Festsaal)');
    });

    test('Veranstaltung bearbeiten – Name ändern', async ({ page }) => {
        await mockGeocode(page);
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/backend/events');
        // Click the admin event row
        await page.getByText('Testveranstaltung Berlin').click();
        await expect(page).toHaveURL(/\/backend\/events\/\d+\/edit/);

        const nameInput = page.locator('input[type="text"]').first();
        // Wait for async event data to populate the form before editing
        await expect(nameInput).not.toHaveValue('', { timeout: 10000 });
        await nameInput.clear();
        await nameInput.fill('Testveranstaltung Berlin (geändert)');

        // Wait for location to load from API before Speichern becomes enabled
        const saveBtn = page.getByRole('button', { name: 'Speichern' });
        await expect(saveBtn).toBeEnabled({ timeout: 10000 });
        await saveBtn.click();

        await expect(page).toHaveURL(/\/backend\/events$/);
        await expect(page.getByText('Testveranstaltung Berlin (geändert)')).toBeVisible();
    });

    test('Bearbeiten-Formular zeigt öffentlichen Link', async ({ page }) => {
        await mockGeocode(page);
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/backend/events');
        await page.getByText(/Testveranstaltung Berlin/).click();
        // Public link is shown as a clickable link
        const publicLink = page.locator('a[href*="/events/"]').first();
        await expect(publicLink).toBeVisible({ timeout: 10000 });
        await expect(page.getByRole('button', { name: 'Kopieren' })).toBeVisible();
    });

    test('Bearbeiten-Seite zeigt Mitfahrten-Kacheln des Events', async ({ page }) => {
        await mockGeocode(page);
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/backend/events');
        await page.getByText('Testveranstaltung Berlin').click();
        await expect(page).toHaveURL(/\/backend\/events\/\d+\/edit/);

        // Pre-seeded ride tile (same RideCard as on the public page)
        await expect(page.getByText('Hauptbahnhof, 80335 München')).toBeVisible();
        await expect(page.getByTestId('ride-tiles').getByText('Angebot')).toBeVisible();

        // Clicking the tile opens the detail popup with contact info.
        // The tile stays mounted behind the popup overlay, so scope to the popup itself.
        await page.getByText('Hauptbahnhof, 80335 München').click();
        await expect(page.getByTestId('ride-popup').getByText('Max Muster')).toBeVisible();
    });

    test('Bearbeiten-Karte zeichnet die Route der Mitfahrt', async ({ page }) => {
        await mockGeocode(page);
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/backend/events');
        await page.getByText('Testveranstaltung Berlin').click();
        await expect(page).toHaveURL(/\/backend\/events\/\d+\/edit/);

        // Pre-seeded offer ride renders as a violet pin (circle marker) on the map
        const pin = page.locator('path.leaflet-interactive[fill="#7c3aed"]');
        await expect(pin).toHaveCount(1);

        // Clicking the pin opens the same detail popup as the ride tile
        await pin.click();
        await expect(page.getByTestId('ride-popup').getByText('Max Muster')).toBeVisible();
    });

    test('Neu-Anlegen-Seite zeigt keine Mitfahrten-Kacheln', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.getByRole('link', { name: /Neue Veranstaltung/i }).click();
        await expect(page).toHaveURL(/\/backend\/events\/create/);
        await expect(page.getByTestId('ride-tiles')).not.toBeVisible();
    });

    test('Admin kann nicht-eigene Veranstaltung bearbeiten', async ({ page }) => {
        await mockGeocode(page);
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/backend/events');
        await page.getByText('Testveranstaltung Stuttgart').click();
        await expect(page).toHaveURL(/\/backend\/events\/\d+\/edit/);
        // Form should be shown (not an error)
        await expect(page.locator('input[type="text"]').first()).toBeVisible();
    });

    // These two tests mutate / remove the only pre-seeded ride – keep them last
    // so earlier tests in this file still see its original state.

    test('Admin kann Mitfahrt über das Bearbeiten-Icon auf der Kachel ändern (ohne Token)', async ({ page }) => {
        await mockGeocode(page, [{
            place_id:     2,
            display_name: 'Neue Teststraße 5, 99999 Teststadt, Deutschland',
            lat:          '51.0',
            lon:          '11.0',
            address:      { country_code: 'de' },
        }]);
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/backend/events');
        await page.getByText('Testveranstaltung Berlin').click();
        await expect(page).toHaveURL(/\/backend\/events\/\d+\/edit/);
        await expect(page.getByText('Hauptbahnhof, 80335 München')).toBeVisible();

        // Edit icon on the tile opens the ride form directly, no edit_token needed
        await page.locator('[data-testid^="ride-edit-"]').click();
        const addressInput = page.getByTestId('ride-address');
        await addressInput.fill('Teststadt');
        await page.waitForSelector('[data-testid="ride-suggestions"] li');
        await page.locator('[data-testid="ride-suggestions"] li').first().click();
        await page.getByTestId('ride-submit').click();

        await expect(page.getByText('Neue Teststraße 5, 99999 Teststadt')).toBeVisible();
        await expect(page.getByText('Hauptbahnhof, 80335 München')).not.toBeVisible();
    });

    test('Admin kann Mitfahrt über das Löschen-Icon auf der Kachel entfernen (ohne Token)', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/backend/events');
        await page.getByText('Testveranstaltung Berlin').click();
        await expect(page).toHaveURL(/\/backend\/events\/\d+\/edit/);
        await expect(page.locator('[data-testid^="ride-delete-"]')).toHaveCount(1);

        page.once('dialog', dialog => dialog.accept());
        await page.locator('[data-testid^="ride-delete-"]').click();

        await expect(page.locator('[data-testid^="ride-delete-"]')).toHaveCount(0);
    });
});
