const { chromium } = require('playwright');

/**
 * Optimized Playwright script for trial.1office.vn login
 * Based on comprehensive analysis of the platform
 */
async function loginToTrialOffice() {
  console.log('🚀 Starting optimized login to trial.1office.vn...');
  
  const browser = await chromium.launch({ 
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });
  
  const page = await browser.newPage();
  
  // Set user agent to mimic real browser
  await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
  
  try {
    console.log('📍 Navigating to trial.1office.vn...');
    
    // Navigate and wait for redirect to /login
    await page.goto('https://trial.1office.vn', { 
      waitUntil: 'networkidle',
      timeout: 30000
    });
    
    const currentUrl = page.url();
    console.log(`🔗 Current URL: ${currentUrl}`);
    
    // Verify we're on the login page
    if (!currentUrl.includes('/login')) {
      throw new Error('Did not redirect to login page as expected');
    }
    
    // Wait for login form to be visible
    console.log('⏳ Waiting for login form...');
    await page.waitForSelector('#form-login', { timeout: 10000 });
    
    // Verify form elements are present
    const usernameField = page.locator('#username');
    const passwordField = page.locator('#userpwd');
    const submitButton = page.locator('.submit.form-btn');
    
    await Promise.all([
      usernameField.waitFor({ state: 'visible' }),
      passwordField.waitFor({ state: 'visible' }),
      submitButton.waitFor({ state: 'visible' })
    ]);
    
    console.log('✅ Login form elements found and visible');
    
    // Take screenshot of login page
    await page.screenshot({ path: 'trial-office-login-page.png', fullPage: true });
    console.log('📸 Login page screenshot saved');
    
    // Demo credentials to try (based on common trial patterns)
    const credentials = [
      { username: 'demo', password: 'demo' },
      { username: 'admin', password: 'admin' },
      { username: 'test', password: 'test' },
      { username: 'trial', password: 'trial' },
      { username: 'guest', password: 'guest' },
      { username: 'demo@1office.vn', password: 'demo123' },
      { username: 'admin@1office.vn', password: 'admin123' },
      { username: 'trial@1office.vn', password: 'trial123' }
    ];
    
    console.log('\\n🔐 Attempting login with demo credentials...');
    
    for (const cred of credentials) {
      console.log(`\\n🔑 Trying: ${cred.username} / ${'*'.repeat(cred.password.length)}`);
      
      try {
        // Clear and fill username
        await usernameField.clear();
        await usernameField.fill(cred.username);
        
        // Clear and fill password
        await passwordField.clear();
        await passwordField.fill(cred.password);
        
        // Submit form
        await submitButton.click();
        
        // Wait for response (either redirect or error)
        await page.waitForTimeout(3000);
        
        const newUrl = page.url();
        console.log(`  📍 URL after login: ${newUrl}`);
        
        // Check if login was successful
        if (newUrl !== currentUrl && !newUrl.includes('/login')) {
          console.log('  🎉 LOGIN SUCCESSFUL!');
          
          // Take screenshot of successful login
          await page.screenshot({ 
            path: `trial-office-success-${cred.username.replace('@', '-').replace('.', '-')}.png`,
            fullPage: true 
          });
          
          // Get page title and basic info
          const pageTitle = await page.title();
          const bodyText = await page.locator('body').textContent();
          
          console.log(`  📄 Page title: ${pageTitle}`);
          console.log(`  📊 Page contains dashboard elements: ${bodyText.toLowerCase().includes('dashboard')}`);
          console.log(`  📊 Page contains menu elements: ${bodyText.toLowerCase().includes('menu')}`);
          
          // Look for user info or logout elements
          const userElements = await page.locator('.user, .profile, .avatar, .logout').count();
          console.log(`  👤 User-related elements found: ${userElements}`);
          
          // Try to find navigation or main content
          const navElements = await page.locator('nav, .nav, .menu, .sidebar, .main-menu').count();
          console.log(`  🧭 Navigation elements found: ${navElements}`);
          
          // Save successful login data
          const successData = {
            credentials: cred,
            loginUrl: currentUrl,
            dashboardUrl: newUrl,
            pageTitle,
            timestamp: new Date().toISOString(),
            userElements,
            navElements
          };
          
          require('fs').writeFileSync(
            'trial-office-login-success.json', 
            JSON.stringify(successData, null, 2)
          );
          
          console.log('  💾 Success data saved to trial-office-login-success.json');
          
          // Explore the dashboard briefly
          console.log('\\n🔍 Exploring dashboard...');
          
          // Look for common dashboard elements
          const dashboardElements = await page.evaluate(() => {
            const selectors = [
              '.dashboard', '.main-content', '.content', '.workspace',
              '.sidebar', '.menu', '.nav', '.header', '.footer',
              '.user-info', '.profile', '.avatar', '.logout',
              'h1', 'h2', 'h3', '.title', '.heading'
            ];
            
            const found = {};
            selectors.forEach(selector => {
              const elements = document.querySelectorAll(selector);
              if (elements.length > 0) {
                found[selector] = {
                  count: elements.length,
                  text: Array.from(elements).slice(0, 3).map(el => 
                    el.textContent ? el.textContent.trim().substring(0, 50) : ''
                  ).filter(text => text.length > 0)
                };
              }
            });
            
            return found;
          });
          
          console.log('  📋 Dashboard elements found:');
          Object.entries(dashboardElements).forEach(([selector, info]) => {
            console.log(`    ${selector}: ${info.count} elements`);
            if (info.text.length > 0) {
              console.log(`      Text samples: ${info.text.join(', ')}`);
            }
          });
          
          // Save dashboard analysis
          require('fs').writeFileSync(
            'trial-office-dashboard-analysis.json',
            JSON.stringify(dashboardElements, null, 2)
          );
          
          console.log('\\n✅ Login and exploration completed successfully!');
          return; // Exit on successful login
          
        } else {
          console.log('  ❌ Login failed or redirected back to login');
          
          // Look for error messages
          const errorSelectors = [
            '.error', '.alert-danger', '.text-red', '.invalid-feedback',
            '[class*="error"]', '.notification-error', '.message-error'
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
              // Continue to next selector
            }
          }
          
          // Take screenshot of failed attempt
          await page.screenshot({ 
            path: `trial-office-failed-${cred.username.replace('@', '-').replace('.', '-')}.png`,
            fullPage: true 
          });
        }
        
      } catch (error) {
        console.log(`  💥 Error during login attempt: ${error.message}`);
      }
      
      // Return to login page if not already there
      if (page.url() !== currentUrl) {
        await page.goto(currentUrl);
        await page.waitForSelector('#form-login', { timeout: 5000 });
      }
    }
    
    console.log('\\n❌ All login attempts failed');
    
  } catch (error) {
    console.error('💥 Error during login process:', error.message);
    await page.screenshot({ path: 'trial-office-error.png', fullPage: true });
  } finally {
    console.log('\\n🏁 Closing browser...');
    await browser.close();
  }
}

// Enhanced error handling
async function main() {
  try {
    await loginToTrialOffice();
  } catch (error) {
    console.error('💥 Fatal error:', error.message);
    process.exit(1);
  }
}

// Run the script
main();
