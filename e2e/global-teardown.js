import { execSync } from 'child_process';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';

const PROJECT = resolve(dirname(fileURLToPath(import.meta.url)), '..');

export default async function globalTeardown() {
    execSync(
        'docker compose -f docker-compose.yml -f docker-compose.e2e.yml stop app_e2e',
        { cwd: PROJECT, stdio: 'inherit' }
    );
}
