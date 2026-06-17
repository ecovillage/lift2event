import { test, expect } from '@playwright/test';
import { resetDb, loginAs } from './helpers.js';
import { ADMIN_EMAIL, USER_EMAIL, UNAPPROVED_EMAIL } from './fixtures.js';

test.describe('Admin – Nutzerverwaltung', () => {
    test.beforeAll(() => resetDb());

    test('Normaler Nutzer kann /admin/users nicht aufrufen', async ({ page }) => {
        await loginAs(page, USER_EMAIL);
        await page.goto('/admin/users');
        // Router redirects non-admins away
        await expect(page).not.toHaveURL(/\/admin\/users/);
    });

    test('Admin sieht alle Nutzer in der Tabelle', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/users');
        await expect(page.getByText('Admin Test')).toBeVisible();
        await expect(page.getByText('Regular User')).toBeVisible();
        await expect(page.getByText('Unapproved User')).toBeVisible();
    });

    test('Freigegebene Nutzer haben grünen Status-Button', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/users');
        // Regular User and Admin Test are approved → green button
        const approvedButtons = page.locator('.bg-green-100');
        await expect(approvedButtons.first()).toBeVisible();
    });

    test('Nicht-bestätigter Nutzer hat grauen Status-Button', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/users');
        // Unapproved User should have the gray button with "Freigeben" text
        await expect(page.getByText('Freigeben')).toBeVisible();
    });

    test('Nutzer kann freigegeben werden', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/users');
        // Click "Freigeben" for the unapproved user
        await page.getByText('Freigeben').click();
        // Button should change to green/approved state
        await expect(page.getByText('Freigeben')).not.toBeVisible({ timeout: 5000 });
    });

    test('Nutzer kann gelöscht werden', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/users');

        // Accept the confirmation dialog
        page.on('dialog', d => d.accept());

        // Find and click delete for "Unapproved User" using row-scoped selector
        await page.getByRole('row').filter({ hasText: 'Unapproved User' })
            .getByRole('button', { name: 'Löschen' }).click();

        // User should be gone from the list
        await expect(page.getByText('Unapproved User')).not.toBeVisible({ timeout: 5000 });
    });

    test('Admin kann sich selbst nicht löschen', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.goto('/admin/users');
        // The delete button should not be present for the admin's own row
        const adminRow = page.getByRole('row').filter({ hasText: 'Admin Test' });
        await expect(adminRow.getByRole('button', { name: 'Löschen' })).not.toBeVisible();
    });
});
