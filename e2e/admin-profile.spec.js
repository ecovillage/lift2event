import { test, expect } from '@playwright/test';
import { resetDb, loginAs } from './helpers.js';
import { ADMIN_EMAIL, USER_EMAIL, PASSWORD } from './fixtures.js';

test.describe('Admin – Profil', () => {
    test.beforeAll(() => resetDb());

    test('Profil-Seite zeigt aktuellen Namen', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/profile');
        await expect(page.getByTestId('profile-name')).toHaveValue('Admin Test');
    });

    test('Profil-Seite zeigt aktuelle E-Mail', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/profile');
        await expect(page.getByTestId('profile-email')).toHaveValue(ADMIN_EMAIL);
    });

    test('Name kann geändert werden', async ({ page }) => {
        await loginAs(page, USER_EMAIL);
        await page.goto('/admin/profile');

        await page.getByTestId('profile-name').clear();
        await page.getByTestId('profile-name').fill('Geänderter Name');
        await page.getByTestId('save-name-btn').click();

        await expect(page.getByTestId('profile-feedback')).toContainText('Gespeichert');
    });

    test('Sprache kann auf Englisch umgestellt werden', async ({ page }) => {
        await loginAs(page, USER_EMAIL);
        await page.goto('/admin/profile');

        await page.getByTestId('profile-language').selectOption('en');
        await page.getByTestId('save-language-btn').click();

        // Feedback shows in the new language (English)
        await expect(page.getByTestId('profile-feedback')).toContainText(/Saved|Gespeichert/);
        // Nav switches to English
        await expect(page.getByRole('link', { name: 'My Events' })).toBeVisible({ timeout: 5000 });
    });

    test('Passwort kann mit korrektem alten Passwort geändert werden', async ({ page }) => {
        await loginAs(page, USER_EMAIL);
        await page.goto('/admin/profile');

        await page.getByTestId('profile-current-password').fill(PASSWORD);
        await page.getByTestId('profile-new-password').fill('neuespasswort123');
        await page.getByTestId('profile-password-confirm').fill('neuespasswort123');
        await page.getByTestId('save-password-btn').click();

        await expect(page.getByTestId('profile-feedback')).toContainText('Gespeichert');
    });

    test('Passwort-Änderung mit falschem alten Passwort schlägt fehl', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/profile');

        await page.getByTestId('profile-current-password').fill('falschaspasswort');
        await page.getByTestId('profile-new-password').fill('neuespasswort123');
        await page.getByTestId('profile-password-confirm').fill('neuespasswort123');
        await page.getByTestId('save-password-btn').click();

        await expect(page.getByTestId('profile-feedback')).toContainText(/falsch|Passwort/i);
    });

    test('E-Mail kann mit korrektem Passwort geändert werden', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/profile');

        await page.getByTestId('profile-email').clear();
        await page.getByTestId('profile-email').fill('admin-neu@lift2event.test');
        await page.getByTestId('profile-cpw-email').fill(PASSWORD);
        await page.getByTestId('save-email-btn').click();

        await expect(page.getByTestId('profile-feedback')).toContainText('Gespeichert');
    });
});
