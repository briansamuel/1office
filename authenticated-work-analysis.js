const https = require('https');
const fs = require('fs');
const zlib = require('zlib');
const querystring = require('querystring');

/**
 * Authenticated analysis of trial.1office.vn/work with admin/admin@123
 */

class AuthenticatedAnalyzer {
  constructor() {
    this.cookies = '';
    this.csrfToken = '';
    this.sessionId = '';
    this.credentials = {
      username: 'admin',
      password: 'admin@123'
    };
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

  async login() {
    console.log('🔐 Attempting login with admin/admin@123...');

    try {
      // Get login page
      const loginPageResponse = await this.makeRequest('https://trial.1office.vn/login');
      console.log(`📄 Login page status: ${loginPageResponse.statusCode}`);

      // Extract CSRF token
      this.csrfToken = this.extractCSRFToken(loginPageResponse.body);
      if (this.csrfToken) {
        console.log(`🔑 CSRF token found: ${this.csrfToken.substring(0, 20)}...`);
      }

      // Prepare login data
      const loginData = {
        username: this.credentials.username,
        userpwd: this.credentials.password,
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

      console.log(`📊 Login response status: ${loginResponse.statusCode}`);
      console.log(`🔗 Response headers location: ${loginResponse.headers.location || 'none'}`);

      // Check if login was successful
      if (loginResponse.statusCode === 302 || 
          (loginResponse.body && !loginResponse.body.includes('form-login'))) {
        console.log('🎉 Login successful!');
        
        // Save login session
        fs.writeFileSync('admin-session.json', JSON.stringify({
          credentials: this.credentials,
          cookies: this.cookies,
          csrfToken: this.csrfToken,
          timestamp: new Date().toISOString()
        }, null, 2));

        return true;
      } else {
        console.log('❌ Login failed');
        
        // Look for error messages
        const errorPatterns = [
          /error[^>]*>([^<]+)</gi,
          /alert[^>]*>([^<]+)</gi,
          /invalid[^>]*>([^<]+)</gi
        ];

        for (const pattern of errorPatterns) {
          const matches = loginResponse.body.match(pattern);
          if (matches) {
            console.log(`⚠️ Error: ${matches[0]}`);
            break;
          }
        }
        
        return false;
      }

    } catch (error) {
      console.log(`💥 Login error: ${error.message}`);
      return false;
    }
  }

  analyzeHTML(html, url) {
    const analysis = {
      url: url,
      title: '',
      metaTags: [],
      cssFiles: [],
      jsFiles: [],
      inlineScripts: [],
      forms: [],
      inputs: [],
      buttons: [],
      links: [],
      apiEndpoints: [],
      uiComponents: [],
      workFeatures: [],
      navigation: [],
      dataStructures: []
    };

    // Extract title
    const titleMatch = html.match(/<title[^>]*>(.*?)<\/title>/i);
    if (titleMatch) {
      analysis.title = titleMatch[1].trim();
    }

    // Extract meta tags
    const metaMatches = html.match(/<meta[^>]*>/gi) || [];
    metaMatches.forEach(meta => {
      const nameMatch = meta.match(/name=["']([^"']+)["']/i);
      const propertyMatch = meta.match(/property=["']([^"']+)["']/i);
      const contentMatch = meta.match(/content=["']([^"']+)["']/i);
      
      if ((nameMatch || propertyMatch) && contentMatch) {
        analysis.metaTags.push({
          name: nameMatch ? nameMatch[1] : propertyMatch[1],
          content: contentMatch[1]
        });
      }
    });

    // Extract CSS files
    const cssMatches = html.match(/<link[^>]*rel=["']stylesheet["'][^>]*>/gi) || [];
    cssMatches.forEach(link => {
      const hrefMatch = link.match(/href=["']([^"']+)["']/i);
      if (hrefMatch) {
        analysis.cssFiles.push(hrefMatch[1]);
      }
    });

    // Extract JS files
    const jsMatches = html.match(/<script[^>]*src=["']([^"']+)["'][^>]*>/gi) || [];
    jsMatches.forEach(script => {
      const srcMatch = script.match(/src=["']([^"']+)["']/i);
      if (srcMatch) {
        analysis.jsFiles.push(srcMatch[1]);
      }
    });

    // Extract inline scripts and look for API endpoints
    const scriptMatches = html.match(/<script(?![^>]*src=)[^>]*>(.*?)<\/script>/gis) || [];
    scriptMatches.forEach((script, index) => {
      const contentMatch = script.match(/<script[^>]*>(.*?)<\/script>/is);
      if (contentMatch) {
        const content = contentMatch[1].trim();
        analysis.inlineScripts.push({
          index: index + 1,
          content: content.substring(0, 500) + '...',
          length: content.length
        });

        // Look for API endpoints
        const apiMatches = content.match(/['"`]\/api\/[^'"`\s]+['"`]/g) || [];
        apiMatches.forEach(match => {
          const endpoint = match.replace(/['"`]/g, '');
          if (!analysis.apiEndpoints.includes(endpoint)) {
            analysis.apiEndpoints.push(endpoint);
          }
        });

        // Look for work-specific features
        const workPatterns = [
          /project/gi, /task/gi, /assignment/gi, /deadline/gi,
          /milestone/gi, /progress/gi, /team/gi, /collaboration/gi,
          /workflow/gi, /kanban/gi, /gantt/gi, /calendar/gi
        ];

        workPatterns.forEach(pattern => {
          const matches = content.match(pattern) || [];
          if (matches.length > 0) {
            analysis.workFeatures.push({
              feature: pattern.source,
              occurrences: matches.length
            });
          }
        });
      }
    });

    // Extract forms
    const formMatches = html.match(/<form[^>]*>.*?<\/form>/gis) || [];
    formMatches.forEach((form, index) => {
      const actionMatch = form.match(/action=["']([^"']+)["']/i);
      const methodMatch = form.match(/method=["']([^"']+)["']/i);
      const idMatch = form.match(/id=["']([^"']+)["']/i);
      
      analysis.forms.push({
        index: index + 1,
        action: actionMatch ? actionMatch[1] : '',
        method: methodMatch ? methodMatch[1] : 'GET',
        id: idMatch ? idMatch[1] : '',
        hasCSRF: form.includes('_token') || form.includes('csrf')
      });
    });

    // Extract inputs
    const inputMatches = html.match(/<input[^>]*>/gi) || [];
    inputMatches.forEach((input, index) => {
      const typeMatch = input.match(/type=["']([^"']+)["']/i);
      const nameMatch = input.match(/name=["']([^"']+)["']/i);
      const idMatch = input.match(/id=["']([^"']+)["']/i);
      const placeholderMatch = input.match(/placeholder=["']([^"']+)["']/i);
      
      analysis.inputs.push({
        index: index + 1,
        type: typeMatch ? typeMatch[1] : 'text',
        name: nameMatch ? nameMatch[1] : '',
        id: idMatch ? idMatch[1] : '',
        placeholder: placeholderMatch ? placeholderMatch[1] : ''
      });
    });

    // Extract buttons
    const buttonMatches = html.match(/<button[^>]*>.*?<\/button>/gis) || [];
    buttonMatches.forEach((button, index) => {
      const typeMatch = button.match(/type=["']([^"']+)["']/i);
      const idMatch = button.match(/id=["']([^"']+)["']/i);
      const classMatch = button.match(/class=["']([^"']+)["']/i);
      const textMatch = button.match(/<button[^>]*>(.*?)<\/button>/is);
      
      analysis.buttons.push({
        index: index + 1,
        type: typeMatch ? typeMatch[1] : 'button',
        id: idMatch ? idMatch[1] : '',
        className: classMatch ? classMatch[1] : '',
        text: textMatch ? textMatch[1].replace(/<[^>]*>/g, '').trim() : ''
      });
    });

    // Extract navigation links
    const linkMatches = html.match(/<a[^>]*href=["']([^"']+)["'][^>]*>([^<]+)</gi) || [];
    linkMatches.forEach((link, index) => {
      const hrefMatch = link.match(/href=["']([^"']+)["']/i);
      const textMatch = link.match(/>([^<]+)</);
      
      if (hrefMatch && textMatch) {
        const href = hrefMatch[1];
        const text = textMatch[1].trim();
        
        if (text.length > 0 && href.length > 0 && !href.startsWith('javascript:')) {
          analysis.links.push({
            href: href,
            text: text.substring(0, 100),
            isWorkRelated: href.includes('/work') || 
                          text.toLowerCase().includes('project') ||
                          text.toLowerCase().includes('task') ||
                          text.toLowerCase().includes('work')
          });
        }
      }
    });

    // Look for UI components
    const componentSelectors = [
      'btn', 'button', 'card', 'modal', 'dropdown', 'tooltip',
      'alert', 'badge', 'progress', 'table', 'form', 'nav',
      'sidebar', 'header', 'footer', 'dashboard', 'widget'
    ];

    componentSelectors.forEach(selector => {
      const regex = new RegExp(`class=["'][^"']*\\b${selector}\\b[^"']*["']`, 'gi');
      const matches = html.match(regex) || [];
      if (matches.length > 0) {
        analysis.uiComponents.push({
          component: selector,
          count: matches.length,
          samples: matches.slice(0, 3)
        });
      }
    });

    return analysis;
  }

  async exploreWorkModule() {
    console.log('\\n🔍 Exploring Work Module...');

    try {
      // Access work module
      const workResponse = await this.makeRequest('https://trial.1office.vn/work');
      console.log(`📊 Work module status: ${workResponse.statusCode}`);
      console.log(`🔗 Final URL: ${workResponse.url}`);

      if (workResponse.statusCode === 200) {
        console.log('✅ Successfully accessed work module!');
        
        // Save work module HTML
        fs.writeFileSync('work-module-authenticated.html', workResponse.body);
        console.log('💾 Work module HTML saved');

        // Analyze work module
        const workAnalysis = this.analyzeHTML(workResponse.body, workResponse.url);
        
        console.log(`📄 Work module title: "${workAnalysis.title}"`);
        console.log(`🔗 Links found: ${workAnalysis.links.length}`);
        console.log(`📜 JavaScript files: ${workAnalysis.jsFiles.length}`);
        console.log(`🎨 CSS files: ${workAnalysis.cssFiles.length}`);
        console.log(`📡 API endpoints: ${workAnalysis.apiEndpoints.length}`);
        console.log(`🔧 UI components: ${workAnalysis.uiComponents.length}`);
        console.log(`⚡ Work features: ${workAnalysis.workFeatures.length}`);

        // Display work-related links
        const workLinks = workAnalysis.links.filter(link => link.isWorkRelated);
        if (workLinks.length > 0) {
          console.log('\\n🔗 Work-related navigation:');
          workLinks.slice(0, 10).forEach(link => {
            console.log(`  📎 ${link.text} → ${link.href}`);
          });
        }

        // Display API endpoints
        if (workAnalysis.apiEndpoints.length > 0) {
          console.log('\\n📡 API endpoints discovered:');
          workAnalysis.apiEndpoints.slice(0, 10).forEach(endpoint => {
            console.log(`  🔌 ${endpoint}`);
          });
        }

        // Display work features
        if (workAnalysis.workFeatures.length > 0) {
          console.log('\\n⚡ Work features detected:');
          workAnalysis.workFeatures.forEach(feature => {
            console.log(`  🎯 ${feature.feature}: ${feature.occurrences} occurrences`);
          });
        }

        return workAnalysis;

      } else if (workResponse.statusCode === 302) {
        console.log('❌ Still redirected - login may have failed');
        return null;
      } else {
        console.log(`❌ Unexpected status: ${workResponse.statusCode}`);
        return null;
      }

    } catch (error) {
      console.log(`💥 Error exploring work module: ${error.message}`);
      return null;
    }
  }

  async exploreSubModules(baseAnalysis) {
    console.log('\\n🔍 Exploring work sub-modules...');

    const subModules = [
      '/work/projects',
      '/work/tasks', 
      '/work/calendar',
      '/work/reports',
      '/work/teams',
      '/work/dashboard',
      '/work/settings'
    ];

    const subModuleAnalysis = [];

    for (const subModule of subModules) {
      try {
        console.log(`\\n📦 Exploring ${subModule}...`);
        
        const response = await this.makeRequest(`https://trial.1office.vn${subModule}`);
        console.log(`  📊 Status: ${response.statusCode}`);

        if (response.statusCode === 200) {
          const analysis = this.analyzeHTML(response.body, response.url);
          
          subModuleAnalysis.push({
            path: subModule,
            status: response.statusCode,
            title: analysis.title,
            apiEndpoints: analysis.apiEndpoints,
            forms: analysis.forms.length,
            links: analysis.links.length,
            workFeatures: analysis.workFeatures
          });

          console.log(`  📄 Title: ${analysis.title}`);
          console.log(`  📡 API endpoints: ${analysis.apiEndpoints.length}`);
          console.log(`  📝 Forms: ${analysis.forms.length}`);

          // Save sub-module HTML
          fs.writeFileSync(`work-submodule-${subModule.replace(/\//g, '-')}.html`, response.body);

        } else {
          subModuleAnalysis.push({
            path: subModule,
            status: response.statusCode,
            accessible: false
          });
          console.log(`  ❌ Not accessible`);
        }

      } catch (error) {
        console.log(`  💥 Error: ${error.message}`);
        subModuleAnalysis.push({
          path: subModule,
          error: error.message
        });
      }
    }

    return subModuleAnalysis;
  }

  async run() {
    console.log('🚀 Starting authenticated analysis of trial.1office.vn/work...');

    const results = {
      timestamp: new Date().toISOString(),
      credentials: this.credentials,
      loginSuccessful: false,
      workModuleAnalysis: null,
      subModules: [],
      summary: {}
    };

    try {
      // Step 1: Login
      results.loginSuccessful = await this.login();
      
      if (!results.loginSuccessful) {
        console.log('❌ Login failed, cannot proceed with authenticated analysis');
        return results;
      }

      // Step 2: Explore main work module
      results.workModuleAnalysis = await this.exploreWorkModule();

      if (!results.workModuleAnalysis) {
        console.log('❌ Could not access work module');
        return results;
      }

      // Step 3: Explore sub-modules
      results.subModules = await this.exploreSubModules(results.workModuleAnalysis);

      // Step 4: Generate summary
      const accessibleSubModules = results.subModules.filter(m => m.status === 200);
      const totalApiEndpoints = results.subModules.reduce((sum, m) => sum + (m.apiEndpoints?.length || 0), 0);

      results.summary = {
        loginSuccessful: results.loginSuccessful,
        workModuleAccessible: !!results.workModuleAnalysis,
        totalSubModules: results.subModules.length,
        accessibleSubModules: accessibleSubModules.length,
        totalApiEndpoints: totalApiEndpoints + (results.workModuleAnalysis?.apiEndpoints?.length || 0),
        workFeatures: results.workModuleAnalysis?.workFeatures?.length || 0,
        uiComponents: results.workModuleAnalysis?.uiComponents?.length || 0
      };

      console.log('\\n📊 Final Summary:');
      console.log(`🔐 Login successful: ${results.summary.loginSuccessful ? '✅' : '❌'}`);
      console.log(`📦 Work module accessible: ${results.summary.workModuleAccessible ? '✅' : '❌'}`);
      console.log(`🔧 Sub-modules accessible: ${results.summary.accessibleSubModules}/${results.summary.totalSubModules}`);
      console.log(`📡 Total API endpoints: ${results.summary.totalApiEndpoints}`);
      console.log(`⚡ Work features detected: ${results.summary.workFeatures}`);
      console.log(`🎨 UI components: ${results.summary.uiComponents}`);

      // Save comprehensive results
      fs.writeFileSync('authenticated-work-analysis.json', JSON.stringify(results, null, 2));
      console.log('\\n💾 Authenticated analysis saved to authenticated-work-analysis.json');

      console.log('\\n✅ Authenticated analysis completed successfully!');

    } catch (error) {
      console.error('💥 Fatal error:', error.message);
      results.error = error.message;
    }

    return results;
  }
}

// Run the authenticated analysis
const analyzer = new AuthenticatedAnalyzer();
analyzer.run()
  .then(results => {
    console.log('\\n🎉 Authenticated analysis complete!');
  })
  .catch(error => {
    console.error('💥 Analysis failed:', error.message);
    process.exit(1);
  });
