import { test, expect } from '@playwright/test';
import { resetDb, loginAs } from './helpers.js';
import { ADMIN_EMAIL, USER_EMAIL } from './fixtures.js';

test.describe('Admin – Einstellungen', () => {
    test.beforeAll(() => resetDb());

    test('Normaler Nutzer kann /admin/settings nicht aufrufen', async ({ page }) => {
        await loginAs(page, USER_EMAIL);
        await page.goto('/admin/settings');
        await expect(page).not.toHaveURL(/\/admin\/settings/);
    });

    test('Admin sieht Einstellungs-Seite', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/settings');
        await expect(page.getByRole('heading', { name: 'Einstellungen' })).toBeVisible();
        await expect(page.locator('[data-testid="settings-map"]')).toBeVisible();
    });

    test('Karte wird auf der Einstellungs-Seite angezeigt', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/settings');
        await expect(page.locator('.leaflet-container')).toBeVisible();
    });

    test('Karten-Ansicht kann gespeichert werden', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/settings');
        await page.locator('[data-testid="settings-map"]').waitFor({ state: 'visible' });
        await expect(page.locator('.leaflet-container')).toBeVisible();

        await page.getByTestId('save-map-btn').click();
        await expect(page.getByText('Gespeichert!')).toBeVisible({ timeout: 8000 });
    });

    test('Footer-Link kann hinzugefügt und gespeichert werden', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/settings');

        await page.getByTestId('add-footer-link-btn').click();
        await page.getByTestId('footer-link-label-0').fill('Impressum');
        await page.getByTestId('footer-link-url-0').fill('https://example.com/impressum');

        await page.getByTestId('save-footer-btn').click();
        await expect(page.getByText('Gespeichert!').last()).toBeVisible({ timeout: 8000 });
    });

    test('Footer-Link bleibt nach Seiten-Reload erhalten', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);

        // Wait for the settings API response so footer links are rendered before we inspect them
        await Promise.all([
            page.waitForResponse(resp =>
                resp.url().includes('/api/settings') && resp.request().method() === 'GET'
            ),
            page.goto('/admin/settings'),
        ]);

        // Add a link if none exist yet (previous test may have saved one already)
        const existingLabel = page.getByTestId('footer-link-label-0');
        if (!(await existingLabel.isVisible())) {
            await page.getByTestId('add-footer-link-btn').click();
        }
        await page.getByTestId('footer-link-label-0').fill('Test Link');
        await page.getByTestId('footer-link-url-0').fill('https://example.com/test');
        await page.getByTestId('save-footer-btn').click();
        await expect(page.getByText('Gespeichert!')).toBeVisible({ timeout: 8000 });

        // Reload and wait for API again before checking
        await Promise.all([
            page.waitForResponse(resp =>
                resp.url().includes('/api/settings') && resp.request().method() === 'GET'
            ),
            page.goto('/admin/settings'),
        ]);
        await expect(page.getByTestId('footer-link-label-0')).toHaveValue('Test Link');
    });
});
