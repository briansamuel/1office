const { chromium } = require('playwright');
const fs = require('fs');

/**
 * Comprehensive analysis of trial.1office.vn/work module
 * Including login, module scanning, and detailed page analysis
 */
async function analyzeWorkModule() {
  console.log('🚀 Starting comprehensive analysis of trial.1office.vn/work...');
  
  const browser = await chromium.launch({ 
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-web-security']
  });
  
  const page = await browser.newPage();
  
  // Set realistic user agent
  await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
  
  // Track network requests
  const networkLogs = [];
  const apiCalls = [];
  
  page.on('response', async (response) => {
    const url = response.url();
    const method = response.request().method();
    const status = response.status();
    const contentType = response.headers()['content-type'] || '';
    
    networkLogs.push({
      url,
      method,
      status,
      contentType,
      timestamp: new Date().toISOString()
    });
    
    // Track API calls
    if (url.includes('/api/') || contentType.includes('application/json')) {
      try {
        const responseBody = await response.text();
        apiCalls.push({
          url,
          method,
          status,
          requestHeaders: response.request().headers(),
          responseHeaders: response.headers(),
          body: responseBody.substring(0, 1000) // Limit body size
        });
      } catch (e) {
        // Ignore errors reading response body
      }
    }
  });
  
  const analysisData = {
    timestamp: new Date().toISOString(),
    loginAttempts: [],
    modules: [],
    pages: [],
    apiEndpoints: [],
    securityFeatures: [],
    uiComponents: [],
    errors: []
  };
  
  try {
    console.log('📍 Step 1: Navigating to trial.1office.vn/work...');
    
    // Try direct access to work module first
    const response = await page.goto('https://trial.1office.vn/work', { 
      waitUntil: 'networkidle',
      timeout: 30000
    });
    
    const currentUrl = page.url();
    console.log(`🔗 Current URL: ${currentUrl}`);
    console.log(`📊 Response Status: ${response.status()}`);
    
    // Take initial screenshot
    await page.screenshot({ path: 'work-module-initial.png', fullPage: true });
    
    // Check if redirected to login
    if (currentUrl.includes('/login') || currentUrl.includes('login')) {
      console.log('🔐 Redirected to login page, attempting authentication...');
      
      // Wait for login form
      await page.waitForSelector('#form-login, form[action*="login"], .login-form', { timeout: 10000 });
      
      // Demo credentials to try
      const credentials = [
        { username: 'demo', password: 'demo' },
        { username: 'admin', password: 'admin' },
        { username: 'test', password: 'test' },
        { username: 'trial', password: 'trial' },
        { username: 'guest', password: 'guest' },
        { username: 'work', password: 'work' },
        { username: 'demo@1office.vn', password: 'demo123' },
        { username: 'admin@1office.vn', password: 'admin123' },
        { username: 'work@1office.vn', password: 'work123' }
      ];
      
      let loginSuccess = false;
      
      for (const cred of credentials) {
        console.log(`🔑 Trying login: ${cred.username}`);
        
        try {
          // Find login fields (multiple possible selectors)
          const usernameSelectors = ['#username', 'input[name="username"]', 'input[name="email"]', 'input[type="email"]'];
          const passwordSelectors = ['#userpwd', '#password', 'input[name="password"]', 'input[type="password"]'];
          
          let usernameField = null;
          let passwordField = null;
          
          // Find username field
          for (const selector of usernameSelectors) {
            try {
              const field = page.locator(selector).first();
              if (await field.isVisible({ timeout: 1000 })) {
                usernameField = field;
                break;
              }
            } catch (e) { continue; }
          }
          
          // Find password field
          for (const selector of passwordSelectors) {
            try {
              const field = page.locator(selector).first();
              if (await field.isVisible({ timeout: 1000 })) {
                passwordField = field;
                break;
              }
            } catch (e) { continue; }
          }
          
          if (!usernameField || !passwordField) {
            console.log('  ❌ Login fields not found');
            continue;
          }
          
          // Fill credentials
          await usernameField.clear();
          await usernameField.fill(cred.username);
          await passwordField.clear();
          await passwordField.fill(cred.password);
          
          // Find and click submit button
          const submitSelectors = [
            'button[type="submit"]', 
            '.submit', 
            '.login-btn', 
            'input[type="submit"]',
            'button:has-text("Login")',
            'button:has-text("Đăng nhập")'
          ];
          
          let submitButton = null;
          for (const selector of submitSelectors) {
            try {
              const button = page.locator(selector).first();
              if (await button.isVisible({ timeout: 1000 })) {
                submitButton = button;
                break;
              }
            } catch (e) { continue; }
          }
          
          if (!submitButton) {
            console.log('  ❌ Submit button not found');
            continue;
          }
          
          // Submit login
          await submitButton.click();
          await page.waitForTimeout(3000);
          
          const newUrl = page.url();
          console.log(`  📍 URL after login: ${newUrl}`);
          
          // Check if login successful
          if (!newUrl.includes('/login') && newUrl !== currentUrl) {
            console.log('  🎉 Login successful!');
            loginSuccess = true;
            
            analysisData.loginAttempts.push({
              credentials: cred,
              success: true,
              finalUrl: newUrl
            });
            
            await page.screenshot({ path: 'work-module-logged-in.png', fullPage: true });
            break;
          } else {
            console.log('  ❌ Login failed');
            analysisData.loginAttempts.push({
              credentials: cred,
              success: false,
              finalUrl: newUrl
            });
          }
          
        } catch (error) {
          console.log(`  💥 Error during login: ${error.message}`);
          analysisData.errors.push({
            step: 'login',
            credentials: cred.username,
            error: error.message
          });
        }
      }
      
      if (!loginSuccess) {
        console.log('❌ All login attempts failed');
        // Continue analysis even without login
      }
    }
    
    console.log('\\n📊 Step 2: Analyzing current page structure...');
    
    // Get page information
    const pageInfo = await page.evaluate(() => {
      return {
        title: document.title,
        url: window.location.href,
        doctype: document.doctype ? document.doctype.name : 'unknown',
        charset: document.characterSet,
        lang: document.documentElement.lang,
        viewport: document.querySelector('meta[name="viewport"]')?.content,
        description: document.querySelector('meta[name="description"]')?.content,
        keywords: document.querySelector('meta[name="keywords"]')?.content
      };
    });
    
    console.log(`📄 Page Title: ${pageInfo.title}`);
    console.log(`🌐 Language: ${pageInfo.lang}`);
    console.log(`📱 Viewport: ${pageInfo.viewport}`);
    
    analysisData.pages.push({
      url: pageInfo.url,
      title: pageInfo.title,
      type: 'main',
      ...pageInfo
    });
    
    console.log('\\n🔍 Step 3: Scanning for modules and navigation...');
    
    // Scan for modules and navigation
    const moduleAnalysis = await page.evaluate(() => {
      const modules = [];
      const navigation = [];
      const menuItems = [];
      
      // Look for navigation elements
      const navSelectors = [
        'nav', '.nav', '.navigation', '.menu', '.sidebar', 
        '.main-menu', '.top-menu', '.side-menu', '[class*="nav"]',
        '[class*="menu"]', '[id*="nav"]', '[id*="menu"]'
      ];
      
      navSelectors.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach((el, index) => {
          if (el.textContent && el.textContent.trim().length > 0) {
            navigation.push({
              selector: selector,
              index: index,
              text: el.textContent.trim().substring(0, 200),
              className: el.className,
              id: el.id,
              tagName: el.tagName.toLowerCase()
            });
          }
        });
      });
      
      // Look for menu items and links
      const linkElements = document.querySelectorAll('a[href]');
      linkElements.forEach(link => {
        const href = link.href;
        const text = link.textContent ? link.textContent.trim() : '';
        
        if (text.length > 0 && href.length > 0) {
          menuItems.push({
            href: href,
            text: text.substring(0, 100),
            className: link.className,
            id: link.id
          });
        }
      });
      
      // Look for module indicators
      const moduleSelectors = [
        '[class*="module"]', '[id*="module"]', '[data-module]',
        '[class*="app"]', '[id*="app"]', '[data-app]',
        '.work', '.hrm', '.crm', '.warehouse', '.finance',
        '#work', '#hrm', '#crm', '#warehouse', '#finance'
      ];
      
      moduleSelectors.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach((el, index) => {
          modules.push({
            selector: selector,
            index: index,
            text: el.textContent ? el.textContent.trim().substring(0, 100) : '',
            className: el.className,
            id: el.id,
            tagName: el.tagName.toLowerCase()
          });
        });
      });
      
      return {
        modules: modules,
        navigation: navigation,
        menuItems: menuItems.slice(0, 50) // Limit to first 50 links
      };
    });
    
    console.log(`🧭 Navigation elements found: ${moduleAnalysis.navigation.length}`);
    console.log(`🔗 Menu items found: ${moduleAnalysis.menuItems.length}`);
    console.log(`📦 Module elements found: ${moduleAnalysis.modules.length}`);
    
    analysisData.modules = moduleAnalysis.modules;
    
    // Display interesting menu items
    console.log('\\n🔗 Interesting menu items:');
    moduleAnalysis.menuItems
      .filter(item => item.text.length > 2 && !item.href.includes('javascript:'))
      .slice(0, 20)
      .forEach(item => {
        console.log(`  📎 ${item.text} → ${item.href}`);
      });
    
    console.log('\\n🎨 Step 4: Analyzing UI components and frameworks...');
    
    // Analyze UI components and frameworks
    const uiAnalysis = await page.evaluate(() => {
      const frameworks = [];
      const components = [];
      
      // Check for frameworks
      if (window.Vue) frameworks.push(`Vue.js ${window.Vue.version || 'detected'}`);
      if (window.React) frameworks.push('React detected');
      if (window.Angular) frameworks.push('Angular detected');
      if (window.jQuery || window.$) frameworks.push(`jQuery ${window.jQuery ? window.jQuery.fn.jquery : 'detected'}`);
      if (window.bootstrap) frameworks.push('Bootstrap detected');
      if (window.Tailwind) frameworks.push('Tailwind CSS detected');
      
      // Check for 1Office specific
      if (window.Apps) frameworks.push('1Office Apps Framework');
      if (window.baseURL) frameworks.push('1Office Platform');
      if (window.LANG) frameworks.push(`Language: ${window.LANG}`);
      
      // Look for UI components
      const componentSelectors = [
        '.btn', '.button', '.card', '.modal', '.dropdown', '.tooltip',
        '.alert', '.badge', '.progress', '.spinner', '.loader',
        '.table', '.form', '.input', '.select', '.checkbox', '.radio',
        '.tab', '.accordion', '.carousel', '.slider', '.chart'
      ];
      
      componentSelectors.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        if (elements.length > 0) {
          components.push({
            selector: selector,
            count: elements.length,
            sample: elements[0] ? {
              className: elements[0].className,
              id: elements[0].id,
              text: elements[0].textContent ? elements[0].textContent.trim().substring(0, 50) : ''
            } : null
          });
        }
      });
      
      return {
        frameworks: frameworks,
        components: components
      };
    });
    
    console.log('🔧 Detected frameworks:');
    uiAnalysis.frameworks.forEach(fw => console.log(`  ✅ ${fw}`));
    
    console.log('\\n🎨 UI Components found:');
    uiAnalysis.components.slice(0, 15).forEach(comp => {
      console.log(`  🧩 ${comp.selector}: ${comp.count} elements`);
    });
    
    analysisData.uiComponents = uiAnalysis.components;
    
    console.log('\\n🔒 Step 5: Security analysis...');
    
    // Security analysis
    const securityAnalysis = await page.evaluate(() => {
      const security = {
        csrfTokens: [],
        forms: [],
        cookies: document.cookie,
        localStorage: {},
        sessionStorage: {},
        headers: {}
      };
      
      // Check for CSRF tokens
      const csrfSelectors = [
        'meta[name="csrf-token"]',
        'input[name="_token"]',
        'input[name="csrf_token"]',
        '[data-csrf]'
      ];
      
      csrfSelectors.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(el => {
          security.csrfTokens.push({
            selector: selector,
            value: el.content || el.value || el.dataset.csrf || 'found'
          });
        });
      });
      
      // Analyze forms
      const forms = document.querySelectorAll('form');
      forms.forEach((form, index) => {
        security.forms.push({
          index: index,
          action: form.action,
          method: form.method,
          hasCSRF: form.querySelector('input[name="_token"], input[name="csrf_token"]') !== null,
          inputCount: form.querySelectorAll('input').length
        });
      });
      
      // Check localStorage and sessionStorage
      try {
        for (let i = 0; i < localStorage.length; i++) {
          const key = localStorage.key(i);
          security.localStorage[key] = localStorage.getItem(key)?.substring(0, 100) || '';
        }
      } catch (e) {}
      
      try {
        for (let i = 0; i < sessionStorage.length; i++) {
          const key = sessionStorage.key(i);
          security.sessionStorage[key] = sessionStorage.getItem(key)?.substring(0, 100) || '';
        }
      } catch (e) {}
      
      return security;
    });
    
    console.log(`🛡️ CSRF tokens found: ${securityAnalysis.csrfTokens.length}`);
    console.log(`📝 Forms found: ${securityAnalysis.forms.length}`);
    console.log(`💾 LocalStorage items: ${Object.keys(securityAnalysis.localStorage).length}`);
    console.log(`🗃️ SessionStorage items: ${Object.keys(securityAnalysis.sessionStorage).length}`);
    
    analysisData.securityFeatures = securityAnalysis;
    
    console.log('\\n🌐 Step 6: Exploring available modules...');
    
    // Try to explore different modules
    const modulesToExplore = [
      '/work', '/hrm', '/crm', '/warehouse', '/finance', '/admin',
      '/dashboard', '/profile', '/settings', '/reports'
    ];
    
    for (const modulePath of modulesToExplore) {
      try {
        console.log(`🔍 Exploring module: ${modulePath}`);
        
        const moduleUrl = `https://trial.1office.vn${modulePath}`;
        const moduleResponse = await page.goto(moduleUrl, { 
          waitUntil: 'networkidle',
          timeout: 15000
        });
        
        if (moduleResponse && moduleResponse.status() === 200) {
          const modulePageInfo = await page.evaluate(() => ({
            title: document.title,
            url: window.location.href,
            hasContent: document.body.textContent.trim().length > 100,
            mainElements: document.querySelectorAll('main, .main, .content, .container').length
          }));
          
          console.log(`  ✅ ${modulePath}: ${modulePageInfo.title}`);
          
          analysisData.pages.push({
            url: modulePageInfo.url,
            title: modulePageInfo.title,
            type: 'module',
            path: modulePath,
            accessible: true,
            hasContent: modulePageInfo.hasContent
          });
          
          // Take screenshot of each accessible module
          await page.screenshot({ 
            path: `module-${modulePath.replace('/', '')}.png`,
            fullPage: true 
          });
          
        } else {
          console.log(`  ❌ ${modulePath}: Not accessible (${moduleResponse?.status()})`);
          analysisData.pages.push({
            url: moduleUrl,
            title: 'Not accessible',
            type: 'module',
            path: modulePath,
            accessible: false,
            status: moduleResponse?.status()
          });
        }
        
      } catch (error) {
        console.log(`  💥 ${modulePath}: Error - ${error.message}`);
        analysisData.errors.push({
          step: 'module_exploration',
          module: modulePath,
          error: error.message
        });
      }
    }
    
    console.log('\\n📡 Step 7: API endpoint discovery...');
    
    // Analyze API calls made
    console.log(`🔗 API calls detected: ${apiCalls.length}`);
    apiCalls.slice(0, 10).forEach(call => {
      console.log(`  📡 ${call.method} ${call.url} → ${call.status}`);
    });
    
    analysisData.apiEndpoints = apiCalls;
    
    // Final page analysis
    console.log('\\n📊 Step 8: Final comprehensive analysis...');
    
    const finalAnalysis = await page.evaluate(() => {
      return {
        totalElements: document.querySelectorAll('*').length,
        totalLinks: document.querySelectorAll('a[href]').length,
        totalImages: document.querySelectorAll('img').length,
        totalForms: document.querySelectorAll('form').length,
        totalInputs: document.querySelectorAll('input').length,
        totalButtons: document.querySelectorAll('button').length,
        totalScripts: document.querySelectorAll('script').length,
        totalStyles: document.querySelectorAll('style, link[rel="stylesheet"]').length,
        bodyClasses: document.body.className,
        bodyId: document.body.id,
        htmlLang: document.documentElement.lang,
        hasServiceWorker: 'serviceWorker' in navigator,
        userAgent: navigator.userAgent,
        platform: navigator.platform,
        cookieEnabled: navigator.cookieEnabled
      };
    });
    
    console.log('📈 Final statistics:');
    console.log(`  🏗️ Total elements: ${finalAnalysis.totalElements}`);
    console.log(`  🔗 Total links: ${finalAnalysis.totalLinks}`);
    console.log(`  🖼️ Total images: ${finalAnalysis.totalImages}`);
    console.log(`  📝 Total forms: ${finalAnalysis.totalForms}`);
    console.log(`  🔘 Total buttons: ${finalAnalysis.totalButtons}`);
    console.log(`  📜 Total scripts: ${finalAnalysis.totalScripts}`);
    
    // Compile final analysis
    analysisData.finalStats = finalAnalysis;
    analysisData.networkLogs = networkLogs.slice(0, 100); // Limit network logs
    
    // Save comprehensive analysis
    fs.writeFileSync('work-module-comprehensive-analysis.json', JSON.stringify(analysisData, null, 2));
    console.log('\\n💾 Comprehensive analysis saved to work-module-comprehensive-analysis.json');
    
    console.log('\\n✅ Analysis completed successfully!');
    
  } catch (error) {
    console.error('💥 Fatal error during analysis:', error.message);
    analysisData.errors.push({
      step: 'main',
      error: error.message,
      stack: error.stack
    });
    
    await page.screenshot({ path: 'work-module-error.png', fullPage: true });
  } finally {
    await browser.close();
  }
  
  return analysisData;
}

// Run the analysis
analyzeWorkModule()
  .then(data => {
    console.log('\\n🎉 Analysis complete! Check the generated files for detailed results.');
  })
  .catch(error => {
    console.error('💥 Analysis failed:', error.message);
    process.exit(1);
  });
