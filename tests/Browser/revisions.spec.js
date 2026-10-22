import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

async function register(page) {
  const email = 'writer-' + Date.now() + '@example.test';
  await page.goto('/register');
  await page.getByLabel('Name', { exact: true }).fill('Draft Writer');
  await page.getByLabel('Email address').fill(email);
  await page.getByLabel('Password (at least 12 characters)').fill('NotesTesting!2026');
  await page.getByLabel('Confirm password').fill('NotesTesting!2026');
  await page.getByRole('button', { name: 'Create account' }).click();
  await expect(page).toHaveURL(/\/notes$/);
  return email;
}
async function create(page, title) {
  await page.getByLabel('Title', { exact: true }).fill(title);
  await page.getByLabel('Your note').fill('First version of the idea.');
  await page.getByRole('button', { name: 'Save note', exact: true }).click();
  await expect(page.getByRole('heading', { name: title, exact: true })).toBeVisible();
}

test('revision restore creates new history and the dialog supports keyboard focus', async ({ page }) => {
  await register(page);
  await create(page, 'Revision example');
  await page.getByRole('button', { name: 'Edit Revision example', exact: true }).click();
  await page.getByLabel('Your note').fill('A second version to compare.');
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page.getByText('Note updated.', { exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'History of Revision example', exact: true }).click();
  const dialog = page.getByRole('dialog', { name: 'Revision history' });
  await expect(dialog.getByRole('button', { name: 'Close history' })).toBeFocused();
  await expect(dialog.getByRole('button', { name: 'Restore version 1', exact: true })).toBeEnabled();
  const axe = await new AxeBuilder({ page }).analyze();
  expect(axe.violations.filter(v => ['serious', 'critical'].includes(v.impact))).toEqual([]);
  page.once('dialog', prompt => prompt.accept());
  await dialog.getByRole('button', { name: 'Restore version 1', exact: true }).click();
  await expect(page.getByText('Earlier content restored as a new version.')).toBeVisible();
  await expect(page.getByLabel('Your note')).toHaveValue('First version of the idea.');
  await page.getByRole('button', { name: 'History of Revision example', exact: true }).click();
  await expect(dialog.getByText('Current version 3', { exact: false })).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(dialog).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'History of Revision example', exact: true })).toBeFocused();
});

test('conflicts survive filtering and a preserved draft can become a new note', async ({ page }) => {
  await register(page);
  await create(page, 'Saved original');
  await page.getByRole('button', { name: 'Edit Saved original', exact: true }).click();
  await page.getByLabel('Title', { exact: true }).fill('Recovered draft');
  await page.getByLabel('Your note').fill('Unsaved work that must stay intact.');
  await page.route('**/api/v1/notes/*', route => route.request().method() === 'PATCH'
    ? route.fulfill({ status: 409, contentType: 'application/json', body: JSON.stringify({ message: 'Changed elsewhere.' }) }) : route.continue());
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page.getByRole('alert')).toContainText('Your draft is still here');
  await page.getByRole('button', { name: 'Important only', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Room for your next idea' })).toBeVisible();
  await expect(page.getByRole('alert')).toContainText('Your draft is still here');
  await expect(page.getByLabel('Your note')).toHaveValue('Unsaved work that must stay intact.');
  page.once('dialog', prompt => prompt.dismiss());
  await page.getByRole('button', { name: 'Cancel', exact: true }).click();
  await expect(page.getByLabel('Title', { exact: true })).toHaveValue('Recovered draft');
  await page.getByRole('button', { name: 'Keep draft as new note' }).click();
  await page.getByRole('button', { name: 'Show all notes', exact: true }).click();
  await page.getByRole('button', { name: 'Save note', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Recovered draft', exact: true })).toBeVisible();
});

test('session expiry preserves a draft only for the same account in this tab', async ({ page }) => {
  const email = await register(page);
  await page.getByLabel('Title', { exact: true }).fill('Draft before expiry');
  await page.getByLabel('Your note').fill('Private unsaved content');
  let expired = false;
  await page.route('**/api/v1/notes?**', route => {
    if (!expired) { expired = true; return route.fulfill({ status: 401, contentType: 'application/json', body: '{"message":"Unauthenticated."}' }); }
    return route.continue();
  });
  await page.getByRole('button', { name: 'Important only', exact: true }).click();
  await expect(page).toHaveURL(/\/login$/);
  await expect(page.getByRole('status')).toContainText('unsaved draft is kept');
  await page.getByLabel('Email address').fill(email);
  await page.getByLabel('Password', { exact: true }).fill('NotesTesting!2026');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/notes$/);
  await expect(page.getByLabel('Title', { exact: true })).toHaveValue('Draft before expiry');
  await expect(page.getByLabel('Your note')).toHaveValue('Private unsaved content');
});

test('cross-site writes without a token fail and private API requests require auth', async ({ page }) => {
  await page.goto('/login');
  const response = await page.request.post('/login', {
    headers: { Accept: 'application/json', Origin: 'https://untrusted.example.test', 'Sec-Fetch-Site': 'cross-site' },
    data: { email: 'missing@example.test', password: 'NotesTesting!2026' },
  });
  expect(response.status()).toBe(419);
  expect((await page.request.get('/api/v1/notes')).status()).toBe(401);
});
