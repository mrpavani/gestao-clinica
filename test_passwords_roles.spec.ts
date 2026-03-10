import { test, expect } from '@playwright/test';

test('Test Reset Password and Change Password UIs', async ({ page }) => {
  // 1. Forgot password UI
  await page.goto('http://localhost:8000/?page=login');
  await page.click('a[href="?page=forgot_password"]');
  await page.waitForURL('**/?page=forgot_password');
  await page.locator('input[name="username"]').fill('admin'); // Simulate trying to recover admin
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: 'forgot_password_success.png', fullPage: true });

  // 2. Change password UI - Log in first
  await page.goto('http://localhost:8000/?page=login');
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'admin123'); // Original pass (might be different if testing changes it)
  await page.click('button[type="submit"]');
  
  if (page.url().includes('branch_select')) {
      const btn = page.locator('a.btn-primary');
      if (await btn.count() > 0) {
          await btn.first().click();
      }
      await page.waitForURL('**/?page=dashboard');
  }

  await page.goto('http://localhost:8000/?page=change_password');
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: 'change_password_ui.png', fullPage: true });
});
