import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.DDEV_PRIMARY_URL ?? 'https://sylius-classification-bundle.ddev.site';

export default defineConfig({
    testDir: '.',
    timeout: 30_000,
    expect: { timeout: 3_000 },
    fullyParallel: false,
    reporter: [['list']],
    use: {
        baseURL,
        ignoreHTTPSErrors: true,
        trace: 'retain-on-failure',
    },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    ],
});
