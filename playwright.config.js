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
    env: {
      APP_ENV: 'local', APP_DEBUG: 'false',
      APP_KEY: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
      APP_URL: 'http://127.0.0.1:8010', DB_CONNECTION: 'sqlite',
      SANCTUM_STATEFUL_DOMAINS: '127.0.0.1:8010,localhost:8010', CACHE_STORE: 'array',
      DB_DATABASE: '/tmp/notes-browser.sqlite', SESSION_DRIVER: 'file', MAIL_MAILER: 'log',
    },
  },
});
