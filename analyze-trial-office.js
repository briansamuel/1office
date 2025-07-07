const { chromium } = require('playwright');
const fs = require('fs');

async function analyzeTrialOffice() {
  console.log('🚀 Starting comprehensive analysis of trial.1office.vn...');
  
  const browser = await chromium.launch({ 
    headless: true // Chạy headless để tránh GUI dependencies
  });
  
  const page = await browser.newPage();
  
  // Intercept network requests để phân tích resources
  const resources = {
    css: [],
    js: [],
    images: [],
    fonts: [],
    api: [],
    other: []
  };
  
  page.on('response', async (response) => {
    const url = response.url();
    const contentType = response.headers()['content-type'] || '';
    
    if (contentType.includes('text/css') || url.endsWith('.css')) {
      resources.css.push({
        url,
        status: response.status(),
        size: response.headers()['content-length'] || 'unknown'
      });
    } else if (contentType.includes('javascript') || url.endsWith('.js')) {
      resources.js.push({
        url,
        status: response.status(),
        size: response.headers()['content-length'] || 'unknown'
      });
    } else if (contentType.includes('image/') || /\.(png|jpg|jpeg|gif|svg|webp)$/i.test(url)) {
      resources.images.push({
        url,
        status: response.status(),
        size: response.headers()['content-length'] || 'unknown'
      });
    } else if (contentType.includes('font/') || /\.(woff|woff2|ttf|eot)$/i.test(url)) {
      resources.fonts.push({
        url,
        status: response.status(),
        size: response.headers()['content-length'] || 'unknown'
      });
    } else if (url.includes('/api/') || contentType.includes('application/json')) {
      resources.api.push({
        url,
        status: response.status(),
        method: response.request().method(),
        size: response.headers()['content-length'] || 'unknown'
      });
    } else {
      resources.other.push({
        url,
        status: response.status(),
        contentType,
        size: response.headers()['content-length'] || 'unknown'
      });
    }
  });
  
  try {
    // Navigate to the site
    console.log('📍 Navigating to trial.1office.vn...');
    const response = await page.goto('https://trial.1office.vn', { 
      waitUntil: 'networkidle',
      timeout: 30000
    });
    
    if (!response || !response.ok()) {
      throw new Error(`Failed to load page. Status: ${response ? response.status() : 'No response'}`);
    }
    
    // Take screenshot
    await page.screenshot({ path: 'trial-office-analysis.png', fullPage: true });
    console.log('📸 Screenshot saved as trial-office-analysis.png');
    
    // Basic page info
    const pageInfo = {
      title: await page.title(),
      url: page.url(),
      timestamp: new Date().toISOString()
    };
    
    console.log(`📄 Page Title: ${pageInfo.title}`);
    console.log(`🔗 Current URL: ${pageInfo.url}`);
    
    // Wait a bit more for all resources to load
    await page.waitForTimeout(3000);
    
    // Analyze HTML structure
    console.log('\\n🏗️ ANALYZING HTML STRUCTURE...');
    
    const htmlStructure = await page.evaluate(() => {
      const getElementInfo = (element) => {
        return {
          tagName: element.tagName.toLowerCase(),
          id: element.id || null,
          className: element.className || null,
          textContent: element.textContent ? element.textContent.trim().substring(0, 100) : null
        };
      };
      
      return {
        doctype: document.doctype ? document.doctype.name : 'unknown',
        lang: document.documentElement.lang || 'not specified',
        charset: document.characterSet,
        head: {
          title: document.title,
          metaTags: Array.from(document.querySelectorAll('meta')).map(meta => ({
            name: meta.name || meta.property || meta.httpEquiv,
            content: meta.content
          })),
          links: Array.from(document.querySelectorAll('link')).map(link => ({
            rel: link.rel,
            href: link.href,
            type: link.type
          }))
        },
        body: {
          id: document.body.id,
          className: document.body.className,
          children: Array.from(document.body.children).slice(0, 10).map(getElementInfo)
        }
      };
    });
    
    console.log(`Document Type: ${htmlStructure.doctype}`);
    console.log(`Language: ${htmlStructure.lang}`);
    console.log(`Charset: ${htmlStructure.charset}`);
    console.log(`Meta Tags: ${htmlStructure.head.metaTags.length}`);
    
    // Analyze CSS
    console.log('\\n🎨 ANALYZING CSS...');
    console.log(`External CSS files: ${resources.css.length}`);
    resources.css.forEach((css, index) => {
      console.log(`  ${index + 1}. ${css.url} (Status: ${css.status}, Size: ${css.size})`);
    });
    
    // Get inline styles
    const inlineStyles = await page.evaluate(() => {
      const styleElements = Array.from(document.querySelectorAll('style'));
      return styleElements.map((style, index) => ({
        index: index + 1,
        content: style.textContent ? style.textContent.substring(0, 200) + '...' : 'empty',
        length: style.textContent ? style.textContent.length : 0
      }));
    });
    
    console.log(`Inline style blocks: ${inlineStyles.length}`);
    inlineStyles.forEach(style => {
      console.log(`  Block ${style.index}: ${style.length} characters`);
    });
    
    // Analyze JavaScript
    console.log('\\n📜 ANALYZING JAVASCRIPT...');
    console.log(`External JS files: ${resources.js.length}`);
    resources.js.forEach((js, index) => {
      console.log(`  ${index + 1}. ${js.url} (Status: ${js.status}, Size: ${js.size})`);
    });
    
    // Get inline scripts
    const inlineScripts = await page.evaluate(() => {
      const scriptElements = Array.from(document.querySelectorAll('script:not([src])'));
      return scriptElements.map((script, index) => ({
        index: index + 1,
        content: script.textContent ? script.textContent.substring(0, 200) + '...' : 'empty',
        length: script.textContent ? script.textContent.length : 0,
        type: script.type || 'text/javascript'
      }));
    });
    
    console.log(`Inline script blocks: ${inlineScripts.length}`);
    inlineScripts.forEach(script => {
      console.log(`  Block ${script.index}: ${script.length} characters (${script.type})`);
    });
    
    // Analyze forms and inputs
    console.log('\\n📝 ANALYZING FORMS AND INPUTS...');
    
    const formAnalysis = await page.evaluate(() => {
      const forms = Array.from(document.querySelectorAll('form'));
      const inputs = Array.from(document.querySelectorAll('input'));
      const buttons = Array.from(document.querySelectorAll('button'));
      
      return {
        forms: forms.map((form, index) => ({
          index: index + 1,
          action: form.action,
          method: form.method,
          id: form.id,
          className: form.className,
          inputCount: form.querySelectorAll('input').length
        })),
        inputs: inputs.map((input, index) => ({
          index: index + 1,
          type: input.type,
          name: input.name,
          id: input.id,
          placeholder: input.placeholder,
          className: input.className,
          required: input.required,
          value: input.value ? '***' : ''
        })),
        buttons: buttons.map((button, index) => ({
          index: index + 1,
          type: button.type,
          text: button.textContent ? button.textContent.trim() : '',
          className: button.className,
          id: button.id
        }))
      };
    });
    
    console.log(`Forms found: ${formAnalysis.forms.length}`);
    formAnalysis.forms.forEach(form => {
      console.log(`  Form ${form.index}:`);
      console.log(`    Action: ${form.action}`);
      console.log(`    Method: ${form.method}`);
      console.log(`    ID: ${form.id}`);
      console.log(`    Class: ${form.className}`);
      console.log(`    Inputs: ${form.inputCount}`);
    });
    
    console.log(`\\nInput fields found: ${formAnalysis.inputs.length}`);
    formAnalysis.inputs.forEach(input => {
      console.log(`  Input ${input.index}: type="${input.type}" name="${input.name}" id="${input.id}" placeholder="${input.placeholder}"`);
    });
    
    console.log(`\\nButtons found: ${formAnalysis.buttons.length}`);
    formAnalysis.buttons.forEach(button => {
      console.log(`  Button ${button.index}: "${button.text}" type="${button.type}" class="${button.className}"`);
    });
    
    // Analyze frameworks and libraries
    console.log('\\n🔧 DETECTING FRAMEWORKS AND LIBRARIES...');
    
    const frameworks = await page.evaluate(() => {
      const detected = [];
      
      // Check for common frameworks
      if (window.Vue) detected.push(`Vue.js ${window.Vue.version || 'detected'}`);
      if (window.React) detected.push('React detected');
      if (window.Angular) detected.push('Angular detected');
      if (window.jQuery || window.$) detected.push(`jQuery ${window.jQuery ? window.jQuery.fn.jquery : 'detected'}`);
      if (window.bootstrap) detected.push('Bootstrap detected');
      if (window.Tailwind) detected.push('Tailwind CSS detected');
      
      // Check for other libraries
      if (window.axios) detected.push('Axios detected');
      if (window.moment) detected.push('Moment.js detected');
      if (window.lodash || window._) detected.push('Lodash detected');
      
      return detected;
    });
    
    if (frameworks.length > 0) {
      frameworks.forEach(framework => console.log(`  ✅ ${framework}`));
    } else {
      console.log('  ❌ No common frameworks detected in global scope');
    }
    
    // Try to attempt login
    console.log('\\n🔐 ATTEMPTING LOGIN...');
    
    const loginAttempt = await page.evaluate(() => {
      // Find potential login fields
      const emailField = document.querySelector('input[type="email"], input[name="email"], input[name="username"], input[placeholder*="email" i]');
      const passwordField = document.querySelector('input[type="password"]');
      const loginButton = document.querySelector('button[type="submit"], button:contains("login"), button:contains("đăng nhập"), .login-btn');
      
      return {
        hasEmailField: !!emailField,
        hasPasswordField: !!passwordField,
        hasLoginButton: !!loginButton,
        emailFieldInfo: emailField ? {
          type: emailField.type,
          name: emailField.name,
          id: emailField.id,
          placeholder: emailField.placeholder
        } : null,
        passwordFieldInfo: passwordField ? {
          name: passwordField.name,
          id: passwordField.id,
          placeholder: passwordField.placeholder
        } : null
      };
    });
    
    if (loginAttempt.hasEmailField && loginAttempt.hasPasswordField) {
      console.log('✅ Login form detected');
      console.log(`  Email field: ${JSON.stringify(loginAttempt.emailFieldInfo)}`);
      console.log(`  Password field: ${JSON.stringify(loginAttempt.passwordFieldInfo)}`);
      
      // Try demo credentials
      const demoCredentials = [
        { email: 'demo@1office.vn', password: 'demo123456' },
        { email: 'admin@1office.vn', password: 'admin123456' },
        { email: 'test@1office.vn', password: 'test123456' }
      ];
      
      for (const creds of demoCredentials) {
        console.log(`\\n🔑 Trying credentials: ${creds.email}`);
        
        try {
          // Fill form
          await page.fill('input[type="email"], input[name="email"], input[name="username"]', creds.email);
          await page.fill('input[type="password"]', creds.password);
          
          // Click login
          await page.click('button[type="submit"], button:has-text("Đăng nhập"), button:has-text("Login"), .login-btn');
          
          // Wait for response
          await page.waitForTimeout(3000);
          
          const currentUrl = page.url();
          console.log(`  Result URL: ${currentUrl}`);
          
          if (currentUrl !== 'https://trial.1office.vn' && !currentUrl.includes('/login')) {
            console.log('  🎉 Login appears successful!');
            
            // Take screenshot of logged in state
            await page.screenshot({ path: 'trial-office-logged-in.png', fullPage: true });
            console.log('  📸 Logged-in screenshot saved');
            
            // Analyze logged-in page
            const loggedInTitle = await page.title();
            console.log(`  📄 Logged-in page title: ${loggedInTitle}`);
            
            break;
          } else {
            console.log('  ❌ Login failed or redirected back');
            
            // Check for error messages
            const errorMessage = await page.textContent('.error, .alert-danger, .text-red, [class*="error"]').catch(() => null);
            if (errorMessage) {
              console.log(`  Error message: ${errorMessage.trim()}`);
            }
          }
          
        } catch (error) {
          console.log(`  ❌ Error during login attempt: ${error.message}`);
        }
      }
    } else {
      console.log('❌ Login form not found or incomplete');
    }
    
    // Save comprehensive analysis
    const analysisData = {
      pageInfo,
      htmlStructure,
      resources,
      formAnalysis,
      frameworks,
      loginAttempt,
      timestamp: new Date().toISOString()
    };
    
    fs.writeFileSync('trial-office-analysis.json', JSON.stringify(analysisData, null, 2));
    console.log('\\n💾 Comprehensive analysis saved to trial-office-analysis.json');
    
    console.log('\\n✅ Analysis completed successfully!');
    
  } catch (error) {
    console.error('💥 Error during analysis:', error.message);
    await page.screenshot({ path: 'trial-office-error.png', fullPage: true });
  } finally {
    await browser.close();
  }
}

// Run the analysis
analyzeTrialOffice().catch(console.error);
