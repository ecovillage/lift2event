# Lift2Event – Spezifikation

Dieses Dokument ist die überarbeitete und konsolidierte Spezifikation auf Basis von
`initial-prompt`. Alle dort offen gebliebenen Fragen wurden geklärt und sind unten
eingearbeitet bzw. im Abschnitt 8 ("Entscheidungen") als Änderungs-Log dokumentiert.

## 1. Zweck

Lift2Event ist eine Webanwendung, die es Gästen einer Veranstaltung (z.B. eines
Seminars) ermöglicht, sich für die An- und Abreise zu Fahrgemeinschaften
zusammenzuschließen. Teilnehmer mit Auto können **Mitfahrangebote**, Teilnehmer
ohne Auto können **Mitfahrgesuche** veröffentlichen – jeweils mit Kontaktdaten,
damit sich beide Seiten finden und die Fahrt z.B. per Telefon, Messenger oder
Email selbst organisieren. Beide Arten von Einträgen werden als "Mitfahrten"
bezeichnet.

Eine Installation von Lift2Event verwaltet **mehrere Veranstaltungen**; jede
Veranstaltung hat eine eigene öffentliche Mitfahrbörsen-Seite mit eigener URL.

## 2. Technische Vorgaben

- **Name**: Lift2Event
- **Backend**: Laravel
- **Frontend**: Vue.js
- **Entwicklung**: Dockerisiert (Apache, MariaDB, Vue.js-Container). Produktiv
  läuft die Anwendung **ohne Docker**.
- **Datenbank**: MariaDB und PostgreSQL werden unterstützt (Migrationen/Queries
  DB-agnostisch halten).
- **Deployment**: Ansible-Skript für einen typischen LAMP-Webspace mit
  SSH-Zugang; pullt vom Webserver aus den `master`-Branch und führt
  Migrationen aus. Der Server-Pfad ist als Variable konfigurierbar.
- **Konfiguration**: `config.php` enthält DB-Zugangsdaten, SMTP-Einstellungen
  (für Passwort-Reset- und Bestätigungsmails) und Geocoding-/Karten-Konfiguration.
  Sie wird **nicht versioniert**.
  `config.php.sample` dient als Format-Vorlage. Alles andere ist unter Git
  versioniert.
- **Git**: Hauptbranch `master`.
- **Tests**: Umfassende Integrations-Testsuite für alle nutzergerichteten
  Funktionen.
- **I18n**: Vollständige Übersetzung des Frontends nach Deutsch, Englisch,
  Französisch, Chinesisch.
- **Logo**: `logo.svg` (Header, mit Schriftzug) und `favicon.svg` (Favicon,
  quadratisch) – einfache SVG-Platzhalter, später austauschbar. Liegen aktuell
  im Projekt-Root und werden bei Anlage des Frontend-Projekts in dessen
  Asset-Verzeichnis übernommen.
- **Karten**: Leaflet + OpenStreetMap-Tiles.
- **Geocoding** (Adress-Autovervollständigung & Koordinaten): Nominatim
  (OpenStreetMap); Dienst und Endpunkt über `config.php` austauschbar.
- **Farben**: CSS-Variablen für Mitfahrgesuche (orange) und Mitfahrangebote
  (grün), global definiert und anpassbar.

## 3. Datenmodell

### `event`

| Feld | Typ | Beschreibung |
|---|---|---|
| id | PK | |
| name | string | |
| start_at | datetime | Beginn (Datum + Uhrzeit) |
| end_at | datetime | Ende (Datum + Uhrzeit) |
| location_id | FK → location | Veranstaltungsort |
| created_by_id | FK → user | Ersteller |
| slug | string | a 64 bit hash to be used in URL to event, automatisch  beim Anlegen erstellt und nicht vom Nutzer änderbar |

**Öffentliche Sichtbarkeit**: Die Mitfahrbörse eines Events ist für Besucher nur
erreichbar, wenn `created_by.bestaetigt == true` (Admins gelten implizit als
bestätigt). Der Ersteller und Admins sehen das Event im eingeloggten Bereich
unabhängig davon.

### `user`

| Feld | Typ | Beschreibung |
|---|---|---|
| id | PK | |
| name | string | |
| email | string, unique | |
| password | hash | |
| is_admin | boolean, default false | Im initial-prompt nicht explizit gelistet, aber für die Admin/Nutzer-Unterscheidung notwendig (ergänzt) |
| approved | boolean, default false | Admin-Freischaltung, siehe Workflow unten |
| preferred_language | enum('de','en','fr','zh'), default 'de' | ersetzt die ursprünglich vorgesehene `language`-Tabelle |

**approved-Workflow**: Login ist auch ohne Bestätigung möglich. Ein
unbestätigter Nutzer kann sich einloggen und Veranstaltungen anlegen/bearbeiten,
sieht diese aber nur im eingeloggten Bereich. Erst nach Admin-Freischaltung
(`approved = true`, Toggle in der Nutzerverwaltung) werden die
Veranstaltungen dieses Nutzers öffentlich (für Besucher) erreichbar.

### `location`

| Feld | Typ | Beschreibung |
|---|---|---|
| id | PK | |
| address | string | Adresse (aus Geocoding-Vorschlag) |
| latitude | decimal | aus Geocoding |
| longitude | decimal | aus Geocoding |
| country_code | string(2), nullable | aus Geocoding (ISO 3166-1 alpha-2); wird für die Länder-Validierung der Telefonnummer benötigt (siehe Abschnitt 6) |

### `ride`

Repräsentiert sowohl Mitfahrangebote als auch -gesuche. Im initial-prompt war
nur `event`, `user`, `beginn`, `ende`, `location` als FK gelistet – die
UI-Beschreibungen erfordern deutlich mehr Felder, die hier ergänzt sind.

| Feld | Typ | Beschreibung |
|---|---|---|
| id | PK | |
| event_id | FK → event | |
| user_id | FK → user, nullable | gesetzt, wenn beim Anlegen eingeloggt; sonst `null` (anonymer Besucher) |
| location_id | FK → location | Heimat-/Routenort (Abfahrtsort bei Hinfahrt bzw. Zielort bei Rückfahrt – derselbe Ort für beide Richtungen) |
| type | enum('offer','request') | Angebot oder Gesuch |
| direction | enum('both-ways','outbound-only','return-only') | Hin+Rück / nur Hin / nur Rück |
| outbound_at | datetime, nullable | Datum+Uhrzeit Hinfahrt (gesetzt, wenn `direction` ∈ {both-ways, outbound-only}) |
| return_at | datetime, nullable | Datum+Uhrzeit Rückfahrt (gesetzt, wenn `direction` ∈ {both-ways, return-only}) |
| seats | integer, default 1, min 1 | "Verfügbare Plätze" (offer) bzw. "Benötigte Plätze" (request) |
| name | string | |
| email | string | |
| phone | string, nullable | inkl. Landesvorwahl mit `+`, falls Event-Land ≠ Heimat-Land |
| contact_methods | set/JSON | Teilmenge von {signal, telegram, whatsapp, email, sms, call}; mindestens 1 Eintrag erforderlich |
| info | text, nullable | Freitext "Infos zu deiner Fahrt" |
| edit_token | string, unique, nullable | Token für den Bearbeiten/Löschen-Link aus der Bestätigungsmail |

### `settings`

Singleton-Tabelle (eine Zeile) für globale, admin-konfigurierbare Einstellungen.

| Feld | Typ | Beschreibung |
|---|---|---|
| map_center_lat | decimal | Default-Kartenmittelpunkt beim Anlegen einer Veranstaltung |
| map_center_lng | decimal | |
| map_zoom | integer | Default-Zoomstufe |
| footer_links | JSON, nullable | Benutzerdefinierte Links (Label + URL) für den Footer der öffentlichen Startseite |

Wird über die neue Admin-Seite **Einstellungen** verwaltet (siehe Abschnitt 5).

### `language`-Tabelle

Entfällt (siehe `user.preferred_language` als Enum). Siehe Entscheidung in
Abschnitt 8.

## 4. Routing

### Öffentlich (kein Login nötig)

- `/e/{event.slug}` – Mitfahrbörsen-Startseite einer Veranstaltung
- `/e/{event.slug}/ride/{ride}/edit?token=...` – Bearbeiten/Löschen einer eigenen
  Mitfahrt über den per Email verschickten Link

### Authentifizierung

- `/login`
- `/register`
- Passwort-vergessen-Flow (Link von `/login` aus)

### Eingeloggter Bereich (`/admin/...`)

- `/admin` – Landing-Page: Admin → Nutzerverwaltung, Nutzer → Veranstaltungen
- `/admin/users` – Nutzerverwaltung (nur Admin)
- `/admin/events` – Veranstaltungen (Admin: alle Veranstaltungen aller Nutzer;
  Nutzer: nur eigene = "Meine Veranstaltungen")
- `/admin/events/create` – Neue Veranstaltung anlegen
- `/admin/events/{event}/edit` – Veranstaltung bearbeiten
- `/admin/settings` – Einstellungen (nur Admin)
- `/admin/profile` – Mein Profil

## 5. UI-Beschreibungen

### Backend (`/admin/...`)

#### `/login`

Login-Formular mit:
- Emailadresse
- Passwort
- Passwort-vergessen-Link
- Submit-Button (führt zu `/admin`)
- Registrieren-Link

#### Menü (eingeloggter Bereich, auf allen `/admin/...`-Seiten)

- **Admin**: Nutzerverwaltung, Veranstaltungen, Einstellungen, Mein Profil
- **Nutzer**: Meine Veranstaltungen, Mein Profil

("Einstellungen" ist neu ergänzt und – analog zur Nutzerverwaltung – nur für
Admins sichtbar.)

#### `/admin/events` – "Veranstaltungen" / "Meine Veranstaltungen"

Von oben nach unten:
- Rechts oben: Button "Neue Veranstaltung anlegen" → `/admin/events/create`
- Liste aller Veranstaltungen, zeitlich sortiert (neueste zuerst). Admin sieht
  alle Veranstaltungen aller Nutzer, ein normaler Nutzer nur seine eigenen.
  Spalten:
  - Name
  - Beginn (mit Uhrzeit)
  - Ende (mit Uhrzeit)
  - Ort (aus `location.address`)
  - Anzahl der eingetragenen Mitfahrgesuche und Mitfahrangebote

#### `/admin/events/create` und `/admin/events/{event}/edit`

Zwei Spalten in der Desktop-Ansicht:

- **Links** (20% der Breite, mindestens 200px):
  - Formular zum Eingeben der Event-Attribute. Adresse ist ein Feld mit
    Live-Vorschlägen aus Nominatim; bei Mehrdeutigkeit wählt der Nutzer eine
    der Möglichkeiten aus.
  - Öffentlicher Link zur Mitfahrbörse dieser Veranstaltung (`/e/{event}`),
    zum Kopieren/Teilen.
- **Rechts**: Karte. Der Veranstaltungsort ist als gelbes Stern-Emoji
  eingezeichnet.
  - Neu-Anlegen-Modus: Kartenausschnitt gemäß `settings`
    (Einstellungen-Seite), mit Zoom-Steuerelementen. Sobald der Nutzer eine
    Adresse einträgt, erscheint dort eine Pin-Nadel.
  - Bearbeiten-Modus: Karte mit allen eingetragenen Routen, je einem Pin beim
    Abfahrtsort, plus dem Veranstaltungsort. Angebote sind grün, Gesuche
    orange (Routen und Pins).
- Darunter: Kacheln mit allen Mitfahrgesuchen und
  -angeboten dieses Events – dieselben Kacheln wie auf der öffentlichen
  Startseite (siehe unten). Im Neu-Anlegen-Modus ist diese Liste leer.

Mobile Ansicht: vertikal gestapelt – Karte oben (obere Hälfte des
Bildschirms), darunter das Formular, darunter die Kachel-Liste.

#### `/admin/settings` – "Einstellungen" (neu, nur Admin)

Einstellungsmöglichkeit 1: der Default-Kartenausschnitt für die
Veranstaltungs-Anlegen-Seite.

- Interaktive Karte (Leaflet); der Admin verändert den Ausschnitt per Zoom und
  Ziehen mit der Maus.
- Label: "Folgenden Kartenausschnitt sehen Nutzer beim Anlegen einer
  Veranstaltung. Verändern mit Zoom und Ziehen mit der Maus."
- Speichern-Button schreibt den aktuellen Kartenausschnitt (Mittelpunkt +
  Zoomstufe) in `settings`.

Einstellungsmöglichkeit 2: Benutzerdefinierte Links im Footer

#### `/admin/users` – "Nutzerverwaltung"

Liste aller Nutzer mit folgenden Spalten:
- Name
- Email
- Anzahl der angelegten Veranstaltungen
- bestätigt-Status mit Knopf zum Umschalten
- Knopf zum Löschen

#### `/admin/profile` – "Mein Profil"

Liste mit (jeweils mit Bearbeiten-Icon):
- Name (Beispieleintrag: "Max Muster")
- Email
- Passwort

Darunter: Dropdown "bevorzugte Sprache" des eingeloggten Nutzers.

### Frontend (öffentlich, `/e/{event.slug}`)

#### Startseite

Von oben nach unten:
- Überschrift (klein): "Mitfahrbörse zur Veranstaltung"
- Überschrift (groß): `[Veranstaltungsname]`
- Box darunter:
  - Veranstaltungszeiten im langen Format (mit Uhrzeit)
  - Adresse

Darunter: eine Zeile mit den Filtermöglichkeiten "Angebote", "Gesuche" und
"Alle" (zeigt sowohl Gesuche als auch Angebote). Daneben eine Datumsauswahl, in
der nur Tage auswählbar sind, an denen mindestens eine Mitfahrt eine Hin- oder
Rückfahrt hat (`outbound_at` oder `return_at` an diesem Tag). Rechts davon ein
Button "+ Eintrag erstellen".

Darunter zweigeteilte Seite in der Desktop-Ansicht:
- **Rechts**: Karte mit allen eingetragenen Routen, je einem Pin beim
  Abfahrtsort. Der Veranstaltungsort ist ein gelber Stern. Angebote sind grün,
  Gesuche orange (Routen und Pins). Steuerelemente: Zoom in/out, Ziehen mit der
  Maus, Scroll-to-Zoom.
- **Links** (20% der Breite, mindestens 200px): vertikal gestapelte Kacheln mit
  allen Mitfahrgesuchen und -angeboten, die im aktuellen Kartenausschnitt
  sichtbar sind – eine Kachel pro Eintrag, dezent orange (Gesuch) oder grün
  (Angebot) eingefärbt. Inhalt einer Kachel:
  - Angebot/Gesuch
  - Abfahrts- bzw. Zielort
  - optional, falls zutreffend: Hinweis, dass es sich nur um eine Hin- oder
    Rückfahrt handelt
  - Wenn Hinfahrtsdatum = Veranstaltungsstartdatum: nur die Uhrzeit der
    Hinfahrt
  - Wenn Hinfahrtsdatum ≠ Veranstaltungsstartdatum: Datum und Uhrzeit der
    Hinfahrt, mit Warndreieck und Text "N Tage vor/nach Veranstaltungsbeginn"
  - Wenn Rückfahrtsdatum ≠ Veranstaltungsenddatum: Datum und Uhrzeit der
    Rückfahrt, mit Warndreieck und Text "N Tage vor/nach Veranstaltungsende"

Mobile Ansicht: horizontale Teilung – oben die Karte (obere Hälfte des
Bildschirms), darunter die Kachel-Liste.

Liste und Kartenausschnitt reagieren sofort auf Filteränderungen (clientseitig
gefiltert, kein Reload).

#### Mitfahrt-Popup (Klick auf eine Kachel)

- Oben, in einer Reihe:
  - Badge mit Personen-Icon und Anzahl der suchenden Personen bzw.
    angebotenen Plätze (`seats`), in Orange (Gesuch) bzw. Grün (Angebot)
- darunter: Info, ob Hin-, Rück- oder beide Fahrten angeboten/gesucht werden
- nächste Reihe: Datum und Uhrzeit mit jeweiligen Icons
- darunter: Label "Abfahrtsort: " (wenn Hinfahrt mit angeboten wird) bzw.
  "Zielort nach der Veranstaltung: " (wenn nur Rückfahrt angeboten wird), mit
  der entsprechenden Adresse
- darunter: Freitext aus `ride.info`
- Überschrift "Kontakt"
- Box mit Email und Telefonnummer, jeweils mit Icon
- Buttons zum Kontaktieren über die ausgewählten Messenger (`contact_methods`)

**Bearbeiten/Löschen-Berechtigung**:
- Für anonyme Besucher ist Bearbeiten nur über den Token-Link aus der
  Bestätigungsmail nutzbar (`/e/{event.slug}/ride/{ride}/edit?token=...`).
- Für anonyme Besucher ist Löschen nur über den Token-Link aus der
  Bestätigungsmail nutzbar (`/e/{event.slug}/ride/{ride}/delete?token=...`). Der Link führt zu einer Seite, auf der die Löschung noch einmal explizit bestätigt werden muss.
- Der Event-Ersteller und Admins sehen auf der Event-Bearbeiten-Seite
  zusätzlich eigene Bearbeiten- und Löschen-Icons direkt auf jeder Kachel
  (nicht im Popup), mit denen sie jede Mitfahrt ihres Events ohne Token
  bearbeiten/löschen können.

**Kontakt-Buttons / Deep-Links**:
- Email: `mailto:`
- Anruf: `tel:`
- SMS: `sms:`
- WhatsApp: `https://wa.me/{telefonnummer}`
- Signal: `https://signal.me/#p/{telefonnummer}`
- Telegram: `https://t.me/{telefonnummer}` (Telefonnummer-Fallback statt
  eigenem Username-Feld; funktioniert, wenn der Kontakt in Telegram per
  Telefonnummer auffindbar ist)

#### Footer der Startseite

- Impressum
- Github-Link
- Benutzerdefinierte Links (konfigurierbar in "Einstellungen")

#### "Mitfahrt-Erstellen-Popup"

Überschrift: "Eintrag erstellen"

1. Radio-Knöpfe, nicht-optional (nebeneinander):
   - Label: "Was möchtest du eintragen?"
   - "Ich *biete* eine Mitfahrgelegenheit." → `type = offer`
   - "Ich *suche* eine Mitfahrgelegenheit." → `type = request`

2. Radio-Knöpfe, nicht-optional (untereinander):
   - Label: "Es geht um eine Mitfahrgelegenheit für …"
   - "… Hin- und Rückfahrt" → `direction = both`
   - "… nur Hinfahrt" → `direction = outbound`
   - "… nur Rückfahrt" → `direction = return`

3. Text-Feld, nicht-optional: Name
4. Text-Feld: Email
5. Text-Feld, optional: Telefonnummer

6. Checkboxen, nicht optional (mind. 1 muss ausgewählt sein):
   - Label: "Wie möchtest du kontaktiert werden?"
   - Signal, Telegram, WhatsApp, E-Mail, SMS, Anruf
   - Die telefonbasierten Optionen (Signal, Telegram, WhatsApp, SMS, Anruf)
     sind ausgegraut, solange keine Telefonnummer angegeben ist. "E-Mail" ist
     immer wählbar (Email ist Pflichtfeld).

7. Überschrift: "Deine Route"

8. Text-Feld, nicht-optional: Label ist "Dein Abfahrtsort", wenn Hinfahrt mit
   angeboten wird (`direction` ∈ {both-ways, outbound-only}); "Dein Zielort nach der
   Veranstaltung", wenn nur Rückfahrt (`direction = return-only`). → erzeugt
   `location`.

9. **Hinfahrt-Block** (nur sichtbar, wenn `direction` ∈ {both-ways, outbound-only}):
   - Datums-Feld mit Datepicker, voreingestellt = Startdatum der Veranstaltung
   - Label: "Hinfahrt:"
   - Label 2 unter dem Datumsfeld: "Am Tag des Veranstaltungsbeginns"
   - Neben dem Datumsfeld: Zeit-Feld mit Time-Picker, nicht-optional,
     Placeholder "--:--"
   - Darunter: Pfeil-Knöpfe "1 Tag vor" / "1 Tag zurück"
   - Beim Klick auf die Pfeile ändert sich Label 2 dynamisch: "Am Tag des
     Veranstaltungsbeginns" → "N Tage vor Veranstaltungsbeginn" bzw. "N Tage
     nach Veranstaltungsbeginn"

10. **Rückfahrt-Block** (nur sichtbar, wenn `direction` ∈ {both-ways, return-only}):
    - Datums-Feld mit Datepicker, voreingestellt = Enddatum der Veranstaltung
    - Label: "Rückfahrt:"
    - Label 2 unter dem Datumsfeld: "Am letzten Veranstaltungstag"
    - Neben dem Datumsfeld: Zeit-Feld mit Time-Picker, optional, Placeholder =
      End-Uhrzeit der Veranstaltung (wird beim Speichern als Default
      übernommen, falls leer)
    - Darunter: Pfeil-Knöpfe "1 Tag vor" / "1 Tag zurück"
    - Beim Klick auf die Pfeile ändert sich Label 2 dynamisch: "Am letzten
      Veranstaltungstag" → "N Tage vor Veranstaltungsende" bzw. "N Tage nach
      Veranstaltungsende"

11. Zahl-Feld mit Minus-Knopf links und Plus-Knopf rechts, Default und Minimum
    = 1:
    - Label bei `type = offer`: "Verfügbare Plätze"
    - Label bei `type = request`: "Benötigte Plätze"

12. Textarea:
    - Label: "Infos zu deiner Fahrt"
    - Placeholder: "z.B. Infos zur Route, besondere Wünsche etc."

13. Reihe mit transparentem "Abbrechen"-Knopf und grünem
    "Eintrag einstellen!"-Knopf.

#### Bestätigungsmail

Nach dem Erstellen einer Mitfahrt mit angegebener Email erhält der Ersteller
eine Email mit:
- Bestätigung der Eintragung
- Link zum Bearbeiten
  (`/e/{event.slug}/ride/{ride}/edit?token={edit_token}`)
- Link zum Löschen
  (`/e/{event.slug}/ride/{ride}/delete?token={edit_token}`)

## 6. User Stories

### Als Admin…

- … lande ich nach dem Login in der Nutzerverwaltung.
- … kann ich alles tun, was ein normaler Nutzer auch kann.
- … sehe ich unter "Veranstaltungen" alle Veranstaltungen aller Nutzer, nicht
  nur meine eigenen.

### Als normaler Nutzer…

- … lande ich nach dem Login auf der Seite "Meine Veranstaltungen".
- … kann ich eine Veranstaltung bearbeiten, indem ich in der Liste darauf
  klicke.
- … kann ich, auch wenn mein Account noch nicht von einem Admin bestätigt
  wurde, Veranstaltungen anlegen und bearbeiten; die öffentliche
  Mitfahrbörsen-Seite (`/e/{event.slug}`) ist für Besucher aber erst nach
  Admin-Freischaltung erreichbar.

### Als Besucher (= nicht eingeloggter Nutzer) …

- … kann ich die Startseite einer Veranstaltung (`/e/{event.slug}`) besuchen, um
  Mitfahrten einzusehen oder einzustellen.
- … sehe ich sofort nach Aufruf der Startseite auf der Karte, wo die
  Veranstaltung ist und von wo aus Mitfahr-Routen dort hinführen, um schon
  einmal einen Überblick zu erhalten.
- … kann ich mit den Zoom-Steuerelementen und Ziehen bzw. Scrollen mit der
  Maus den Kartenausschnitt ändern, ähnlich wie bei Google Maps.
- … sehe ich in der Mitfahrten-Liste links von der Karte nur die Mitfahrten,
  die im Kartenausschnitt zu sehen sind.
- … passen sich bei einer Veränderung der Filter sofort Liste und
  Kartenausschnitt an den Filter an, damit ich sofort ein visuelles Feedback
  habe.
- … kann ich auf eine Mitfahrt-Kachel klicken und sehe alle Daten zu dieser
  Mitfahrt in einem Popup.
- … kann ich in einem Mitfahrt-Popup auf eine Kontaktmöglichkeit klicken, und
  es öffnet sich je nach Kontaktmöglichkeit: mein Emailprogramm, ein Messenger
  oder eine Anruf-App (mobil und Desktop).
- … kann ich eine neue Mitfahrt erstellen, indem ich auf "+ Eintrag erstellen"
  klicke, wobei sich ein Mitfahrt-Erstellen-Popup öffnet.
- … erhalte ich nach dem Erstellen einer Mitfahrt mit angegebener Email eine
  Bestätigungsmail mit einem Link, über den ich diese Mitfahrt später
  bearbeiten oder löschen kann.
- … bekomme ich beim Klick auf "Eintragen" eine Fehlermeldung, wenn
  Veranstaltungsort und meine Heimat-Adresse in verschiedenen Ländern liegen,
  meine Telefonnummer aber nicht mit einem Plus-Zeichen beginnt, um Verwirrung
  darüber zu vermeiden, in welchem Land mein Telefon registriert ist.
- … kann ich beim Erstellen einer Mitfahrt auf die Pfeil-Knöpfe unter dem
  Datum klicken und dabei das Datumsfeld um einen Tag vor- oder zurückstellen.
- … sehe ich beim Klick auf die Pfeil-Knöpfe, wie sich Label 2 entsprechend
  ändert (siehe Abschnitt 5, Mitfahrt-Erstellen-Popup, Punkte 9 und 10).
- … kann ich mit den Plus- und Minus-Knöpfen des Platz-Feldes die Zahl erhöhen
  und senken; voreingestellter Wert und Minimum ist 1 (sowohl bei "Verfügbare
  Plätze" als auch bei "Benötigte Plätze").

## 7. Offene technische Detailfragen für die Implementierung

Diese Punkte sind keine Blocker, sollten aber während der Implementierung
entschieden werden:

- Genaues Schema für `contact_methods` (JSON-Spalte vs. Pivot-Tabelle
  `ride_contact_methods`).
- Persistenz der Karten-Filter (Angebote/Gesuche/Alle, Datum) in der URL
  (Query-Parameter) für Teilbarkeit von Links.

## 8. Entscheidungen / Klärungen gegenüber `initial-prompt`

| # | Thema | Entscheidung | Begründung |
|---|---|---|---|
| 1 | Event-Modell | Mehrere Veranstaltungen pro Installation, jede mit eigener öffentlicher URL (`/e/{event}`) | initial-prompt beschreibt eine Startseite mit festem Veranstaltungsnamen, das Datenmodell und der Admin-Bereich erlauben aber mehrere Events |
| 2 | Bearbeiten/Löschen von Mitfahrten durch Besucher | Token-Link per **Email** (nicht SMS) in der Bestätigungsmail; Event-Ersteller/Admins können zusätzlich ohne Token im eingeloggten Bereich | Erklärt die in den User Stories erwähnte "Bestätigungsnachricht" und schützt vor Vandalismus, ohne eine Registrierung für Besucher zu erfordern |
| 3 | Admin-Sicht auf "Veranstaltungen" | Admin sieht alle Veranstaltungen aller Nutzer | Passt zur Menü-Unterscheidung "Veranstaltungen" (Admin) vs. "Meine Veranstaltungen" (Nutzer) und macht eine Moderation überhaupt möglich |
| 4 | `bestaetigt`-Status | Login auch ohne Bestätigung möglich; Admin-Freischaltung steuert, ob die Veranstaltungen des Nutzers öffentlich sichtbar sind | So gewählt vom Nutzer; erlaubt Nutzern, Events vorzubereiten, ohne dass sie sofort öffentlich sind |
| 5 | Kartenanbieter | Leaflet + OpenStreetMap-Tiles | Kostenlos, kein API-Key, passt zum produktiven Deploy ohne Cloud-Abhängigkeiten |
| 6 | Geocoding | Nominatim, austauschbar über `config.php` | Kostenlos nutzbar, passt zu Leaflet/OSM; Austauschbarkeit lässt spätere Migration zu kommerziellem Dienst offen |
| 7 | Branch-Name | `master` bleibt Hauptbranch | initial-prompt forderte `master`; das Repo hat aktuell auch nur `master` (kein `main`) |
| 8 | Karten-Default-Ausschnitt | Konfigurierbar über neue Admin-Seite "Einstellungen" (Center + Zoom), statt fest "Europa"/"Deutschland" | Löst den Widerspruch zwischen den beiden im initial-prompt genannten Default-Ausschnitten auf und macht ihn nutzbar für beliebige Zielgruppen |
| 9 | `language`-Tabelle | Entfällt; `user.preferred_language` ist ein Enum (de/en/fr/zh) | UI unterstützt ohnehin nur 4 feste, im Code übersetzte Sprachen; eine DB-Tabelle bringt keinen Mehrwert |
| 10 | Mitfahrten-Liste auf Event-Bearbeiten-Seite | Auch im Desktop, unterhalb von Formular und Karte über die volle Breite (gleiche Kacheln wie auf der Startseite); mobil ebenfalls ganz unten | initial-prompt erwähnte die Liste nur in der Mobil-Ansicht; auf Desktop fehlte sie ohne ersichtlichen Grund |
| 11 | Logo | Einfaches SVG-Platzhalter-Logo (`logo.svg`, `favicon.svg`) erstellt | Kein Logo im Repo vorhanden; SVG ist leicht austauschbar |
| 12 | Telegram-Kontakt | `href="https://t.me/{telefonnummer}"`, kein separates Username-Feld | Einfachste Lösung ohne zusätzliches Formularfeld, vom Nutzer so vorgegeben |
| 13 | `ride`-Datenmodell | Um Typ, Richtung, Kontaktdaten, Plätze, Freitext, Edit-Token etc. erweitert (siehe Abschnitt 3) | initial-prompt listete nur FKs + `beginn`/`ende`; UI-Beschreibungen erfordern deutlich mehr Felder |
| 14 | `location`-Modell | Um `latitude`, `longitude`, `country_code` erweitert | Für Kartenanzeige und die Länder-Validierung der Telefonnummer notwendig |
| 15 | `user.is_admin` | Neues Feld ergänzt | War im initial-prompt-Datenmodell nicht gelistet, aber für die durchgängige Admin/Nutzer-Unterscheidung erforderlich |
