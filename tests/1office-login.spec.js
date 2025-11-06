const { test, expect } = require('@playwright/test');

test.describe('1Office Login Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Điều hướng đến trang đăng nhập
    await page.goto('https://trial.1office.vn');
  });

  test('should login successfully with valid credentials', async ({ page }) => {
    // Chờ trang load hoàn toàn
    await page.waitForLoadState('networkidle');

    // Tìm và điền thông tin đăng nhập
    // Thử các selector phổ biến cho email/username
    const emailSelectors = [
      'input[type="email"]',
      'input[name="email"]',
      'input[name="username"]',
      'input[placeholder*="email" i]',
      'input[placeholder*="tài khoản" i]',
      '#email',
      '#username',
      '.email-input',
      '.username-input'
    ];

    const passwordSelectors = [
      'input[type="password"]',
      'input[name="password"]',
      'input[placeholder*="password" i]',
      'input[placeholder*="mật khẩu" i]',
      '#password',
      '.password-input'
    ];

    // Tìm trường email/username
    let emailField = null;
    for (const selector of emailSelectors) {
      try {
        emailField = await page.locator(selector).first();
        if (await emailField.isVisible()) {
          break;
        }
      } catch (e) {
        continue;
      }
    }

    // Tìm trường password
    let passwordField = null;
    for (const selector of passwordSelectors) {
      try {
        passwordField = await page.locator(selector).first();
        if (await passwordField.isVisible()) {
          break;
        }
      } catch (e) {
        continue;
      }
    }

    if (!emailField || !passwordField) {
      throw new Error('Không tìm thấy form đăng nhập');
    }

    // Điền thông tin đăng nhập
    await emailField.fill('demo@1office.vn'); // Thay đổi email phù hợp
    await passwordField.fill('demo123456'); // Thay đổi password phù hợp

    // Tìm và click nút đăng nhập
    const loginButtonSelectors = [
      'button[type="submit"]',
      'input[type="submit"]',
      'button:has-text("Đăng nhập")',
      'button:has-text("Login")',
      'button:has-text("Sign in")',
      '.login-btn',
      '.btn-login',
      '#login-button',
      '.submit-btn'
    ];

    let loginButton = null;
    for (const selector of loginButtonSelectors) {
      try {
        loginButton = await page.locator(selector).first();
        if (await loginButton.isVisible()) {
          break;
        }
      } catch (e) {
        continue;
      }
    }

    if (!loginButton) {
      throw new Error('Không tìm thấy nút đăng nhập');
    }

    // Click đăng nhập
    await loginButton.click();

    // Chờ điều hướng sau khi đăng nhập
    await page.waitForLoadState('networkidle');

    // Kiểm tra đăng nhập thành công
    // Thử các cách kiểm tra khác nhau
    const successIndicators = [
      // URL thay đổi
      () => expect(page.url()).not.toBe('https://trial.1office.vn'),
      
      // Có dashboard hoặc menu
      () => expect(page.locator('.dashboard, .main-menu, .sidebar, .navbar')).toBeVisible(),
      
      // Có thông tin user
      () => expect(page.locator('.user-info, .profile, .avatar')).toBeVisible(),
      
      // Không còn form login
      () => expect(page.locator('input[type="password"]')).not.toBeVisible(),
    ];

    // Thử từng cách kiểm tra
    let loginSuccess = false;
    for (const check of successIndicators) {
      try {
        await check();
        loginSuccess = true;
        break;
      } catch (e) {
        continue;
      }
    }

    if (!loginSuccess) {
      // Chụp screenshot để debug
      await page.screenshot({ path: 'login-failed.png', fullPage: true });
      throw new Error('Đăng nhập không thành công');
    }

    console.log('✅ Đăng nhập thành công!');
  });

  test('should handle login with invalid credentials', async ({ page }) => {
    await page.waitForLoadState('networkidle');

    // Điền thông tin sai
    await page.fill('input[type="email"], input[name="email"], input[name="username"]', 'invalid@email.com');
    await page.fill('input[type="password"]', 'wrongpassword');

    // Click đăng nhập
    await page.click('button[type="submit"], .login-btn, button:has-text("Đăng nhập")');

    // Chờ và kiểm tra thông báo lỗi
    await page.waitForTimeout(2000);

    const errorSelectors = [
      '.error-message',
      '.alert-danger',
      '.text-red',
      '.invalid-feedback',
      '[class*="error"]',
      ':has-text("sai"), :has-text("incorrect"), :has-text("invalid")'
    ];

    let errorFound = false;
    for (const selector of errorSelectors) {
      try {
        const errorElement = page.locator(selector);
        if (await errorElement.isVisible()) {
          errorFound = true;
          console.log('❌ Thông báo lỗi hiển thị:', await errorElement.textContent());
          break;
        }
      } catch (e) {
        continue;
      }
    }

    expect(errorFound).toBe(true);
  });

  test('should capture login form structure', async ({ page }) => {
    await page.waitForLoadState('networkidle');

    // Chụp screenshot toàn trang
    await page.screenshot({ path: 'login-page.png', fullPage: true });

    // Lấy thông tin về form
    const forms = await page.locator('form').count();
    console.log(`Số lượng form: ${forms}`);

    // Lấy tất cả input fields
    const inputs = await page.locator('input').all();
    console.log('Input fields found:');
    for (let i = 0; i < inputs.length; i++) {
      const input = inputs[i];
      const type = await input.getAttribute('type');
      const name = await input.getAttribute('name');
      const placeholder = await input.getAttribute('placeholder');
      const id = await input.getAttribute('id');
      
      console.log(`  Input ${i + 1}: type="${type}", name="${name}", placeholder="${placeholder}", id="${id}"`);
    }

    // Lấy tất cả buttons
    const buttons = await page.locator('button').all();
    console.log('Buttons found:');
    for (let i = 0; i < buttons.length; i++) {
      const button = buttons[i];
      const text = await button.textContent();
      const type = await button.getAttribute('type');
      
      console.log(`  Button ${i + 1}: text="${text?.trim()}", type="${type}"`);
    }
  });
});
