import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

test('private notes work through registration, CRUD, filtering, reload and logout', async ({ page, browser }) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  const rejected = await page.request.post('/register', { data: { name: 'No token', email: 'no-token@example.test', password: 'no-token-password' } });
  expect(rejected.status()).toBe(419);
  await page.goto('/login');
  await expect(page.getByRole('heading', { name: 'Welcome back' })).toBeVisible();
  await page.screenshot({ path: 'docs/screenshots/sign-in.png', fullPage: true });
  await page.goto('/register');
  await page.getByLabel('Name', { exact: true }).fill('Alex Morgan');
  await page.getByLabel('Email address').fill('alex@example.test');
  await page.getByLabel('Password (at least 12 characters)', { exact: true }).fill('portfolio-demo-123');
  await page.getByLabel('Confirm password').fill('portfolio-demo-123');
  await page.getByRole('button', { name: 'Create account', exact: true }).click();
  await expect(page).toHaveURL(/\/notes$/);
  for (const [title, content, important] of [
    ['A thought worth keeping', 'Make room for the small ideas. They often become the most useful ones.', false],
    ['Friday reflection', 'What went well this week?\n\nWe shipped a smaller feature, asked better questions, and left the code easier to understand.', false],
    ['Ideas to revisit', 'Review the ownership checks.\nCheck the validation edge cases.\nWrite down what we learned.', true],
  ]) {
    await page.getByLabel('Title', { exact: true }).fill(title);
    await page.getByLabel('Your note').fill(content);
    await page.getByLabel('Mark as important').setChecked(important);
    await page.getByRole('button', { name: 'Save note', exact: true }).click();
    await expect(page.getByRole('heading', { name: title, exact: true })).toBeVisible();
  }
  await page.screenshot({ path: 'docs/screenshots/notes-desktop.png', fullPage: true });
  const accessibility = await new AxeBuilder({ page }).analyze();
  expect(accessibility.violations.filter(v => ['critical', 'serious'].includes(v.impact))).toEqual([]);
  await page.getByRole('button', { name: 'Important only', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Friday reflection', exact: true })).toHaveCount(0);
  await expect(page.getByRole('heading', { name: 'Ideas to revisit', exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'Show all notes', exact: true }).click();
  await page.getByRole('button', { name: 'Edit Friday reflection', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Edit note', exact: true })).toBeVisible();
  await expect(page.getByLabel('Title', { exact: true })).toBeFocused();
  await page.screenshot({ path: 'docs/screenshots/edit-note.png', fullPage: true });
  await page.getByLabel('Title', { exact: true }).fill('Weekly reflection');
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Weekly reflection', exact: true })).toBeVisible();
  await page.reload();
  await expect(page.getByRole('heading', { name: 'Weekly reflection', exact: true })).toBeVisible();
  await page.setViewportSize({ width: 390, height: 844 });
  await page.screenshot({ path: 'docs/screenshots/notes-mobile.png', fullPage: true });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  // A separate account must never receive the first account's data.
  const context = await browser.newContext();
  const other = await context.newPage();
  await other.goto('http://127.0.0.1:8010/register');
  await other.getByLabel('Name', { exact: true }).fill('Other account');
  await other.getByLabel('Email address').fill('other@example.test');
  await other.getByLabel('Password (at least 12 characters)', { exact: true }).fill('portfolio-demo-456');
  await other.getByLabel('Confirm password').fill('portfolio-demo-456');
  await other.getByRole('button', { name: 'Create account', exact: true }).click();
  await expect(other.getByRole('heading', { name: 'Room for your next idea' })).toBeVisible();
  await expect(other.getByText('Ideas to revisit', { exact: true })).toHaveCount(0);
  await context.close();
  page.once('dialog', dialog => dialog.accept());
  await page.getByRole('button', { name: 'Delete Weekly reflection', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Weekly reflection', exact: true })).toHaveCount(0);
  await page.getByRole('button', { name: 'Sign out', exact: true }).click();
  await expect(page).toHaveURL(/\/login$/);
  await page.goto('/notes');
  await expect(page).toHaveURL(/\/login$/);
  expect(errors).toEqual([]);
});
