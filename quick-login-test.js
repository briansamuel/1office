const { chromium } = require('playwright');

async function quickLoginTest() {
  console.log('🚀 Starting quick login test for trial.1office.vn...');
  
  const browser = await chromium.launch({ 
    headless: false, // Hiển thị browser
    slowMo: 1000 // Chậm lại để dễ theo dõi
  });
  
  const page = await browser.newPage();
  
  try {
    // Điều hướng đến trang
    console.log('📍 Navigating to trial.1office.vn...');
    await page.goto('https://trial.1office.vn');
    await page.waitForLoadState('networkidle');
    
    // Chụp screenshot ban đầu
    await page.screenshot({ path: 'quick-test-initial.png', fullPage: true });
    console.log('📸 Initial screenshot saved');
    
    // Tìm form đăng nhập
    console.log('🔍 Looking for login form...');
    
    // Thử tìm các trường input
    const emailSelectors = [
      'input[type="email"]',
      'input[name="email"]',
      'input[name="username"]',
      'input[placeholder*="email" i]',
      '#email',
      '#username'
    ];
    
    const passwordSelectors = [
      'input[type="password"]',
      'input[name="password"]',
      '#password'
    ];
    
    let emailField = null;
    let passwordField = null;
    
    // Tìm email field
    for (const selector of emailSelectors) {
      try {
        const field = page.locator(selector).first();
        if (await field.isVisible()) {
          emailField = field;
          console.log(`✅ Found email field: ${selector}`);
          break;
        }
      } catch (e) {
        continue;
      }
    }
    
    // Tìm password field
    for (const selector of passwordSelectors) {
      try {
        const field = page.locator(selector).first();
        if (await field.isVisible()) {
          passwordField = field;
          console.log(`✅ Found password field: ${selector}`);
          break;
        }
      } catch (e) {
        continue;
      }
    }
    
    if (!emailField || !passwordField) {
      console.log('❌ Could not find login form fields');
      
      // In ra tất cả input fields có trên trang
      const allInputs = await page.locator('input').all();
      console.log('\n📋 All input fields found:');
      for (let i = 0; i < allInputs.length; i++) {
        const input = allInputs[i];
        const type = await input.getAttribute('type');
        const name = await input.getAttribute('name');
        const id = await input.getAttribute('id');
        const placeholder = await input.getAttribute('placeholder');
        console.log(`  ${i + 1}. type="${type}" name="${name}" id="${id}" placeholder="${placeholder}"`);
      }
      
      return;
    }
    
    // Thử đăng nhập với credentials demo
    const testCredentials = [
      { email: 'demo@1office.vn', password: 'demo123456' },
      { email: 'admin@1office.vn', password: 'admin123456' },
      { email: 'test@1office.vn', password: 'test123456' },
      { email: 'demo', password: 'demo' }
    ];
    
    for (const creds of testCredentials) {
      console.log(`\n🔐 Trying login with: ${creds.email}`);
      
      // Clear và fill fields
      await emailField.clear();
      await emailField.fill(creds.email);
      
      await passwordField.clear();
      await passwordField.fill(creds.password);
      
      // Tìm nút đăng nhập
      const loginButtonSelectors = [
        'button[type="submit"]',
        'input[type="submit"]',
        'button:has-text("Đăng nhập")',
        'button:has-text("Login")',
        '.login-btn',
        '#login-button'
      ];
      
      let loginButton = null;
      for (const selector of loginButtonSelectors) {
        try {
          const button = page.locator(selector).first();
          if (await button.isVisible()) {
            loginButton = button;
            console.log(`✅ Found login button: ${selector}`);
            break;
          }
        } catch (e) {
          continue;
        }
      }
      
      if (!loginButton) {
        console.log('❌ Could not find login button');
        continue;
      }
      
      // Click đăng nhập
      await loginButton.click();
      await page.waitForTimeout(3000); // Chờ 3 giây
      
      // Kiểm tra kết quả
      const currentUrl = page.url();
      console.log(`📍 Current URL after login: ${currentUrl}`);
      
      // Chụp screenshot sau khi đăng nhập
      await page.screenshot({ 
        path: `quick-test-after-login-${creds.email.replace('@', '-').replace('.', '-')}.png`, 
        fullPage: true 
      });
      
      // Kiểm tra xem có đăng nhập thành công không
      if (currentUrl !== 'https://trial.1office.vn' && !currentUrl.includes('/login')) {
        console.log('🎉 Login appears successful! URL changed.');
        
        // Kiểm tra thêm các dấu hiệu thành công
        const successIndicators = [
          '.dashboard',
          '.main-menu',
          '.user-info',
          '.profile',
          '.navbar'
        ];
        
        for (const indicator of successIndicators) {
          try {
            const element = page.locator(indicator);
            if (await element.isVisible({ timeout: 2000 })) {
              console.log(`✅ Found success indicator: ${indicator}`);
              console.log('🎊 LOGIN SUCCESSFUL!');
              return;
            }
          } catch (e) {
            continue;
          }
        }
      }
      
      // Kiểm tra thông báo lỗi
      const errorSelectors = [
        '.error-message',
        '.alert-danger',
        '.text-red',
        ':has-text("sai")',
        ':has-text("incorrect")'
      ];
      
      for (const selector of errorSelectors) {
        try {
          const errorElement = page.locator(selector);
          if (await errorElement.isVisible({ timeout: 2000 })) {
            const errorText = await errorElement.textContent();
            console.log(`❌ Error message: ${errorText}`);
            break;
          }
        } catch (e) {
          continue;
        }
      }
      
      // Quay lại trang login để thử credentials tiếp theo
      if (testCredentials.indexOf(creds) < testCredentials.length - 1) {
        await page.goto('https://trial.1office.vn');
        await page.waitForLoadState('networkidle');
      }
    }
    
    console.log('❌ All login attempts failed');
    
  } catch (error) {
    console.error('💥 Error during test:', error.message);
    await page.screenshot({ path: 'quick-test-error.png', fullPage: true });
  } finally {
    console.log('🏁 Test completed. Browser will close in 5 seconds...');
    await page.waitForTimeout(5000);
    await browser.close();
  }
}

// Chạy test
quickLoginTest().catch(console.error);
