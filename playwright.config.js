import { defineConfig } from '@playwright/test';
export default defineConfig({
  testDir: './tests/Browser',
  workers: 1,
  use: {
    baseURL: 'http://127.0.0.1:8010',
    viewport: { width: 1440, height: 1100 },
    launchOptions: process.env.CHROME_BIN ? { executablePath: process.env.CHROME_BIN } : {},
    trace: 'retain-on-failure',
  },
  webServer: {
    command: 'sh scripts/browser-server.sh',
    url: 'http://127.0.0.1:8010/login',
    reuseExistingServer: false,
    timeout: 120_000,
    gracefulShutdown: { signal: 'SIGTERM', timeout: 10_000 },

  },
});
