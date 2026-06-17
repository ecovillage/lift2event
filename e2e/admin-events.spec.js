import { test, expect } from '@playwright/test';
import { resetDb, loginAs, mockGeocode } from './helpers.js';
import { ADMIN_EMAIL, USER_EMAIL, ADMIN_EVENT_SLUG } from './fixtures.js';

test.describe('Admin – Veranstaltungen', () => {
    test.beforeAll(() => resetDb());

    test('Admin sieht alle Veranstaltungen aller Nutzer', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/events');
        await expect(page.getByText('Testveranstaltung Berlin')).toBeVisible();
        await expect(page.getByText('Testveranstaltung Stuttgart')).toBeVisible();
    });

    test('Normaler Nutzer sieht nur eigene Veranstaltungen', async ({ page }) => {
        await loginAs(page, USER_EMAIL);
        await page.goto('/admin/events');
        await expect(page.getByText('Testveranstaltung Stuttgart')).toBeVisible();
        await expect(page.getByText('Testveranstaltung Berlin')).not.toBeVisible();
    });

    test('Admin sieht Mitfahrten-Anzahl in der Tabelle', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/events');
        // Admin event has 1 pre-seeded ride – use row-scoped cell to avoid matching "1" elsewhere
        const row = page.getByRole('row').filter({ hasText: 'Testveranstaltung Berlin' });
        await expect(row.getByRole('cell', { name: '1', exact: true })).toBeVisible();
    });

    test('Neue Veranstaltung anlegen', async ({ page }) => {
        await mockGeocode(page);
        await loginAs(page, ADMIN_EMAIL);
        await page.getByRole('link', { name: /Neue Veranstaltung/i }).click();
        await expect(page).toHaveURL(/\/admin\/events\/create/);

        // Fill the form
        await page.locator('input[type="text"]').first().fill('E2E-Test-Event');
        await page.locator('input[type="datetime-local"]').first().fill('2026-10-01T10:00');
        await page.locator('input[type="datetime-local"]').last().fill('2026-10-03T18:00');

        // Trigger address search and select suggestion
        const addressInput = page.locator('input[placeholder*="Adresse"]');
        await addressInput.fill('Muster');
        await page.waitForSelector('ul li');
        await page.locator('ul li').first().click();

        // Submit
        await page.getByRole('button', { name: 'Speichern' }).click();
        await expect(page).toHaveURL(/\/admin\/events$/);
        await expect(page.getByText('E2E-Test-Event')).toBeVisible();
    });

    test('Veranstaltung bearbeiten – Name ändern', async ({ page }) => {
        await mockGeocode(page);
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/events');
        // Click the admin event row
        await page.getByText('Testveranstaltung Berlin').click();
        await expect(page).toHaveURL(/\/admin\/events\/\d+\/edit/);

        const nameInput = page.locator('input[type="text"]').first();
        // Wait for async event data to populate the form before editing
        await expect(nameInput).not.toHaveValue('', { timeout: 10000 });
        await nameInput.clear();
        await nameInput.fill('Testveranstaltung Berlin (geändert)');

        // Wait for location to load from API before Speichern becomes enabled
        const saveBtn = page.getByRole('button', { name: 'Speichern' });
        await expect(saveBtn).toBeEnabled({ timeout: 10000 });
        await saveBtn.click();

        await expect(page).toHaveURL(/\/admin\/events$/);
        await expect(page.getByText('Testveranstaltung Berlin (geändert)')).toBeVisible();
    });

    test('Bearbeiten-Formular zeigt öffentlichen Link', async ({ page }) => {
        await mockGeocode(page);
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/events');
        await page.getByText(/Testveranstaltung Berlin/).click();
        // Public link is in a readonly input (not visible text) – check its value
        const linkInput = page.locator('input[readonly]');
        await expect(linkInput).toHaveValue(/\/e\//, { timeout: 10000 });
        await expect(page.getByRole('button', { name: 'Kopieren' })).toBeVisible();
    });

    test('Admin kann nicht-eigene Veranstaltung bearbeiten', async ({ page }) => {
        await mockGeocode(page);
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/events');
        await page.getByText('Testveranstaltung Stuttgart').click();
        await expect(page).toHaveURL(/\/admin\/events\/\d+\/edit/);
        // Form should be shown (not an error)
        await expect(page.locator('input[type="text"]').first()).toBeVisible();
    });
});
