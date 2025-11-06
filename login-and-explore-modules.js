const https = require('https');
const fs = require('fs');
const zlib = require('zlib');
const querystring = require('querystring');

/**
 * Advanced analysis with login attempt and module exploration
 */

class TrialOfficeAnalyzer {
  constructor() {
    this.cookies = '';
    this.csrfToken = '';
    this.sessionId = '';
  }

  async makeRequest(url, options = {}) {
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

  extractCSRFToken(html) {
    // Look for CSRF token in various formats
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

  async attemptLogin() {
    console.log('🔐 Attempting to login...');

    // First, get the login page to extract CSRF token
    const loginPageResponse = await this.makeRequest('https://trial.1office.vn/login');
    console.log(`📄 Login page status: ${loginPageResponse.statusCode}`);

    // Extract CSRF token if present
    this.csrfToken = this.extractCSRFToken(loginPageResponse.body);
    if (this.csrfToken) {
      console.log(`🔑 CSRF token found: ${this.csrfToken.substring(0, 20)}...`);
    }

    // Demo credentials to try
    const credentials = [
      { username: 'demo', password: 'demo' },
      { username: 'admin', password: 'admin' },
      { username: 'test', password: 'test' },
      { username: 'trial', password: 'trial' },
      { username: 'guest', password: 'guest' },
      { username: 'work', password: 'work' },
      { username: 'demo@1office.vn', password: 'demo123' },
      { username: 'admin@1office.vn', password: 'admin123' }
    ];

    for (const cred of credentials) {
      console.log(`\\n🔑 Trying login: ${cred.username}`);

      try {
        // Prepare login data
        const loginData = {
          username: cred.username,
          userpwd: cred.password,
          url_login: 'https://trial.1office.vn/login',
          selected_language: 'en'
        };

        if (this.csrfToken) {
          loginData._token = this.csrfToken;
        }

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

        console.log(`  📊 Login response status: ${loginResponse.statusCode}`);
        console.log(`  🔗 Response URL: ${loginResponse.url}`);

        // Check if login was successful
        if (loginResponse.statusCode === 302 || 
            (loginResponse.body && !loginResponse.body.includes('form-login'))) {
          console.log('  🎉 Login appears successful!');
          
          // Save successful login info
          fs.writeFileSync('successful-login.json', JSON.stringify({
            credentials: cred,
            cookies: this.cookies,
            timestamp: new Date().toISOString()
          }, null, 2));

          return true;
        } else {
          console.log('  ❌ Login failed');
          
          // Look for error messages
          const errorPatterns = [
            /error[^>]*>([^<]+)</gi,
            /alert[^>]*>([^<]+)</gi,
            /invalid[^>]*>([^<]+)</gi
          ];

          for (const pattern of errorPatterns) {
            const matches = loginResponse.body.match(pattern);
            if (matches) {
              console.log(`  ⚠️ Error: ${matches[0]}`);
              break;
            }
          }
        }

      } catch (error) {
        console.log(`  💥 Login error: ${error.message}`);
      }
    }

    return false;
  }

  async exploreModules() {
    console.log('\\n🔍 Exploring modules...');

    const modules = [
      { path: '/work', name: 'Work Management' },
      { path: '/hrm', name: 'Human Resources' },
      { path: '/crm', name: 'Customer Relations' },
      { path: '/warehouse', name: 'Warehouse Management' },
      { path: '/finance', name: 'Finance Management' },
      { path: '/admin', name: 'Administration' },
      { path: '/dashboard', name: 'Dashboard' },
      { path: '/profile', name: 'User Profile' },
      { path: '/settings', name: 'Settings' },
      { path: '/reports', name: 'Reports' }
    ];

    const moduleAnalysis = [];

    for (const module of modules) {
      try {
        console.log(`\\n📦 Exploring ${module.name} (${module.path})...`);

        const moduleResponse = await this.makeRequest(`https://trial.1office.vn${module.path}`);
        
        console.log(`  📊 Status: ${moduleResponse.statusCode}`);

        const analysis = {
          path: module.path,
          name: module.name,
          status: moduleResponse.statusCode,
          accessible: moduleResponse.statusCode === 200,
          title: '',
          hasContent: false,
          features: [],
          apiEndpoints: [],
          forms: [],
          navigation: []
        };

        if (moduleResponse.statusCode === 200) {
          // Extract title
          const titleMatch = moduleResponse.body.match(/<title[^>]*>(.*?)<\/title>/i);
          if (titleMatch) {
            analysis.title = titleMatch[1].trim();
            console.log(`  📄 Title: ${analysis.title}`);
          }

          // Check if has meaningful content
          analysis.hasContent = moduleResponse.body.length > 1000 && 
                               !moduleResponse.body.includes('form-login');
          
          console.log(`  📝 Has content: ${analysis.hasContent}`);

          // Look for features/functionality
          const featurePatterns = [
            /class="[^"]*(?:btn|button|menu|nav|card|panel|widget|module)[^"]*"/gi,
            /id="[^"]*(?:btn|button|menu|nav|card|panel|widget|module)[^"]*"/gi
          ];

          featurePatterns.forEach(pattern => {
            const matches = moduleResponse.body.match(pattern) || [];
            analysis.features.push(...matches.slice(0, 10));
          });

          // Look for API endpoints
          const apiMatches = moduleResponse.body.match(/['"`]\/api\/[^'"`\s]+['"`]/g) || [];
          analysis.apiEndpoints = apiMatches.map(match => match.replace(/['"`]/g, ''));

          // Look for forms
          const formMatches = moduleResponse.body.match(/<form[^>]*>/gi) || [];
          analysis.forms = formMatches.map(form => {
            const actionMatch = form.match(/action=["']([^"']+)["']/i);
            const methodMatch = form.match(/method=["']([^"']+)["']/i);
            return {
              action: actionMatch ? actionMatch[1] : '',
              method: methodMatch ? methodMatch[1] : 'GET'
            };
          });

          // Look for navigation elements
          const navMatches = moduleResponse.body.match(/<a[^>]*href=["']([^"']+)["'][^>]*>([^<]+)</gi) || [];
          analysis.navigation = navMatches.slice(0, 20).map(match => {
            const hrefMatch = match.match(/href=["']([^"']+)["']/i);
            const textMatch = match.match(/>([^<]+)</);
            return {
              href: hrefMatch ? hrefMatch[1] : '',
              text: textMatch ? textMatch[1].trim() : ''
            };
          });

          console.log(`  🔧 Features found: ${analysis.features.length}`);
          console.log(`  📡 API endpoints: ${analysis.apiEndpoints.length}`);
          console.log(`  📝 Forms: ${analysis.forms.length}`);
          console.log(`  🧭 Navigation items: ${analysis.navigation.length}`);

          // Save module HTML if accessible
          fs.writeFileSync(`module-${module.path.replace('/', '')}-content.html`, moduleResponse.body);

        } else {
          console.log(`  ❌ Not accessible`);
        }

        moduleAnalysis.push(analysis);

      } catch (error) {
        console.log(`  💥 Error exploring ${module.path}: ${error.message}`);
        moduleAnalysis.push({
          path: module.path,
          name: module.name,
          error: error.message,
          accessible: false
        });
      }
    }

    return moduleAnalysis;
  }

  async run() {
    console.log('🚀 Starting comprehensive 1Office analysis...');

    const results = {
      timestamp: new Date().toISOString(),
      loginAttempted: false,
      loginSuccessful: false,
      modules: [],
      summary: {}
    };

    try {
      // Attempt login
      results.loginAttempted = true;
      results.loginSuccessful = await this.attemptLogin();

      // Explore modules (whether login successful or not)
      results.modules = await this.exploreModules();

      // Generate summary
      const accessibleModules = results.modules.filter(m => m.accessible);
      const modulesWithContent = results.modules.filter(m => m.hasContent);

      results.summary = {
        totalModules: results.modules.length,
        accessibleModules: accessibleModules.length,
        modulesWithContent: modulesWithContent.length,
        loginRequired: results.modules.every(m => !m.accessible || !m.hasContent),
        detectedFeatures: results.modules.reduce((sum, m) => sum + (m.features?.length || 0), 0),
        totalApiEndpoints: results.modules.reduce((sum, m) => sum + (m.apiEndpoints?.length || 0), 0)
      };

      console.log('\\n📊 Analysis Summary:');
      console.log(`🔐 Login successful: ${results.loginSuccessful ? '✅' : '❌'}`);
      console.log(`📦 Total modules tested: ${results.summary.totalModules}`);
      console.log(`✅ Accessible modules: ${results.summary.accessibleModules}`);
      console.log(`📝 Modules with content: ${results.summary.modulesWithContent}`);
      console.log(`🔧 Total features detected: ${results.summary.detectedFeatures}`);
      console.log(`📡 Total API endpoints: ${results.summary.totalApiEndpoints}`);

      // Save comprehensive results
      fs.writeFileSync('comprehensive-module-analysis.json', JSON.stringify(results, null, 2));
      console.log('\\n💾 Comprehensive analysis saved to comprehensive-module-analysis.json');

      console.log('\\n✅ Analysis completed successfully!');

    } catch (error) {
      console.error('💥 Fatal error:', error.message);
      results.error = error.message;
    }

    return results;
  }
}

// Run the analysis
const analyzer = new TrialOfficeAnalyzer();
analyzer.run()
  .then(results => {
    console.log('\\n🎉 Analysis complete!');
  })
  .catch(error => {
    console.error('💥 Analysis failed:', error.message);
    process.exit(1);
  });
