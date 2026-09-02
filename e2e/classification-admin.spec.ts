import { test, expect, Page } from '@playwright/test';

const ADMIN_EMAIL = 'sylius@example.com';
const ADMIN_PASSWORD = 'sylius';

async function login(page: Page): Promise<void> {
    await page.goto('/admin/login');
    await page.fill('input[name="_username"]', ADMIN_EMAIL);
    await page.fill('input[name="_password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/\/admin\/?$/);
}

function guardAgainstErrors(page: Page): string[] {
    const problems: string[] = [];
    page.on('pageerror', (error) => problems.push(`pageerror: ${error.message}`));
    page.on('response', (response) => {
        if (response.status() >= 500) {
            problems.push(`${response.status()} ${response.url()}`);
        }
    });
    return problems;
}

test('admin creates a classification and a value through the UI', async ({ page }) => {
    const problems = guardAgainstErrors(page);
    const token = Date.now().toString();
    const axisCode = `size_${token}`;

    await login(page);

    const axisCodeField = 'input[name="sylius_classification_classification[code]"]';
    const valueCodeField = 'input[name="sylius_classification_classification_value[code]"]';

    await page.goto('/admin/classifications/new');
    await expect(page.locator(axisCodeField)).toBeEnabled();
    await page.fill(axisCodeField, axisCode);
    await page.fill('input[name="sylius_classification_classification[translations][en_US][name]"]', `Size ${token}`);
    await page.locator('button[type="submit"][form]').first().click();
    await expect(page).toHaveURL(/\/admin\/classifications\/\d+\/edit/);
    await expect(page.locator(axisCodeField)).toBeDisabled();
    await expect(page.locator(axisCodeField)).toHaveValue(axisCode);

    await page.goto('/admin/classification-values/new');
    await expect(page.locator(valueCodeField)).toBeEnabled();
    await page.selectOption('select[name="sylius_classification_classification_value[classification]"]', { label: axisCode });
    await page.fill('input[name="sylius_classification_classification_value[translations][en_US][name]"]', `Large ${token}`);
    await page.locator('button[type="submit"][form]').first().click();
    await expect(page).toHaveURL(/\/admin\/classification-values\/\d+\/edit/);
    await expect(page.locator(valueCodeField)).toBeDisabled();
    await expect(page.locator(valueCodeField)).toHaveValue(`${axisCode}_large-${token}`);

    expect(problems, problems.join('\n')).toEqual([]);
});

test('the classification types grid links to its values, pre-filtered', async ({ page }) => {
    const problems = guardAgainstErrors(page);
    await login(page);
    await page.goto('/admin/classifications/');

    const showValues = page.locator('table a[href*="/admin/classification-values/"][href*="criteria"]').first();
    await expect(showValues).toHaveAttribute('href', /criteria(\[|%5B)classification(\]|%5D)=\d+/i);

    const href = await showValues.getAttribute('href');
    await page.goto(href ?? '');
    await expect(page).toHaveURL(/\/admin\/classification-values\/\?.*criteria(\[|%5B)classification/i);
    await expect(page.locator('table')).toBeVisible();

    expect(problems, problems.join('\n')).toEqual([]);
});
