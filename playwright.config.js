import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir:        './e2e',
    globalSetup:    './e2e/global-setup.js',
    globalTeardown: './e2e/global-teardown.js',
    use: {
        baseURL:  'http://localhost:8080',
        headless: true,
        trace:    'on-first-retry',
        video:    'on-first-retry',
        locale:   'de-DE',
    },
    timeout:        25000,
    expect:         { timeout: 10000 },
    workers:        1,     // sequential – shared DB state
    reporter:       'list',
});
