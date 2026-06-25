# Deploy auf einem LAMP-Webspace

Für typische Webspace-Pakete (Ionos und Konsorten) mit SSH-Zugang, aber ohne
root/sudo, eigenen vhost oder systemd. `deploy.yml` aktualisiert den Code,
installiert Abhängigkeiten, baut das Frontend, migriert die Datenbank und
cacht Config/Routen/Views.

## Einmalige manuelle Vorbereitung

Das sind Schritte, die nur einmal über die Anbieter-Oberfläche bzw. per SSH
erledigt werden müssen, bevor der erste `ansible-playbook`-Lauf funktioniert:

1. **Datenbank anlegen** – MySQL/MariaDB-Datenbank und Benutzer über die
   Kundenoberfläche des Hosters anlegen (auf Webspace-Paketen meist nicht per
   SSH möglich).
2. **SSH-Deploykey** – einen Deploykey auf dem Server erzeugen
   (`ssh-keygen`) und als Read-Deploykey im Git-Repository hinterlegen, damit
   `git_repo` (siehe `vars/main.yml`) vom Server aus erreichbar ist.
3. **`.env` anlegen** – auf dem Server `.env.example` nach `.env` kopieren
   und DB-/SMTP-Zugangsdaten eintragen. `APP_KEY` kann leer bleiben, das
   Playbook erzeugt ihn beim ersten Lauf automatisch.
4. **Dokumentenstamm setzen** – die Domain in der Hoster-Oberfläche auf
   `{{ deploy_path }}/public` zeigen lassen (Webspace-Pakete erlauben i. d. R.
   keine eigene vhost-Konfiguration, aber die Wahl eines Unterverzeichnisses
   als Document Root).
5. **PHP-Erweiterungen prüfen** – `pdo_mysql`, `zip`, `bcmath`, `intl` müssen
   aktiviert sein (bei den meisten Hostern Standard, ggf. im Panel
   einschalten).
6. **Composer/Node verfügbar?** – das Playbook führt `composer`, `npm` und
   `git` auf dem Server aus. Vorab per SSH prüfen, dass alle drei verfügbar
   sind (`composer_bin`/`npm_bin`/`php_bin` in `vars/main.yml` ggf. anpassen).

## Verwendung

```bash
cp ansible/inventory.example ansible/inventory
# Server, Benutzer ggf. Port/Key in ansible/inventory eintragen
# deploy_path, git_repo, php_bin etc. in ansible/vars/main.yml anpassen

ansible-playbook -i ansible/inventory ansible/deploy.yml
```

Jeder weitere Lauf zieht den aktuellen `master`-Branch, baut neu und migriert
– die Seite geht dafür kurz in den Wartungsmodus (`artisan down`/`up`).
