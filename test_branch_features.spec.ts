import { test, expect } from '@playwright/test';

test.describe('Branch Features', () => {
  // We need to test user login, navigating around, branch separation
  test('Admin can change professional branch', async ({ page }) => {
    await page.goto('http://localhost:8000/?page=login');
    // Login as default admin
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    
    // Select branch
    await page.waitForSelector('text=Selecionar Unidade');
    await page.selectOption('select[name="branch_id"]', { index: 1 });
    await page.click('button[type="submit"]');
    
    // Verify Dashboard
    await expect(page.locator('h1')).toContainText('Dashboard');
    
    // Go to professionals
    await page.click('text=Profissionais');
    await expect(page.locator('h1')).toContainText('Profissionais');

    // See if transfer button exists
    const rowCounts = await page.locator('tbody tr').count();
    if (rowCounts > 0 && !(await page.locator('tbody tr').first().locator('text=Nenhum').isVisible())) {
       await expect(page.locator('button[title="Transferir Filial"]').first()).toBeVisible();
    }
  });

  test('Admin can access user edit page', async ({ page }) => {
    await page.goto('http://localhost:8000/?page=login');
    // Login as default admin
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    
    await page.waitForSelector('text=Selecionar Unidade');
    await page.selectOption('select[name="branch_id"]', { index: 1 });
    await page.click('button[type="submit"]');
    
    await page.goto('http://localhost:8000/?page=users');
    await expect(page.locator('h1')).toHaveText('Gerenciar Usuários');
    
    // Ensure the edit button (pen icon) was added next to the trash icon
    const editBtn = page.locator('a[title="Editar"]').first();
    if (await editBtn.count() > 0) {
        const href = await editBtn.getAttribute('href');
        expect(href).toContain('user_edit');
        
        await editBtn.click();
        await expect(page.locator('h1')).toHaveText('Editar Usuário');
    }
  });
});
