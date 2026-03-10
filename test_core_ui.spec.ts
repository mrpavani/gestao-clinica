import { test, expect } from '@playwright/test';

test('Professional Form Layout Screenshot', async ({ page }) => {
  await page.goto('http://localhost:8000/?page=login');
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'admin123');
  await page.click('button[type="submit"]');

  if (page.url().includes('branch_select')) {
      const btn = page.locator('a.btn-primary');
      if (await btn.count() > 0) {
          await btn.first().click();
      }
      await page.waitForURL('**/?page=dashboard');
  }

  await page.goto('http://localhost:8000/?page=professionals_new');
  await page.waitForLoadState('networkidle');
  // Need multiple resolutions
  // Desktop
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.screenshot({ path: 'prof_form_desktop.png', fullPage: true });
  // Mobile
  await page.setViewportSize({ width: 375, height: 812 });
  await page.screenshot({ path: 'prof_form_mobile.png', fullPage: true });

});
