import { test, expect } from '@playwright/test';
import { resetDb, loginAs } from './helpers.js';
import { ADMIN_EMAIL, USER_EMAIL, PASSWORD } from './fixtures.js';

test.describe('Authentifizierung', () => {
    test.beforeAll(() => resetDb());

    test('Nicht-eingeloggter Nutzer wird von /admin zu /login umgeleitet', async ({ page }) => {
        await page.goto('/admin');
        await expect(page).toHaveURL(/\/login/);
    });

    test('Nicht-eingeloggter Nutzer wird von /admin/users zu /login umgeleitet', async ({ page }) => {
        await page.goto('/admin/users');
        await expect(page).toHaveURL(/\/login/);
    });

    test('Login-Formular zeigt Email- und Passwort-Felder', async ({ page }) => {
        await page.goto('/login');
        await expect(page.locator('#email')).toBeVisible();
        await expect(page.locator('#password')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Anmelden' })).toBeVisible();
    });

    test('Falsches Passwort zeigt Fehlermeldung', async ({ page }) => {
        await page.goto('/login');
        await page.locator('#email').fill(ADMIN_EMAIL);
        await page.locator('#password').fill('falschespasswort');
        await page.getByRole('button', { name: 'Anmelden' }).click();
        await expect(page.getByText(/falsch|ungültig|fehlgeschlagen/i)).toBeVisible();
    });

    test('Admin sieht Nutzerverwaltung und Einstellungen im Menü', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await expect(page).toHaveURL(/\/admin\/events/);
        await expect(page.getByRole('link', { name: 'Nutzerverwaltung' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Einstellungen' })).toBeVisible();
    });

    test('Normaler Nutzer sieht kein Admin-Menü', async ({ page }) => {
        await loginAs(page, USER_EMAIL);
        await expect(page).toHaveURL(/\/admin\/events/);
        await expect(page.getByRole('link', { name: 'Meine Veranstaltungen' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Nutzerverwaltung' })).not.toBeVisible();
        await expect(page.getByRole('link', { name: 'Einstellungen' })).not.toBeVisible();
    });

    test('Logout leitet zu /login um', async ({ page }) => {
        await loginAs(page, ADMIN_EMAIL);
        await page.getByRole('button', { name: 'Abmelden' }).click();
        await expect(page).toHaveURL(/\/login/);
    });

    test('Registrierung mit neuer E-Mail erstellt Account', async ({ page }) => {
        await page.goto('/register');
        await page.locator('#name').fill('Neuer Nutzer');
        await page.locator('#email').fill('neuernutzer@example.com');
        await page.locator('#password').fill('password123');
        await page.locator('#password_confirmation').fill('password123');
        await page.getByRole('button', { name: 'Registrieren' }).click();
        await expect(page).toHaveURL(/\/admin\/events/);
    });

    test('Registrierung mit bereits vorhandener E-Mail zeigt Fehler', async ({ page }) => {
        await page.goto('/register');
        await page.locator('#name').fill('Duplikat');
        await page.locator('#email').fill(ADMIN_EMAIL);
        await page.locator('#password').fill('password123');
        await page.locator('#password_confirmation').fill('password123');
        await page.getByRole('button', { name: 'Registrieren' }).click();
        await expect(page).not.toHaveURL(/\/admin\/events/);
    });

    test('Registrierung mit falschem Passwortbestätigung zeigt Fehler', async ({ page }) => {
        await page.goto('/register');
        await page.locator('#name').fill('Test');
        await page.locator('#email').fill('mismatch@example.com');
        await page.locator('#password').fill('password123');
        await page.locator('#password_confirmation').fill('anderespasswort');
        await page.getByRole('button', { name: 'Registrieren' }).click();
        await expect(page.locator('.text-red-600')).toBeVisible();
    });

    test('Passwort-vergessen-Link ist sichtbar', async ({ page }) => {
        await page.goto('/login');
        await expect(page.getByRole('link', { name: 'Passwort vergessen?' })).toBeVisible();
    });

    test('Registrieren-Link verweist auf /register', async ({ page }) => {
        await page.goto('/login');
        await page.getByRole('link', { name: 'Registrieren' }).click();
        await expect(page).toHaveURL(/\/register/);
    });
});
