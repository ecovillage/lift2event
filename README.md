# Lift2Event

A self-hosted rideshare board for events. Attendees with cars can post ride offers; attendees without cars can post ride requests — each with contact details so people can coordinate directly. One installation manages multiple events, each with its own public URL.

## Features

- Public rideshare board per event (`/e/{slug}`) — no account required to browse or post
- Interactive map (Leaflet + OpenStreetMap) showing routes and the event location
- Ride offers (green) and ride requests (orange) with client-side filtering by type and date
- Confirmation email with edit/delete token links for anonymous entries
- Admin backend: manage events, users, and global settings
- Geocoding via Nominatim (swappable via `.env`)
- Route display via OpenRouteService
- UI available in German, English, French, and Chinese
- Supports MariaDB and PostgreSQL

## Requirements

- PHP 8.3+, Composer, Node 20+, npm (production build / Ansible deploy)
- Docker and Docker Compose (development only)

## Local development

```bash
git clone git@github.com:ecovillage/lift2event.git
cd lift2event

cp .env.example .env
# Edit .env: set APP_KEY, DB credentials, SMTP, etc.
# Or generate APP_KEY after containers start (see below)

docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

The app is available at **http://localhost:8080**.  
Adminer (DB browser) is at http://localhost:8081.  
Vite dev server runs at http://localhost:5173 (proxied through Apache).

To seed demo data:

```bash
docker compose exec app php artisan app:seed-demo-data
```

To create the first admin user:

```bash
docker compose exec app php artisan tinker
# >>> App\Models\User::factory()->create(['email' => 'you@example.com', 'is_admin' => true])
```

## Configuration

Copy `.env.example` to `.env` and adjust the values. Key settings:

| Variable | Description |
|---|---|
| `DB_CONNECTION` | `mysql` (MariaDB) or `pgsql` (PostgreSQL) |
| `MAIL_*` | SMTP credentials for confirmation and password-reset emails |
| `NOMINATIM_URL` / `NOMINATIM_USER_AGENT` | Geocoding endpoint (Nominatim-compatible) |
| `OPENROUTESERVICE_API_KEY` | Route display on the map (free key at openrouteservice.org) |
| `OSM_TILE_URL` | Map tile source |
| `APP_LOCALE` | Default UI language (`de`, `en`, `fr`, `zh`) |

## Tests

**Unit / integration tests** (PHPUnit, runs inside Docker):

```bash
docker compose exec -T app php artisan test
```

**End-to-end tests** (Playwright):

```bash
npx playwright test
```

## Building frontend assets

After UI changes, rebuild the frontend:

```bash
docker compose exec -T node npm run build
```

## Deployment

Production runs without Docker on a standard LAMP webspace with SSH access. The Ansible playbook builds `vendor/` and frontend assets locally, transfers them via rsync, runs migrations, and caches config/routes/views.

**One-time server setup:**

1. Create a MariaDB/PostgreSQL database via your host's control panel.
2. Generate an SSH deploy key on the server and add it to the repository.
3. Copy `.env.example` to `.env` on the server and fill in credentials (`APP_KEY` can be left empty; the playbook generates it on first run).
4. Point the domain's document root to `{deploy_path}/public`.
5. Ensure PHP extensions `pdo_mysql`, `zip`, `bcmath`, and `intl` are enabled.

**Deploy:**

```bash
cp ansible/inventory.example ansible/inventory
# Edit ansible/inventory (host, user, port/key)
# Edit ansible/vars/main.yml (deploy_path, git_repo, php_bin, etc.)

ansible-galaxy collection install -r ansible/requirements.yml
ansible-playbook -i ansible/inventory ansible/deploy.yml
```

Each subsequent run pulls `master`, rebuilds, and migrates. The site briefly enters maintenance mode during deployment.

## License

MIT
