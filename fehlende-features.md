# Fehlende Features (Spec-Audit)

Feature #1 (Bestätigungsmail nach Ride-Erstellung) ist implementiert (commit f0ea89c).
Feature #2 (Passwort-vergessen-Flow) ist implementiert.
Feature #3 (Admin-Landing-Page-Redirect) ist implementiert.
Feature #4 (Mitfahrten-Liste auf der Event-Bearbeiten-Seite) ist implementiert.
Feature #5 (Karte im Event-Bearbeiten-Modus zeigt Routen) ist implementiert.
Feature #6 (Bearbeiten/Löschen-Icons für Admin/Event-Ersteller auf Kacheln) ist implementiert.

---

## #3 Admin-Landing-Page-Redirect (Spec §4, §6)

- `router/index.js` leitet `/admin` immer auf `admin.events` um
- Spec: Admin → `/admin/users` (Nutzerverwaltung); normaler Nutzer → `/admin/events`

## #4 Mitfahrten-Liste auf der Event-Bearbeiten-Seite (Spec §5)

- `EventForm.vue` zeigt keine Ride-Kacheln unterhalb von Formular/Karte
- Spec: gleiche Ride-Kacheln wie auf der öffentlichen Seite, unterhalb von Formular+Karte im Edit-Modus; leer im Create-Modus

## #5 Karte im Event-Bearbeiten-Modus zeigt keine Routen (Spec §5)

- Karte in `EventForm.vue` (Edit-Modus) zeigt nur den Event-Location-Pin
- Spec: alle Ride-Routen mit grünen (Angebot) / orangen (Gesuch) Pins sollen auf der Karte gezeichnet werden

## #6 Bearbeiten/Löschen-Icons für Admin/Event-Ersteller auf Kacheln (Spec §5)

- Keine Edit/Delete-Icons auf Ride-Kacheln für eingeloggte Admins/Event-Ersteller
- Keine Backend-API-Endpunkte für tokenlose Admin/Creator-Ride-Verwaltung
- Spec: Admins und Event-Ersteller sehen Edit/Delete-Icons direkt auf jeder Kachel auf der Event-Bearbeiten-Seite, ohne Token

## #7 Events-Liste: Angebote und Gesuche separat zählen (Spec §5)

- `Events.vue` zeigt eine einzelne `rides_count`-Spalte
- Spec erfordert separate Zählungen für Angebote (Mitfahrangebote) und Gesuche (Mitfahrgesuche)

## #8 Footer der öffentlichen Startseite (Spec §5)

- `EventPage.vue` hat kein Footer-Element
- Spec: Footer mit Impressum-Link, GitHub-Link und konfigurierbaren Links aus `settings.footer_links`
