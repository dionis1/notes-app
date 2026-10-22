import { chromium, expect } from '@playwright/test';

const base = process.env.SCREENSHOT_URL || 'http://127.0.0.1:8001';
const browser = await chromium.launch(process.env.CHROME_BIN ? { executablePath: process.env.CHROME_BIN } : {});
try {
  const page = await browser.newPage({ viewport: { width: 1440, height: 1050 }, deviceScaleFactor: 1 });
  await page.goto(base + '/login');
  await expect(page.getByRole('heading', { name: 'Welcome back' })).toBeVisible();
  await page.screenshot({ path: 'docs/screenshots/sign-in.png', fullPage: true });
  await page.getByLabel('Email address').fill('writer@demo.notes.test');
  await page.getByLabel('Password', { exact: true }).fill(process.env.DEMO_PASSWORD || 'NotesDemo!2026');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/notes$/);
  await page.getByRole('button', { name: 'Important only', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Weekly reflection', exact: true })).toBeVisible();
  await page.screenshot({ path: 'docs/screenshots/notes-desktop.png', fullPage: true });
  await page.getByRole('button', { name: 'Edit Weekly reflection', exact: true }).click();
  await expect(page.getByLabel('Title', { exact: true })).toHaveValue('Weekly reflection');
  await page.screenshot({ path: 'docs/screenshots/edit-note.png', fullPage: true });
  await page.getByRole('button', { name: 'History of Weekly reflection', exact: true }).click();
  await expect(page.getByRole('button', { name: 'Restore version 1', exact: true })).toBeVisible();
  await page.screenshot({ path: 'docs/screenshots/revision-history.png', fullPage: false });
  await page.getByRole('button', { name: 'Close history', exact: true }).click();
  await page.getByRole('searchbox', { name: 'Search notes' }).fill('Weekly');
  await expect(page.getByRole('heading', { name: 'Things to revisit', exact: true })).toHaveCount(0);
  await page.setViewportSize({ width: 390, height: 844 });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
  await page.screenshot({ path: 'docs/screenshots/notes-mobile.png', fullPage: true });
  console.log('Captured five demo screenshots.');
} finally { await browser.close(); }
