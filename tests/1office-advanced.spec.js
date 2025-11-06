const { test, expect } = require('@playwright/test');
const LoginPage = require('./pages/LoginPage');

test.describe('1Office Advanced Login Tests', () => {
  let loginPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    await loginPage.goto();
  });

  test('should login with demo credentials', async ({ page }) => {
    // Thử với các credentials demo phổ biến
    const demoCredentials = [
      { email: 'demo@1office.vn', password: 'demo123456' },
      { email: 'admin@1office.vn', password: 'admin123456' },
      { email: 'test@1office.vn', password: 'test123456' },
      { email: 'demo', password: 'demo' },
      { email: 'admin', password: 'admin' }
    ];

    for (const creds of demoCredentials) {
      console.log(`Thử đăng nhập với: ${creds.email}`);
      
      try {
        await loginPage.login(creds.email, creds.password);
        
        if (await loginPage.isLoginSuccessful()) {
          console.log(`✅ Đăng nhập thành công với: ${creds.email}`);
          
          // Chụp screenshot thành công
          await page.screenshot({ 
            path: `screenshots/login-success-${Date.now()}.png`,
            fullPage: true 
          });
          
          return; // Thoát khỏi test nếu thành công
        } else {
          const errorMsg = await loginPage.getErrorMessage();
          console.log(`❌ Đăng nhập thất bại với ${creds.email}: ${errorMsg || 'Không có thông báo lỗi'}`);
          
          // Quay lại trang login để thử credentials tiếp theo
          await loginPage.goto();
        }
      } catch (error) {
        console.log(`❌ Lỗi khi thử ${creds.email}: ${error.message}`);
        await loginPage.goto();
      }
    }

    // Nếu không có credentials nào thành công
    await page.screenshot({ 
      path: `screenshots/all-login-failed-${Date.now()}.png`,
      fullPage: true 
    });
    
    throw new Error('Không thể đăng nhập với bất kỳ credentials demo nào');
  });

  test('should analyze login form structure', async ({ page }) => {
    const formInfo = await loginPage.captureLoginForm();
    
    console.log('=== THÔNG TIN FORM ĐĂNG NHẬP ===');
    console.log(`URL: ${formInfo.url}`);
    console.log(`Title: ${formInfo.title}`);
    console.log(`Số form: ${formInfo.forms}`);
    
    console.log('\n=== INPUT FIELDS ===');
    formInfo.inputs.forEach((input, index) => {
      console.log(`Input ${index + 1}:`);
      console.log(`  Type: ${input.type}`);
      console.log(`  Name: ${input.name}`);
      console.log(`  ID: ${input.id}`);
      console.log(`  Placeholder: ${input.placeholder}`);
      console.log(`  Class: ${input.class}`);
      console.log(`  Visible: ${input.visible}`);
      console.log('---');
    });
    
    console.log('\n=== BUTTONS ===');
    formInfo.buttons.forEach((button, index) => {
      console.log(`Button ${index + 1}:`);
      console.log(`  Type: ${button.type}`);
      console.log(`  Text: ${button.text}`);
      console.log(`  Class: ${button.class}`);
      console.log(`  Visible: ${button.visible}`);
      console.log('---');
    });

    // Lưu thông tin vào file
    const fs = require('fs');
    fs.writeFileSync('login-form-analysis.json', JSON.stringify(formInfo, null, 2));
  });

  test('should handle different login scenarios', async ({ page }) => {
    // Test 1: Empty credentials
    console.log('Test 1: Empty credentials');
    await loginPage.login('', '');
    await page.waitForTimeout(2000);
    
    let errorMsg = await loginPage.getErrorMessage();
    console.log(`Empty credentials error: ${errorMsg || 'No error message'}`);

    // Test 2: Only email
    console.log('Test 2: Only email');
    await loginPage.goto();
    await loginPage.login('test@example.com', '');
    await page.waitForTimeout(2000);
    
    errorMsg = await loginPage.getErrorMessage();
    console.log(`Only email error: ${errorMsg || 'No error message'}`);

    // Test 3: Only password
    console.log('Test 3: Only password');
    await loginPage.goto();
    await loginPage.login('', 'password123');
    await page.waitForTimeout(2000);
    
    errorMsg = await loginPage.getErrorMessage();
    console.log(`Only password error: ${errorMsg || 'No error message'}`);

    // Test 4: Invalid format email
    console.log('Test 4: Invalid email format');
    await loginPage.goto();
    await loginPage.login('invalid-email', 'password123');
    await page.waitForTimeout(2000);
    
    errorMsg = await loginPage.getErrorMessage();
    console.log(`Invalid email error: ${errorMsg || 'No error message'}`);
  });

  test('should check for common security features', async ({ page }) => {
    console.log('=== KIỂM TRA TÍNH NĂNG BẢO MẬT ===');

    // Kiểm tra CAPTCHA
    const captchaSelectors = [
      '.captcha',
      '.recaptcha',
      '#captcha',
      '[class*="captcha"]',
      'iframe[src*="recaptcha"]'
    ];

    let hasCaptcha = false;
    for (const selector of captchaSelectors) {
      try {
        const element = page.locator(selector);
        if (await element.isVisible({ timeout: 1000 })) {
          hasCaptcha = true;
          console.log(`✅ Tìm thấy CAPTCHA: ${selector}`);
          break;
        }
      } catch (e) {
        continue;
      }
    }

    if (!hasCaptcha) {
      console.log('❌ Không tìm thấy CAPTCHA');
    }

    // Kiểm tra Remember Me
    const rememberMeSelectors = [
      'input[type="checkbox"]',
      ':has-text("Remember")',
      ':has-text("Ghi nhớ")',
      ':has-text("Nhớ mật khẩu")'
    ];

    let hasRememberMe = false;
    for (const selector of rememberMeSelectors) {
      try {
        const element = page.locator(selector);
        if (await element.isVisible({ timeout: 1000 })) {
          hasRememberMe = true;
          console.log(`✅ Tìm thấy Remember Me: ${selector}`);
          break;
        }
      } catch (e) {
        continue;
      }
    }

    if (!hasRememberMe) {
      console.log('❌ Không tìm thấy Remember Me');
    }

    // Kiểm tra Forgot Password
    const forgotPasswordSelectors = [
      ':has-text("Forgot")',
      ':has-text("Quên mật khẩu")',
      ':has-text("Forgot password")',
      '.forgot-password',
      '#forgot-password'
    ];

    let hasForgotPassword = false;
    for (const selector of forgotPasswordSelectors) {
      try {
        const element = page.locator(selector);
        if (await element.isVisible({ timeout: 1000 })) {
          hasForgotPassword = true;
          console.log(`✅ Tìm thấy Forgot Password: ${selector}`);
          break;
        }
      } catch (e) {
        continue;
      }
    }

    if (!hasForgotPassword) {
      console.log('❌ Không tìm thấy Forgot Password');
    }

    // Kiểm tra HTTPS
    const isHttps = page.url().startsWith('https://');
    console.log(`${isHttps ? '✅' : '❌'} HTTPS: ${isHttps}`);
  });
});
