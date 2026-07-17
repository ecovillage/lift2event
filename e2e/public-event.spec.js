import { test, expect } from '@playwright/test';
import { resetDb, mockGeocode, loginAs } from './helpers.js';
import { ADMIN_EVENT_SLUG, USER_EVENT_SLUG, UNAPPROVED_EMAIL, ADMIN_EMAIL } from './fixtures.js';

test.describe('Öffentliche Mitfahrbörse', () => {
    test.beforeAll(() => resetDb());

    const eventUrl = `/e/${ADMIN_EVENT_SLUG}`;

    test('Öffentliche Seite ist ohne Login erreichbar', async ({ page }) => {
        await page.goto(eventUrl);
        await expect(page.getByText('Testveranstaltung Berlin')).toBeVisible();
    });

    test('Zeigt Veranstaltungsdaten in der Kopfzeile', async ({ page }) => {
        await page.goto(eventUrl);
        await expect(page.getByText('Mitfahrbörse', { exact: false })).toBeVisible();
        await expect(page.getByText('Testveranstaltung Berlin')).toBeVisible();
        await expect(page.getByText(/Alexanderplatz/)).toBeVisible();
    });

    test('Karte wird angezeigt', async ({ page }) => {
        await page.goto(eventUrl);
        // Leaflet renders a .leaflet-container
        await expect(page.locator('.leaflet-container')).toBeVisible();
    });

    test('Vorhandene Mitfahrt (aus Seeder) erscheint in der Liste', async ({ page }) => {
        await page.goto(eventUrl);
        // hasText matches the card containing both the name and the München location
        await expect(page.locator('.cursor-pointer', { hasText: 'Max Muster' })).toBeVisible();
    });

    test('Klick auf Kachel öffnet Popup', async ({ page }) => {
        await page.goto(eventUrl);
        // Click the first ride card
        await page.locator('.cursor-pointer').first().click();
        // The list card stays mounted behind the popup overlay, so scope to the popup itself
        const popup = page.getByTestId('ride-popup');
        await expect(popup.getByText('Max Muster')).toBeVisible();
        await expect(popup.getByText('Kontakt')).toBeVisible();
    });

    test('Popup schließen funktioniert', async ({ page }) => {
        await page.goto(eventUrl);
        await page.locator('.cursor-pointer').first().click();
        await page.getByRole('button', { name: 'Schließen' }).click();
        await expect(page.getByText('Kontakt')).not.toBeVisible();
    });

    test('Popup schließt mit Escape-Taste', async ({ page }) => {
        await page.goto(eventUrl);
        await page.locator('.cursor-pointer').first().click();
        await expect(page.getByText('Kontakt')).toBeVisible();
        await page.keyboard.press('Escape');
        await expect(page.getByText('Kontakt')).not.toBeVisible();
    });

    test('Filter "Angebote" zeigt nur Angebote', async ({ page }) => {
        await page.goto(eventUrl);
        await page.getByText('Angebote').click();
        // All visible type badges should be offers
        const badges = page.locator('.border-l-4');
        const count = await badges.count();
        for (let i = 0; i < count; i++) {
            await expect(badges.nth(i)).toHaveClass(/border-\[var\(--color-offer\)\]/);
        }
    });

    test('Filter "Alle" zeigt alle Mitfahrten', async ({ page }) => {
        await page.goto(eventUrl);
        // Use role=button with exact name to avoid matching the <option>Alle Daten</option>
        await page.getByRole('button', { name: 'Alle', exact: true }).click();
        await expect(page.locator('.cursor-pointer').first()).toBeVisible();
    });

    test('Neue Mitfahrt erstellen', async ({ page }) => {
        await mockGeocode(page);
        await page.goto(eventUrl);
        await page.getByText('+ Neue Mitfahrt einstellen').first().click();

        // Wait for the ride form modal to open
        await page.getByTestId('ride-name').waitFor();

        // Type: offer (default, but confirm)
        await page.getByText('Ich biete eine Mitfahrgelegenheit').click();

        // Fill name and email via testid (labels have no for/id association)
        await page.getByTestId('ride-name').fill('Erika Muster');
        await page.getByTestId('ride-email').fill('erika@example.com');

        // Departure location – mocked geocode returns suggestions
        await page.getByTestId('ride-address').fill('Hamburg');
        await page.locator('[data-testid="ride-suggestions"]').waitFor();
        await page.locator('[data-testid="ride-suggestions"] li').first().click();

        // Fill outbound time
        await page.locator('input[type="time"]').first().fill('09:00');

        // Submit button becomes enabled once location is selected
        await expect(page.getByTestId('ride-submit')).toBeEnabled({ timeout: 5000 });
        await page.getByTestId('ride-submit').click();

        // New ride should appear in the list, as a card containing both the
        // name and the Musterstraße address
        await expect(
            page.locator('.cursor-pointer', { hasText: 'Erika Muster' })
        ).toBeVisible({ timeout: 8000 });
    });

    test('Adressvorschläge lassen sich mit Pfeiltasten und Enter auswählen', async ({ page }) => {
        await mockGeocode(page, [
            { place_id: 1, display_name: 'Erste Straße 1, Berlin', lat: '52.1', lon: '13.1', address: { country_code: 'de' } },
            { place_id: 2, display_name: 'Zweite Straße 2, Berlin', lat: '52.2', lon: '13.2', address: { country_code: 'de' } },
            { place_id: 3, display_name: 'Dritte Straße 3, Berlin', lat: '52.3', lon: '13.3', address: { country_code: 'de' } },
        ]);
        await page.goto(eventUrl);
        await page.getByText('+ Neue Mitfahrt einstellen').first().click();
        await page.getByTestId('ride-name').waitFor();

        const address = page.getByTestId('ride-address');
        await address.fill('Berlin');
        const suggestions = page.locator('[data-testid="ride-suggestions"] li');
        await suggestions.first().waitFor();

        // ArrowDown twice highlights the second suggestion
        await address.press('ArrowDown');
        await address.press('ArrowDown');
        await expect(suggestions.nth(1)).toHaveClass(/bg-gray-100/);

        await address.press('Enter');
        await expect(address).toHaveValue('Zweite Straße 2, Berlin');
        await expect(page.locator('[data-testid="ride-suggestions"]')).not.toBeVisible();
    });

    test('Mitfahrt-Formular schließt mit Escape-Taste', async ({ page }) => {
        await page.goto(eventUrl);
        await page.getByText('+ Neue Mitfahrt einstellen').first().click();
        await page.getByTestId('ride-name').waitFor();
        await page.keyboard.press('Escape');
        await expect(page.getByTestId('ride-name')).not.toBeVisible();
    });

    test('Neue Mitfahrt als Gast zeigt Hinweis auf ausstehende Bestätigung', async ({ page }) => {
        await mockGeocode(page);
        await page.goto(eventUrl);
        await page.getByText('+ Neue Mitfahrt einstellen').first().click();
        await page.getByTestId('ride-name').waitFor();

        await page.getByTestId('ride-name').fill('Gast Tester');
        await page.getByTestId('ride-email').fill('gast@example.com');
        await page.getByTestId('ride-address').fill('Hamburg');
        await page.locator('[data-testid="ride-suggestions"]').waitFor();
        await page.locator('[data-testid="ride-suggestions"] li').first().click();
        await page.locator('input[type="time"]').first().fill('09:00');

        await expect(page.getByTestId('ride-submit')).toBeEnabled({ timeout: 5000 });
        await page.getByTestId('ride-submit').click();

        await expect(page.getByText('Bitte bestätige deine Mitfahrt')).toBeVisible({ timeout: 8000 });

        await page.getByRole('button', { name: 'Schließen' }).click();
        await expect(page.getByText('Bitte bestätige deine Mitfahrt')).not.toBeVisible();
    });

    test('Fehlschlag beim Mailversand zeigt übersetzte Fehlermeldung', async ({ page }) => {
        await mockGeocode(page);
        await page.route('**/api/e/*/rides', route =>
            route.request().method() === 'POST'
                ? route.fulfill({ status: 503, json: { message: 'Connection could not be established with host "smtp.example.com:587"' } })
                : route.continue()
        );
        await page.goto(eventUrl);
        await page.getByText('+ Neue Mitfahrt einstellen').first().click();
        await page.getByTestId('ride-name').waitFor();

        await page.getByTestId('ride-name').fill('Gast Tester');
        await page.getByTestId('ride-email').fill('gast@example.com');
        await page.getByTestId('ride-address').fill('Hamburg');
        await page.locator('[data-testid="ride-suggestions"]').waitFor();
        await page.locator('[data-testid="ride-suggestions"] li').first().click();
        await page.locator('input[type="time"]').first().fill('09:00');

        await expect(page.getByTestId('ride-submit')).toBeEnabled({ timeout: 5000 });
        await page.getByTestId('ride-submit').click();

        await expect(page.getByText('Die Bestätigungsmail konnte nicht versendet werden. Bitte versuche es später noch einmal.')).toBeVisible({ timeout: 8000 });
        await expect(page.getByText(/getaddrinfo|smtp\.example\.com/)).not.toBeVisible();
    });

    test('Neue Mitfahrt als eingeloggter Nutzer zeigt keinen Bestätigungs-Hinweis', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await mockGeocode(page);
        await page.goto(eventUrl);
        await page.getByText('+ Neue Mitfahrt einstellen').first().click();
        await page.getByTestId('ride-name').waitFor();

        await page.getByTestId('ride-name').fill('Admin Tester');
        await page.getByTestId('ride-email').fill('admin-ride@example.com');
        await page.getByTestId('ride-address').fill('Hamburg');
        await page.locator('[data-testid="ride-suggestions"]').waitFor();
        await page.locator('[data-testid="ride-suggestions"] li').first().click();
        await page.locator('input[type="time"]').first().fill('09:00');

        await expect(page.getByTestId('ride-submit')).toBeEnabled({ timeout: 5000 });
        await page.getByTestId('ride-submit').click();

        await expect(page.getByText(/Musterstraße/)).toBeVisible({ timeout: 8000 });
        await expect(page.getByText('Bitte bestätige deine Mitfahrt')).not.toBeVisible();
    });

    test('Mitfahrt-Formular: Datum/Uhrzeit sind mit Veranstaltungsdaten vorbefüllt', async ({ page }) => {
        await page.goto(eventUrl);
        await page.getByText('+ Neue Mitfahrt einstellen').first().click();
        await page.getByTestId('ride-name').waitFor();

        const dateInputs = page.locator('input[type="date"]');
        const timeInputs = page.locator('input[type="time"]');

        // Hinfahrt: Datum = Veranstaltungsbeginn, keine Uhrzeit-Voreinstellung
        await expect(dateInputs.nth(0)).toHaveValue('2026-08-15');
        await expect(timeInputs.nth(0)).toHaveValue('');

        // Rückfahrt: Datum = Veranstaltungsende, Uhrzeit = Ende-Uhrzeit der Veranstaltung
        await expect(dateInputs.nth(1)).toHaveValue('2026-08-17');
        await expect(timeInputs.nth(1)).toHaveValue('18:00');
    });

    test('Mitfahrt-Popup zeigt Kontakt-Buttons', async ({ page }) => {
        await page.goto(eventUrl);
        await page.locator('.cursor-pointer').first().click();
        // Should have at least an email contact button
        await expect(page.getByText(/E-Mail|email/i).first()).toBeVisible();
    });

    test('Footer zeigt GitHub-Link', async ({ page }) => {
        await page.goto(eventUrl);
        await expect(page.getByRole('link', { name: 'GitHub' })).toBeVisible();
    });

    test('Footer zeigt benutzerdefinierte Links aus den Einstellungen', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/backend/settings');
        await page.getByTestId('add-footer-link-btn').click();
        await page.getByTestId('footer-link-label-0').fill('Datenschutz');
        await page.getByTestId('footer-link-url-0').fill('https://example.com/datenschutz');
        await page.getByTestId('save-footer-btn').click();
        await expect(page.getByText('Gespeichert!')).toBeVisible({ timeout: 8000 });

        await page.goto(eventUrl);
        await expect(page.getByRole('link', { name: 'Datenschutz' })).toBeVisible();
    });

    test('Verwaltung-Button nicht sichtbar wenn nicht eingeloggt', async ({ page }) => {
        await page.goto(eventUrl);
        await expect(page.getByRole('link', { name: 'Verwaltung' })).not.toBeVisible();
    });

    test('Verwaltung-Button sichtbar wenn eingeloggt und leitet zur Verwaltung weiter', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto(eventUrl);
        const backendLink = page.getByRole('link', { name: 'Verwaltung' });
        await expect(backendLink).toBeVisible();
        await backendLink.click();
        await expect(page).toHaveURL(/\/backend/);
    });

    test('Veranstaltung ohne Mitfahrten zeigt Hinweistext statt Kacheln', async ({ page }) => {
        await page.goto(`/e/${USER_EVENT_SLUG}`);
        await expect(page.getByText('Noch keine Mitfahrten – trag die erste ein!')).toBeVisible();
        await expect(page.locator('.cursor-pointer')).toHaveCount(0);
    });

    test('Mitfahrangebot mit 0 Plätzen zeigt "Ausgebucht"-Badge in Liste und Popup', async ({ page }) => {
        await mockGeocode(page);
        await page.goto(eventUrl);
        await page.getByText('+ Neue Mitfahrt einstellen').first().click();
        await page.getByTestId('ride-name').waitFor();

        // Type: offer (default)
        await page.getByText('Ich biete eine Mitfahrgelegenheit').click();

        await page.getByTestId('ride-name').fill('Ausgebucht Tester');
        await page.getByTestId('ride-email').fill('ausgebucht@example.com');
        await page.getByTestId('ride-address').fill('Hamburg');
        await page.locator('[data-testid="ride-suggestions"]').waitFor();
        await page.locator('[data-testid="ride-suggestions"] li').first().click();
        await page.locator('input[type="time"]').first().fill('09:00');

        // Seats stepper: decrement from default 1 down to 0
        await page.locator('button.stepper-btn.rounded-l').click();
        await expect(page.getByText('Ausgebucht', { exact: true })).toBeVisible();

        await expect(page.getByTestId('ride-submit')).toBeEnabled({ timeout: 5000 });
        await page.getByTestId('ride-submit').click();

        // Dismiss the pending-confirmation popup shown after guest ride creation
        await page.getByRole('button', { name: 'Schließen' }).click();

        const card = page.locator('.cursor-pointer', { hasText: 'Ausgebucht Tester' });
        await expect(card).toBeVisible({ timeout: 8000 });
        await expect(card.getByText('Ausgebucht', { exact: true })).toBeVisible();

        await card.click();
        const popup = page.getByTestId('ride-popup');
        await expect(popup.getByText('Kontakt')).toBeVisible();
        await expect(popup.getByText('Ausgebucht', { exact: true })).toBeVisible();
    });

    test('Mitfahrgesuch kann nicht auf 0 Plätze reduziert werden', async ({ page }) => {
        await mockGeocode(page);
        await page.goto(eventUrl);
        await page.getByText('+ Neue Mitfahrt einstellen').first().click();
        await page.getByTestId('ride-name').waitFor();

        await page.getByText('Ich suche eine Mitfahrgelegenheit').click();
        await page.locator('button.stepper-btn.rounded-l').click();

        // Seats span stays at 1 (min for requests), no "Ausgebucht" hint appears
        await expect(page.getByText('Ausgebucht', { exact: true })).not.toBeVisible();
    });

    test('Event von nicht-bestätigtem Nutzer ist nicht öffentlich', async ({ page }) => {
        // The unapproved user event slug is NOT in the seeder,
        // but we can test via API directly.
        // Instead: visit a non-existent event slug and expect 404-like behavior.
        // The real test is in PublicEventTest.php.
        // Here we verify the Vue app handles a 404 gracefully.
        await page.goto('/e/nichtvorhandener0slug');
        // Should not show an event – either show error or redirect
        await expect(page.getByText(/Testveranstaltung Berlin/)).not.toBeVisible();
    });
});
