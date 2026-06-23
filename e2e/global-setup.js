import { execSync } from 'child_process';
import { readFileSync } from 'fs';

const PROJECT = '/home/martin/werkbank/lift2event2';

function getRootPassword() {
    try {
        const env = readFileSync(`${PROJECT}/.env`, 'utf8');
        return env.match(/^DB_ROOT_PASSWORD=(.+)/m)?.[1]?.trim() ?? 'secret';
    } catch {
        return 'secret';
    }
}

async function waitForApp(maxMs = 30_000) {
    const deadline = Date.now() + maxMs;
    while (Date.now() < deadline) {
        try {
            const res = await fetch('http://localhost:8080');
            if (res.status < 500) return;
        } catch {}
        await new Promise(r => setTimeout(r, 500));
    }
    throw new Error('App not ready within timeout');
}

export default async function globalSetup() {
    const rootPwd = getRootPassword();

    execSync(
        `docker compose exec -T db mariadb -uroot -p${rootPwd} -e "CREATE DATABASE IF NOT EXISTS lift2event_e2e; GRANT ALL PRIVILEGES ON lift2event_e2e.* TO 'lift2event'@'%'; FLUSH PRIVILEGES;"`,
        { cwd: PROJECT, stdio: 'pipe' }
    );

    execSync(
        'docker compose -f docker-compose.yml -f docker-compose.e2e.yml up -d app',
        { cwd: PROJECT, stdio: 'inherit' }
    );

    execSync(
        'docker compose exec -T app php artisan migrate --force',
        { cwd: PROJECT, stdio: 'pipe', timeout: 60_000 }
    );

    await waitForApp();
}
