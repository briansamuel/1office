const { chromium } = require('playwright');
const fs = require('fs');

async function loginToTrialOffice() {
  console.log('🚀 Starting Playwright login to trial.1office.vn...');
  
  const browser = await chromium.launch({
    headless: true, // Chạy headless để tránh GUI dependencies
    args: ['--no-sandbox', '--disable-setuid-sandbox'] // Thêm args cho container
  });
  
  const page = await browser.newPage();
  
  // Intercept network requests để phân tích
  const networkLogs = [];
  page.on('response', async (response) => {
    networkLogs.push({
      url: response.url(),
      status: response.status(),
      contentType: response.headers()['content-type'] || '',
      method: response.request().method()
    });
  });
  
  try {
    console.log('📍 Navigating to trial.1office.vn...');
    await page.goto('https://trial.1office.vn', { 
      waitUntil: 'networkidle',
      timeout: 30000
    });
    
    // Chụp screenshot ban đầu
    await page.screenshot({ path: 'playwright-login-initial.png', fullPage: true });
    console.log('📸 Initial screenshot saved');
    
    // Kiểm tra URL hiện tại
    const currentUrl = page.url();
    console.log(`🔗 Current URL: ${currentUrl}`);
    
    // Phân tích trang login
    console.log('\\n🔍 Analyzing login page...');
    
    // Kiểm tra form login
    const loginForm = await page.locator('#form-login').first();
    const isFormVisible = await loginForm.isVisible();
    console.log(`📝 Login form visible: ${isFormVisible}`);
    
    if (!isFormVisible) {
      throw new Error('Login form not found');
    }
    
    // Phân tích các trường input
    const usernameField = await page.locator('#username').first();
    const passwordField = await page.locator('#userpwd').first();
    const submitButton = await page.locator('button[type="submit"], .submit').first();
    
    console.log(`👤 Username field found: ${await usernameField.isVisible()}`);
    console.log(`🔒 Password field found: ${await passwordField.isVisible()}`);
    console.log(`🔘 Submit button found: ${await submitButton.isVisible()}`);
    
    // Lấy thông tin về các trường
    const usernameInfo = {
      name: await usernameField.getAttribute('name'),
      id: await usernameField.getAttribute('id'),
      type: await usernameField.getAttribute('type'),
      required: await usernameField.getAttribute('required') !== null
    };
    
    const passwordInfo = {
      name: await passwordField.getAttribute('name'),
      id: await passwordField.getAttribute('id'),
      type: await passwordField.getAttribute('type'),
      required: await passwordField.getAttribute('required') !== null
    };
    
    console.log('\\n📋 Form field details:');
    console.log(`Username: ${JSON.stringify(usernameInfo)}`);
    console.log(`Password: ${JSON.stringify(passwordInfo)}`);
    
    // Phân tích CSS và JS
    console.log('\\n🎨 Analyzing CSS and JavaScript...');
    
    // Lấy tất cả stylesheets
    const stylesheets = await page.evaluate(() => {
      const links = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
      return links.map(link => ({
        href: link.href,
        media: link.media || 'all'
      }));
    });
    
    console.log(`CSS files loaded: ${stylesheets.length}`);
    stylesheets.forEach((css, index) => {
      console.log(`  ${index + 1}. ${css.href} (${css.media})`);
    });
    
    // Lấy tất cả scripts
    const scripts = await page.evaluate(() => {
      const scriptTags = Array.from(document.querySelectorAll('script'));
      return scriptTags.map(script => ({
        src: script.src || 'inline',
        type: script.type || 'text/javascript',
        async: script.async,
        defer: script.defer,
        contentLength: script.src ? 0 : (script.textContent || '').length
      }));
    });
    
    console.log(`JavaScript files: ${scripts.length}`);
    scripts.forEach((script, index) => {
      if (script.src !== 'inline') {
        console.log(`  ${index + 1}. ${script.src} (${script.type}, async: ${script.async}, defer: ${script.defer})`);
      } else {
        console.log(`  ${index + 1}. Inline script (${script.contentLength} chars)`);
      }
    });
    
    // Phân tích framework detection
    const frameworks = await page.evaluate(() => {
      const detected = [];
      
      // Check global variables
      if (window.Vue) detected.push(`Vue.js ${window.Vue.version || 'detected'}`);
      if (window.React) detected.push('React detected');
      if (window.Angular) detected.push('Angular detected');
      if (window.jQuery || window.$) detected.push(`jQuery ${window.jQuery ? window.jQuery.fn.jquery : 'detected'}`);
      if (window.bootstrap) detected.push('Bootstrap detected');
      
      // Check for 1Office specific
      if (window.baseURL) detected.push('1Office Platform detected');
      if (window.Apps) detected.push('1Office Apps framework detected');
      if (window.LANG) detected.push(`Language: ${window.LANG}`);
      if (window.VERSION) detected.push(`Version: ${window.VERSION}`);
      
      return detected;
    });
    
    console.log('\\n🔧 Detected frameworks and libraries:');
    if (frameworks.length > 0) {
      frameworks.forEach(framework => console.log(`  ✅ ${framework}`));
    } else {
      console.log('  ❌ No frameworks detected in global scope');
    }
    
    // Thử đăng nhập với các credentials demo
    const demoCredentials = [
      { username: 'demo', password: 'demo' },
      { username: 'admin', password: 'admin' },
      { username: 'test', password: 'test' },
      { username: 'demo@1office.vn', password: 'demo123456' },
      { username: 'admin@1office.vn', password: 'admin123456' },
      { username: 'trial', password: 'trial123' },
      { username: 'guest', password: 'guest123' }
    ];
    
    console.log('\\n🔐 Attempting login with demo credentials...');
    
    for (const creds of demoCredentials) {
      console.log(`\\n🔑 Trying: ${creds.username} / ${creds.password}`);
      
      try {
        // Clear và fill username
        await usernameField.clear();
        await usernameField.fill(creds.username);
        
        // Clear và fill password
        await passwordField.clear();
        await passwordField.fill(creds.password);
        
        // Chụp screenshot trước khi submit
        await page.screenshot({ 
          path: `playwright-before-login-${creds.username.replace('@', '-').replace('.', '-')}.png`,
          fullPage: true 
        });
        
        // Click submit
        await submitButton.click();
        
        // Chờ response
        await page.waitForTimeout(3000);
        
        // Kiểm tra URL sau khi login
        const newUrl = page.url();
        console.log(`  📍 URL after login: ${newUrl}`);
        
        // Kiểm tra xem có đăng nhập thành công không
        if (newUrl !== currentUrl && !newUrl.includes('/login')) {
          console.log('  🎉 Login appears successful! URL changed.');
          
          // Chụp screenshot thành công
          await page.screenshot({ 
            path: `playwright-login-success-${creds.username.replace('@', '-').replace('.', '-')}.png`,
            fullPage: true 
          });
          
          // Phân tích trang sau khi đăng nhập
          const pageTitle = await page.title();
          console.log(`  📄 Page title after login: ${pageTitle}`);
          
          // Tìm các element cho thấy đăng nhập thành công
          const successIndicators = [
            '.dashboard',
            '.main-menu',
            '.user-info',
            '.profile',
            '.navbar',
            '.sidebar',
            '[class*="dashboard"]',
            '[class*="main"]'
          ];
          
          for (const indicator of successIndicators) {
            try {
              const element = page.locator(indicator);
              if (await element.isVisible({ timeout: 2000 })) {
                console.log(`  ✅ Found success indicator: ${indicator}`);
                break;
              }
            } catch (e) {
              continue;
            }
          }
          
          // Phân tích trang dashboard
          console.log('\\n📊 Analyzing dashboard page...');
          
          const dashboardAnalysis = await page.evaluate(() => {
            return {
              title: document.title,
              url: window.location.href,
              bodyClasses: document.body.className,
              mainElements: Array.from(document.querySelectorAll('main, .main, .content, .dashboard')).length,
              navigationElements: Array.from(document.querySelectorAll('nav, .nav, .menu, .sidebar')).length,
              userElements: Array.from(document.querySelectorAll('.user, .profile, .avatar')).length
            };
          });
          
          console.log('Dashboard analysis:', JSON.stringify(dashboardAnalysis, null, 2));
          
          // Lưu thông tin thành công
          const successData = {
            credentials: creds,
            loginUrl: currentUrl,
            dashboardUrl: newUrl,
            dashboardAnalysis,
            timestamp: new Date().toISOString()
          };
          
          fs.writeFileSync('playwright-login-success.json', JSON.stringify(successData, null, 2));
          console.log('\\n💾 Success data saved to playwright-login-success.json');
          
          break; // Thoát khỏi loop nếu thành công
          
        } else {
          console.log('  ❌ Login failed or redirected back to login');
          
          // Tìm thông báo lỗi
          const errorSelectors = [
            '.error',
            '.alert-danger',
            '.text-red',
            '.invalid-feedback',
            '[class*="error"]',
            '.notification-error'
          ];
          
          for (const selector of errorSelectors) {
            try {
              const errorElement = page.locator(selector);
              if (await errorElement.isVisible({ timeout: 1000 })) {
                const errorText = await errorElement.textContent();
                console.log(`  ⚠️ Error message: ${errorText?.trim()}`);
                break;
              }
            } catch (e) {
              continue;
            }
          }
          
          // Chụp screenshot lỗi
          await page.screenshot({ 
            path: `playwright-login-failed-${creds.username.replace('@', '-').replace('.', '-')}.png`,
            fullPage: true 
          });
        }
        
      } catch (error) {
        console.log(`  💥 Error during login attempt: ${error.message}`);
      }
      
      // Quay lại trang login nếu cần
      if (page.url() !== currentUrl) {
        await page.goto(currentUrl);
        await page.waitForLoadState('networkidle');
      }
    }
    
    // Lưu network logs
    fs.writeFileSync('playwright-network-logs.json', JSON.stringify(networkLogs, null, 2));
    console.log('\\n📡 Network logs saved to playwright-network-logs.json');
    
    // Lưu phân tích tổng thể
    const fullAnalysis = {
      url: currentUrl,
      pageTitle: await page.title(),
      formAnalysis: {
        usernameField: usernameInfo,
        passwordField: passwordInfo,
        formVisible: isFormVisible
      },
      stylesheets,
      scripts,
      frameworks,
      networkLogs: networkLogs.slice(0, 20), // Chỉ lưu 20 requests đầu
      timestamp: new Date().toISOString()
    };
    
    fs.writeFileSync('playwright-full-analysis.json', JSON.stringify(fullAnalysis, null, 2));
    console.log('💾 Full analysis saved to playwright-full-analysis.json');
    
    console.log('\\n✅ Analysis completed!');
    
  } catch (error) {
    console.error('💥 Error during analysis:', error.message);
    await page.screenshot({ path: 'playwright-error.png', fullPage: true });
  } finally {
    console.log('\\n🏁 Closing browser in 5 seconds...');
    await page.waitForTimeout(5000);
    await browser.close();
  }
}

// Run the analysis
loginToTrialOffice().catch(console.error);
