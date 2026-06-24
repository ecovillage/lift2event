import { execSync } from 'child_process';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';

const PROJECT = resolve(dirname(fileURLToPath(import.meta.url)), '..');

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

export default async function globalTeardown() {
    execSync('docker compose up -d app', { cwd: PROJECT, stdio: 'inherit' });
    await waitForApp();
}
