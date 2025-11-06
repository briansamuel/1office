const https = require('https');
const fs = require('fs');
const zlib = require('zlib');
const querystring = require('querystring');

/**
 * Detailed test of admin/admin@123 credentials
 */

class DetailedLoginTest {
  constructor() {
    this.cookies = '';
    this.requestCount = 0;
  }

  async makeRequest(url, options = {}) {
    this.requestCount++;
    console.log(`\\n[${this.requestCount}] 🌐 ${options.method || 'GET'} ${url}`);
    
    return new Promise((resolve, reject) => {
      const defaultOptions = {
        method: 'GET',
        headers: {
          'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
          'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
          'Accept-Language': 'en-US,en;q=0.5',
          'Accept-Encoding': 'gzip, deflate, br',
          'Connection': 'keep-alive',
          'Upgrade-Insecure-Requests': '1',
          'Cookie': this.cookies,
          ...options.headers
        }
      };

      const requestOptions = { ...defaultOptions, ...options };
      
      const req = https.request(url, requestOptions, (res) => {
        console.log(`[${this.requestCount}] 📊 Status: ${res.statusCode}`);
        console.log(`[${this.requestCount}] 📍 Location: ${res.headers.location || 'none'}`);
        console.log(`[${this.requestCount}] 🍪 Set-Cookie: ${res.headers['set-cookie'] ? 'yes' : 'no'}`);

        // Update cookies
        if (res.headers['set-cookie']) {
          const newCookies = res.headers['set-cookie'].map(cookie => cookie.split(';')[0]).join('; ');
          this.cookies = this.cookies ? `${this.cookies}; ${newCookies}` : newCookies;
          console.log(`[${this.requestCount}] 🔄 Updated cookies: ${this.cookies.substring(0, 100)}...`);
        }

        let data = [];
        
        res.on('data', (chunk) => {
          data.push(chunk);
        });
        
        res.on('end', () => {
          let buffer = Buffer.concat(data);
          let body = '';

          // Handle compression
          const encoding = res.headers['content-encoding'];
          try {
            if (encoding === 'gzip') {
              body = zlib.gunzipSync(buffer).toString();
            } else if (encoding === 'deflate') {
              body = zlib.inflateSync(buffer).toString();
            } else if (encoding === 'br') {
              body = zlib.brotliDecompressSync(buffer).toString();
            } else {
              body = buffer.toString();
            }
          } catch (error) {
            body = buffer.toString();
          }

          console.log(`[${this.requestCount}] 📏 Body length: ${body.length}`);
          console.log(`[${this.requestCount}] 📄 Contains login form: ${body.includes('form-login')}`);
          console.log(`[${this.requestCount}] 👤 Contains user info: ${body.includes('logout') || body.includes('profile') || body.includes('dashboard')}`);

          resolve({
            statusCode: res.statusCode,
            headers: res.headers,
            body: body,
            url: url
          });
        });
      });

      req.on('error', (err) => {
        reject(err);
      });

      if (options.data) {
        console.log(`[${this.requestCount}] 📤 POST data: ${options.data}`);
        req.write(options.data);
      }

      req.end();
    });
  }

  async testAdminLogin() {
    console.log('🔍 Detailed test of admin/admin@123 credentials...');

    try {
      // Step 1: Get login page
      console.log('\\n=== STEP 1: GET LOGIN PAGE ===');
      const loginPageResponse = await this.makeRequest('https://trial.1office.vn/login');
      
      // Save login page
      fs.writeFileSync('detailed-login-page.html', loginPageResponse.body);
      console.log('💾 Login page saved to detailed-login-page.html');

      // Step 2: Attempt login with admin/admin@123
      console.log('\\n=== STEP 2: LOGIN WITH admin/admin@123 ===');
      
      const loginData = {
        username: 'admin',
        userpwd: 'admin@123',
        url_login: 'https://trial.1office.vn/login',
        selected_language: 'en'
      };

      const postData = querystring.stringify(loginData);

      const loginResponse = await this.makeRequest('https://trial.1office.vn/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'Content-Length': Buffer.byteLength(postData),
          'Referer': 'https://trial.1office.vn/login'
        },
        data: postData
      });

      // Save login response
      fs.writeFileSync('detailed-login-response.html', loginResponse.body);
      console.log('💾 Login response saved to detailed-login-response.html');

      // Step 3: Handle redirect
      if (loginResponse.statusCode === 302 && loginResponse.headers.location) {
        console.log('\\n=== STEP 3: FOLLOW REDIRECT ===');
        
        let redirectUrl = loginResponse.headers.location;
        let redirectCount = 0;
        const maxRedirects = 5;

        while (redirectUrl && redirectCount < maxRedirects) {
          redirectCount++;
          console.log(`\\n--- Redirect ${redirectCount}: ${redirectUrl} ---`);

          const redirectResponse = await this.makeRequest(redirectUrl);
          
          // Save each redirect response
          fs.writeFileSync(`detailed-redirect-${redirectCount}.html`, redirectResponse.body);
          console.log(`💾 Redirect ${redirectCount} saved to detailed-redirect-${redirectCount}.html`);

          // Check if this is the final page
          if (redirectResponse.statusCode === 200) {
            console.log('\\n🎯 Final page reached!');
            
            // Analyze final page
            const hasLoginForm = redirectResponse.body.includes('form-login');
            const hasUserInfo = redirectResponse.body.includes('logout') || 
                               redirectResponse.body.includes('profile') ||
                               redirectResponse.body.includes('dashboard') ||
                               redirectResponse.body.includes('admin') ||
                               redirectResponse.body.includes('user');
            
            const hasWorkContent = redirectResponse.body.includes('work') ||
                                  redirectResponse.body.includes('project') ||
                                  redirectResponse.body.includes('task');

            console.log(`📋 Analysis of final page:`);
            console.log(`  📄 Title: ${redirectResponse.body.match(/<title[^>]*>(.*?)<\/title>/i)?.[1] || 'No title'}`);
            console.log(`  📏 Length: ${redirectResponse.body.length} characters`);
            console.log(`  🔐 Has login form: ${hasLoginForm}`);
            console.log(`  👤 Has user info: ${hasUserInfo}`);
            console.log(`  💼 Has work content: ${hasWorkContent}`);

            if (!hasLoginForm && hasUserInfo) {
              console.log('\\n🎉 LOGIN APPEARS SUCCESSFUL!');
              
              // Step 4: Test work module access
              console.log('\\n=== STEP 4: TEST WORK MODULE ACCESS ===');
              
              const workResponse = await this.makeRequest('https://trial.1office.vn/work');
              fs.writeFileSync('detailed-work-response.html', workResponse.body);
              console.log('💾 Work response saved to detailed-work-response.html');

              if (workResponse.statusCode === 200) {
                const workHasContent = workResponse.body.length > 5000 && 
                                     !workResponse.body.includes('form-login');
                
                console.log(`📦 Work module analysis:`);
                console.log(`  📊 Status: ${workResponse.statusCode}`);
                console.log(`  📏 Length: ${workResponse.body.length} characters`);
                console.log(`  📝 Has content: ${workHasContent}`);
                console.log(`  🔐 Has login form: ${workResponse.body.includes('form-login')}`);

                if (workHasContent) {
                  console.log('\\n✅ WORK MODULE ACCESSIBLE!');
                  
                  // Extract work features
                  const workFeatures = {
                    projects: workResponse.body.includes('project'),
                    tasks: workResponse.body.includes('task'),
                    calendar: workResponse.body.includes('calendar'),
                    dashboard: workResponse.body.includes('dashboard'),
                    reports: workResponse.body.includes('report'),
                    teams: workResponse.body.includes('team')
                  };

                  console.log('\\n🎯 Work features detected:');
                  Object.entries(workFeatures).forEach(([feature, detected]) => {
                    console.log(`  ${detected ? '✅' : '❌'} ${feature}`);
                  });

                  // Extract navigation links
                  const linkMatches = workResponse.body.match(/<a[^>]*href=["']([^"']+)["'][^>]*>([^<]+)</gi) || [];
                  const workLinks = linkMatches
                    .map(link => {
                      const hrefMatch = link.match(/href=["']([^"']+)["']/i);
                      const textMatch = link.match(/>([^<]+)</);
                      return {
                        href: hrefMatch ? hrefMatch[1] : '',
                        text: textMatch ? textMatch[1].trim() : ''
                      };
                    })
                    .filter(link => link.href.includes('/work') || 
                                   link.text.toLowerCase().includes('project') ||
                                   link.text.toLowerCase().includes('task'))
                    .slice(0, 10);

                  if (workLinks.length > 0) {
                    console.log('\\n🔗 Work-related navigation:');
                    workLinks.forEach(link => {
                      console.log(`  📎 ${link.text} → ${link.href}`);
                    });
                  }

                  // Save success info
                  const successInfo = {
                    credentials: { username: 'admin', password: 'admin@123' },
                    loginSuccessful: true,
                    workModuleAccessible: true,
                    cookies: this.cookies,
                    workFeatures,
                    workLinks,
                    timestamp: new Date().toISOString()
                  };

                  fs.writeFileSync('detailed-success-info.json', JSON.stringify(successInfo, null, 2));
                  console.log('\\n💾 Success info saved to detailed-success-info.json');

                } else {
                  console.log('\\n❌ Work module not accessible or empty');
                }
              } else {
                console.log(`\\n❌ Work module returned status: ${workResponse.statusCode}`);
              }

            } else {
              console.log('\\n❌ Login failed - still on login page or no user info');
            }

            break; // Exit redirect loop
          } else if (redirectResponse.statusCode === 302) {
            // Another redirect
            redirectUrl = redirectResponse.headers.location;
            if (redirectUrl && !redirectUrl.startsWith('http')) {
              // Relative URL, make it absolute
              redirectUrl = `https://trial.1office.vn${redirectUrl}`;
            }
          } else {
            console.log(`\\n❌ Unexpected status in redirect: ${redirectResponse.statusCode}`);
            break;
          }
        }

        if (redirectCount >= maxRedirects) {
          console.log('\\n⚠️ Too many redirects, stopping');
        }

      } else {
        console.log('\\n❌ No redirect after login - login likely failed');
      }

      console.log('\\n✅ Detailed test completed!');

    } catch (error) {
      console.error('💥 Error during detailed test:', error.message);
    }
  }
}

// Run the detailed test
const tester = new DetailedLoginTest();
tester.testAdminLogin()
  .then(() => {
    console.log('\\n🎉 Detailed test complete! Check the generated HTML files for details.');
  })
  .catch(error => {
    console.error('💥 Test failed:', error.message);
  });
