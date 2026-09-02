import { test, expect, Page } from '@playwright/test';

async function login(page: Page): Promise<void> {
    await page.goto('/admin/login');
    await page.fill('input[name="_username"]', 'sylius@example.com');
    await page.fill('input[name="_password"]', 'sylius');
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/admin\/?$/);
}

async function seedTypeWithValue(page: Page, token: string): Promise<string> {
    const axisCode = `col_${token}`;

    await page.goto('/admin/classifications/new');
    await page.fill('input[name="sylius_classification_classification[code]"]', axisCode);
    await page.fill('input[name="sylius_classification_classification[translations][en_US][name]"]', `Color ${token}`);
    await page.locator('button[type="submit"][form]').first().click();
    await expect(page).toHaveURL(/\/classifications\/\d+\/edit/);

    await page.goto('/admin/classification-values/new');
    await page.selectOption('select[name="sylius_classification_classification_value[classification]"]', { label: axisCode });
    await page.fill('input[name="sylius_classification_classification_value[translations][en_US][name]"]', `Red ${token}`);
    await page.locator('button[type="submit"][form]').first().click();
    await expect(page).toHaveURL(/\/classification-values\/\d+\/edit/);

    return axisCode;
}

test('the variant tab adds a type, searches values over AJAX, and persists the assignment', async ({ page }) => {
    const problems: string[] = [];
    page.on('pageerror', (error) => problems.push(`pageerror: ${error.message}`));
    page.on('response', (response) => {
        if (response.status() >= 500) {
            problems.push(`${response.status()} ${response.url()}`);
        }
    });

    const token = Date.now().toString();
    await login(page);
    const axisCode = await seedTypeWithValue(page, token);

    await page.goto('/admin/products/1/variants/1/edit');
    await page.locator('[data-test-side-navigation-tab="classifications"]').click();

    await page.locator('[data-test-classification-add-type]').selectOption(axisCode);
    const row = page.locator(`[data-test-classification-row="${axisCode}"]`);
    await expect(row).toBeVisible();

    await row.locator(`[data-test-classification-search="${axisCode}"]`).fill('Red');
    const candidate = row.locator('[data-test-classification-candidate]').first();
    await expect(candidate).toBeVisible();
    await candidate.click();
    await expect(row).toContainText(`Red ${token}`);

    await page.reload();
    await page.locator('[data-test-side-navigation-tab="classifications"]').click();
    await expect(page.locator(`[data-test-classification-row="${axisCode}"]`)).toContainText(`Red ${token}`);

    expect(problems, problems.join('\n')).toEqual([]);
});
