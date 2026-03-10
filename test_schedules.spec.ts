import { test, expect } from '@playwright/test';

test('Professional Schedules and Auto Username Generation', async ({ page }) => {
  // 1. Login as Admin
  await page.goto('http://localhost:8000/?page=login');
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'admin123'); // assuming standard
  await page.click('button[type="submit"]');

  // Verify dashboard loaded OR branch selection
  const title = await page.title();
  
  // If we are at branch selection, click the first one we find
  if (page.url().includes('branch_select')) {
      await page.locator('a.btn-primary').first().click();
      // wait for dashboard
      await page.waitForURL('**/?page=dashboard');
  }

  // 2. Go to Professionals -> New
  await page.goto('http://localhost:8000/?page=professionals_new');
  
  // 3. Test Auto User Generation
  await page.fill('input[name="name"]', 'Carlos Eduardo da Silva');
  // Trigger blur or input to run js
  await page.locator('input[name="username"]').click();
  // Check if username was populated
  const usernameValue = await page.locator('input[name="username"]').inputValue();
  console.log("Auto-generated username:", usernameValue);

  // Fill password
  await page.fill('input[name="password"]', 'senha123');

  // Fill Schedule - Check Mon, Wed, Fri
  await page.check('input[name="schedules[1][active]"]'); // Monday
  await page.check('input[name="schedules[3][active]"]'); // Wednesday
  await page.check('input[name="schedules[5][active]"]'); // Friday

  // Change times just to test logic
  await page.fill('input[name="schedules[1][start]"]', '09:00');
  await page.fill('input[name="schedules[1][end]"]', '12:00');

  // Ensure screenshots to see UI layout
  await page.screenshot({ path: 'test_schedules_form.png', fullPage: true });

  await page.click('button[type="submit"]');

  // Verify redirect
  await page.waitForURL('**/?page=professionals');
  await page.screenshot({ path: 'test_professionals_list.png', fullPage: true });

  // 4. View Agenda filtering by new prof
  await page.goto('http://localhost:8000/?page=schedule');
  
  // Select Carlos in the filter dropdown (we assume he was added successfully).
  const profSelect = page.locator('select[name="prof_filter"]');
  await profSelect.selectOption({ label: 'Carlos Eduardo da Silva' });
  
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: 'test_calendar_filtered_schedules.png', fullPage: true });
});
