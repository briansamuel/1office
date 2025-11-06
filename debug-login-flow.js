const https = require('https');
const fs = require('fs');
const zlib = require('zlib');
const querystring = require('querystring');

/**
 * Debug login flow and session management
 */

class LoginDebugger {
  constructor() {
    this.cookies = '';
    this.csrfToken = '';
    this.requestCount = 0;
  }

  async makeRequest(url, options = {}) {
    this.requestCount++;
    console.log(`\\n[${this.requestCount}] Making request to: ${url}`);
    console.log(`[${this.requestCount}] Method: ${options.method || 'GET'}`);
    console.log(`[${this.requestCount}] Current cookies: ${this.cookies.substring(0, 100)}...`);

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
        console.log(`[${this.requestCount}] Response status: ${res.statusCode}`);
        console.log(`[${this.requestCount}] Response headers:`, JSON.stringify(res.headers, null, 2));

        // Update cookies
        if (res.headers['set-cookie']) {
          const newCookies = res.headers['set-cookie'].map(cookie => cookie.split(';')[0]).join('; ');
          this.cookies = this.cookies ? `${this.cookies}; ${newCookies}` : newCookies;
          console.log(`[${this.requestCount}] Updated cookies: ${this.cookies.substring(0, 100)}...`);
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

          console.log(`[${this.requestCount}] Response body length: ${body.length}`);
          console.log(`[${this.requestCount}] Body preview: ${body.substring(0, 200)}...`);

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
        console.log(`[${this.requestCount}] POST data: ${options.data}`);
        req.write(options.data);
      }

      req.end();
    });
  }

  extractCSRFToken(html) {
    const patterns = [
      /<meta name="csrf-token" content="([^"]+)"/i,
      /<input[^>]*name="_token"[^>]*value="([^"]+)"/i,
      /<input[^>]*value="([^"]+)"[^>]*name="_token"/i,
      /csrf[^"']*["']([^"']+)["']/i
    ];

    for (const pattern of patterns) {
      const match = html.match(pattern);
      if (match) {
        return match[1];
      }
    }
    return null;
  }

  async debugLoginFlow() {
    console.log('🔍 Starting debug login flow...');

    try {
      // Step 1: Get login page
      console.log('\\n=== STEP 1: GET LOGIN PAGE ===');
      const loginPageResponse = await this.makeRequest('https://trial.1office.vn/login');
      
      // Extract CSRF token
      this.csrfToken = this.extractCSRFToken(loginPageResponse.body);
      if (this.csrfToken) {
        console.log(`🔑 CSRF token extracted: ${this.csrfToken}`);
      } else {
        console.log('⚠️ No CSRF token found');
      }

      // Save login page
      fs.writeFileSync('debug-login-page.html', loginPageResponse.body);

      // Step 2: Attempt login
      console.log('\\n=== STEP 2: POST LOGIN ===');
      const loginData = {
        username: 'admin',
        userpwd: 'admin@123',
        url_login: 'https://trial.1office.vn/login',
        selected_language: 'en'
      };

      if (this.csrfToken) {
        loginData._token = this.csrfToken;
      }

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
      fs.writeFileSync('debug-login-response.html', loginResponse.body);

      // Step 3: Follow redirect if any
      if (loginResponse.statusCode === 302 && loginResponse.headers.location) {
        console.log('\\n=== STEP 3: FOLLOW REDIRECT ===');
        const redirectResponse = await this.makeRequest(loginResponse.headers.location);
        fs.writeFileSync('debug-redirect-response.html', redirectResponse.body);

        // Check if we're logged in by looking for user info
        const hasUserInfo = redirectResponse.body.includes('logout') || 
                           redirectResponse.body.includes('profile') ||
                           redirectResponse.body.includes('dashboard') ||
                           redirectResponse.body.includes('admin');

        console.log(`🔍 User info detected: ${hasUserInfo}`);

        if (hasUserInfo) {
          console.log('✅ Login appears successful!');
          
          // Step 4: Try to access work module
          console.log('\\n=== STEP 4: ACCESS WORK MODULE ===');
          const workResponse = await this.makeRequest('https://trial.1office.vn/work');
          fs.writeFileSync('debug-work-response.html', workResponse.body);

          if (workResponse.statusCode === 200) {
            console.log('✅ Work module accessible!');
            
            // Analyze work module content
            const hasWorkContent = workResponse.body.includes('project') ||
                                 workResponse.body.includes('task') ||
                                 workResponse.body.includes('work') ||
                                 workResponse.body.length > 5000;

            console.log(`📊 Work content detected: ${hasWorkContent}`);
            console.log(`📏 Work page length: ${workResponse.body.length} characters`);

            // Look for specific work features
            const workFeatures = {
              projects: workResponse.body.includes('project'),
              tasks: workResponse.body.includes('task'),
              calendar: workResponse.body.includes('calendar'),
              dashboard: workResponse.body.includes('dashboard'),
              reports: workResponse.body.includes('report'),
              teams: workResponse.body.includes('team'),
              assignments: workResponse.body.includes('assign')
            };

            console.log('🎯 Work features detected:');
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
              console.log('\\n🔗 Work-related links found:');
              workLinks.forEach(link => {
                console.log(`  📎 ${link.text} → ${link.href}`);
              });
            }

            // Step 5: Try to access work sub-modules
            console.log('\\n=== STEP 5: ACCESS WORK SUB-MODULES ===');
            const subModules = [
              '/work/projects',
              '/work/tasks',
              '/work/dashboard',
              '/work/calendar',
              '/work/reports'
            ];

            for (const subModule of subModules) {
              try {
                console.log(`\\n🔍 Testing ${subModule}...`);
                const subResponse = await this.makeRequest(`https://trial.1office.vn${subModule}`);
                console.log(`  📊 Status: ${subResponse.statusCode}`);
                console.log(`  📏 Length: ${subResponse.body.length}`);
                
                if (subResponse.statusCode === 200 && subResponse.body.length > 1000) {
                  fs.writeFileSync(`debug-${subModule.replace(/\//g, '-')}.html`, subResponse.body);
                  console.log(`  ✅ Accessible and saved`);
                } else {
                  console.log(`  ❌ Not accessible or empty`);
                }
              } catch (error) {
                console.log(`  💥 Error: ${error.message}`);
              }
            }

          } else {
            console.log(`❌ Work module not accessible: ${workResponse.statusCode}`);
          }

        } else {
          console.log('❌ Login failed - no user info detected');
        }

      } else {
        console.log('❌ No redirect after login - login likely failed');
      }

      // Step 6: Test other modules to verify session
      console.log('\\n=== STEP 6: TEST OTHER MODULES ===');
      const testModules = ['/dashboard', '/profile', '/admin', '/hrm'];
      
      for (const module of testModules) {
        try {
          const response = await this.makeRequest(`https://trial.1office.vn${module}`);
          console.log(`📦 ${module}: ${response.statusCode} (${response.body.length} chars)`);
        } catch (error) {
          console.log(`📦 ${module}: Error - ${error.message}`);
        }
      }

      console.log('\\n✅ Debug login flow completed!');

    } catch (error) {
      console.error('💥 Debug error:', error.message);
    }
  }
}

// Run the debug
const loginDebugger = new LoginDebugger();
loginDebugger.debugLoginFlow()
  .then(() => {
    console.log('\\n🎉 Debug complete! Check the generated HTML files for details.');
  })
  .catch(error => {
    console.error('💥 Debug failed:', error.message);
  });
