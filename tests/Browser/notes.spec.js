import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

test('private React notes: register, CRUD, search, filtering and sign out', async ({ page, browser }) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));

  await page.goto('/login');
  await expect(page.getByRole('heading', { name: 'Welcome back' })).toBeVisible();

  await page.getByRole('link', { name: 'Create an account' }).click();
  await page.getByRole('heading', { name: 'Create your account' }).waitFor();
  await page.getByLabel('Name', { exact: true }).fill('Alex Morgan');
  await page.getByLabel('Email address').fill('alex@example.test');
  await page.getByLabel('Password (at least 12 characters)').fill('portfolio-demo-123');
  await page.getByLabel('Confirm password').fill('portfolio-demo-123');
  await page.getByRole('button', { name: 'Create account' }).click();

  await expect(page).toHaveURL(/\/notes$/);
  await expect(page.getByRole('heading', { name: 'A home for your thoughts.' })).toBeVisible();

  await page.getByLabel('Title', { exact: true }).fill('Friday reflection');
  await page.getByLabel('Your note').fill('What went well this week?');
  await page.getByRole('button', { name: 'Save note' }).click();
  await expect(page.getByRole('heading', { name: 'Friday reflection' })).toBeVisible();

  await page.getByLabel('Title', { exact: true }).fill('Ideas to revisit');
  await page.getByLabel('Your note').fill('Review the ownership checks.');
  await page.getByLabel('Mark as important').check();
  await page.getByRole('button', { name: 'Save note' }).click();
  await expect(page.getByRole('heading', { name: 'Ideas to revisit' })).toBeVisible();

  const axe = await new AxeBuilder({ page }).analyze();
  expect(axe.violations.filter(v => ['serious', 'critical'].includes(v.impact))).toEqual([]);

  await page.getByRole('button', { name: 'Important only' }).click();
  await expect(page.getByRole('heading', { name: 'Friday reflection' })).toHaveCount(0);
  await expect(page.getByRole('heading', { name: 'Ideas to revisit' })).toBeVisible();
  await page.getByRole('button', { name: 'Show all notes' }).click();
  await page.getByRole('button', { name: 'Edit Friday reflection' }).click();
  await expect(page.getByRole('heading', { name: 'Edit note' })).toBeVisible();
  await expect(page.getByLabel('Title', { exact: true })).toBeFocused();
  await page.getByLabel('Title', { exact: true }).fill('Weekly reflection');
  await page.getByRole('button', { name: 'Save changes' }).click();
  await expect(page.getByRole('heading', { name: 'Weekly reflection' })).toBeVisible();

  await page.reload();
  await expect(page.getByRole('heading', { name: 'Weekly reflection' })).toBeVisible();
  await page.getByRole('searchbox', { name: 'Search notes' }).fill('Ideas');
  await expect(page.getByRole('heading', { name: 'Ideas to revisit' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Weekly reflection' })).toHaveCount(0);
  await page.getByRole('searchbox', { name: 'Search notes' }).fill('');

  await page.setViewportSize({ width: 390, height: 844 });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);

  const context = await browser.newContext();
  const other = await context.newPage();
  await other.goto('/register');
  await other.getByLabel('Name', { exact: true }).fill('Another writer');
  await other.getByLabel('Email address').fill('other@example.test');
  await other.getByLabel('Password (at least 12 characters)').fill('portfolio-demo-456');
  await other.getByLabel('Confirm password').fill('portfolio-demo-456');
  await other.getByRole('button', { name: 'Create account' }).click();
  await expect(other.getByRole('heading', { name: 'Room for your next idea' })).toBeVisible();
  await expect(other.getByText('Ideas to revisit')).toHaveCount(0);
  await context.close();

  page.once('dialog', dialog => dialog.accept());
  await page.getByRole('button', { name: 'Delete Weekly reflection' }).click();
  await expect(page.getByRole('heading', { name: 'Weekly reflection' })).toHaveCount(0);
  await page.getByRole('button', { name: 'Sign out' }).click();
  await expect(page).toHaveURL(/\/login$/);
  await page.goto('/notes');
  await expect(page).toHaveURL(/\/login$/);
  expect(errors).toEqual([]);
});
