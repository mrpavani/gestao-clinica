import { test, expect } from '@playwright/test';

test('Agenda Enhancements Screenshots', async ({ page }) => {
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

  // 1. List View
  await page.goto('http://localhost:8000/?page=schedule&view=list');
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: 'agenda_list_view.png', fullPage: true });

  // 2. Filtered by Therapy
  // Try to find a therapy from the select options
  const therapyOptions = await page.locator('select[name="therapy_filter"] option').allTextContents();
  // Filter out the "Todas as Terapias"
  const actualTherapies = therapyOptions.filter(t => t !== 'Todas as Terapias');
  
  if (actualTherapies.length > 0) {
      // Just select the first one arbitrarily to see the filter effect
      await page.selectOption('select[name="therapy_filter"]', { label: actualTherapies[0] });
      await page.waitForLoadState('networkidle');
      await page.screenshot({ path: 'agenda_filtered_therapy.png', fullPage: true });
  }

});
