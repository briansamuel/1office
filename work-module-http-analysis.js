const https = require('https');
const fs = require('fs');
const zlib = require('zlib');
const { URL } = require('url');

/**
 * HTTP-based analysis of trial.1office.vn/work module
 * Since Playwright requires GUI dependencies not available in container
 */

async function fetchWithRedirects(url, maxRedirects = 5, cookies = '') {
  return new Promise((resolve, reject) => {
    const options = {
      headers: {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language': 'en-US,en;q=0.5',
        'Accept-Encoding': 'gzip, deflate, br',
        'Connection': 'keep-alive',
        'Upgrade-Insecure-Requests': '1',
        'Cookie': cookies
      }
    };

    function makeRequest(currentUrl, redirectCount = 0) {
      if (redirectCount > maxRedirects) {
        reject(new Error('Too many redirects'));
        return;
      }

      https.get(currentUrl, options, (res) => {
        // Handle redirects
        if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
          console.log(`📍 Redirect ${res.statusCode}: ${currentUrl} -> ${res.headers.location}`);
          const redirectUrl = new URL(res.headers.location, currentUrl).href;
          makeRequest(redirectUrl, redirectCount + 1);
          return;
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
            console.log('⚠️ Decompression failed, using raw buffer');
            body = buffer.toString();
          }

          resolve({
            statusCode: res.statusCode,
            headers: res.headers,
            body: body,
            finalUrl: currentUrl,
            cookies: res.headers['set-cookie'] || []
          });
        });
      }).on('error', (err) => {
        reject(err);
      });
    }

    makeRequest(url);
  });
}

function analyzeHTML(html, url) {
  const analysis = {
    url: url,
    title: '',
    metaTags: [],
    cssFiles: [],
    jsFiles: [],
    inlineStyles: [],
    inlineScripts: [],
    forms: [],
    inputs: [],
    buttons: [],
    links: [],
    modules: [],
    navigation: [],
    apiEndpoints: [],
    frameworks: [],
    securityFeatures: {
      csrfTokens: [],
      cookies: [],
      headers: []
    }
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

  // Extract inline scripts and analyze for API endpoints
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

      // Look for API endpoints in JavaScript
      const apiMatches = content.match(/['"`]\/api\/[^'"`\s]+['"`]/g) || [];
      apiMatches.forEach(match => {
        const endpoint = match.replace(/['"`]/g, '');
        if (!analysis.apiEndpoints.includes(endpoint)) {
          analysis.apiEndpoints.push(endpoint);
        }
      });

      // Look for base URLs
      const baseUrlMatches = content.match(/baseURL\s*[:=]\s*['"`]([^'"`]+)['"`]/g) || [];
      baseUrlMatches.forEach(match => {
        const urlMatch = match.match(/['"`]([^'"`]+)['"`]/);
        if (urlMatch) {
          analysis.apiEndpoints.push(urlMatch[1]);
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
    
    analysis.inputs.push({
      index: index + 1,
      type: typeMatch ? typeMatch[1] : 'text',
      name: nameMatch ? nameMatch[1] : '',
      id: idMatch ? idMatch[1] : ''
    });
  });

  // Extract links for navigation analysis
  const linkMatches = html.match(/<a[^>]*href=["']([^"']+)["'][^>]*>(.*?)<\/a>/gis) || [];
  linkMatches.forEach((link, index) => {
    const hrefMatch = link.match(/href=["']([^"']+)["']/i);
    const textMatch = link.match(/<a[^>]*>(.*?)<\/a>/is);
    
    if (hrefMatch && textMatch) {
      const href = hrefMatch[1];
      const text = textMatch[1].replace(/<[^>]*>/g, '').trim();
      
      if (text.length > 0 && href.length > 0 && !href.startsWith('javascript:')) {
        analysis.links.push({
          href: href,
          text: text.substring(0, 100),
          isModule: href.includes('/work') || href.includes('/hrm') || 
                   href.includes('/crm') || href.includes('/warehouse')
        });
      }
    }
  });

  // Look for module indicators
  const moduleKeywords = ['work', 'hrm', 'crm', 'warehouse', 'finance', 'admin', 'dashboard'];
  moduleKeywords.forEach(keyword => {
    const regex = new RegExp(`\\b${keyword}\\b`, 'gi');
    const matches = html.match(regex) || [];
    if (matches.length > 0) {
      analysis.modules.push({
        name: keyword,
        occurrences: matches.length
      });
    }
  });

  // Detect frameworks
  const htmlLower = html.toLowerCase();
  if (htmlLower.includes('vue.js') || htmlLower.includes('vue@')) {
    analysis.frameworks.push('Vue.js');
  }
  if (htmlLower.includes('react')) {
    analysis.frameworks.push('React');
  }
  if (htmlLower.includes('angular')) {
    analysis.frameworks.push('Angular');
  }
  if (htmlLower.includes('jquery')) {
    analysis.frameworks.push('jQuery');
  }
  if (htmlLower.includes('bootstrap')) {
    analysis.frameworks.push('Bootstrap');
  }
  if (htmlLower.includes('laravel')) {
    analysis.frameworks.push('Laravel');
  }

  // Look for CSRF tokens
  const csrfMatches = html.match(/csrf[^"']*["']([^"']+)["']/gi) || [];
  csrfMatches.forEach(match => {
    analysis.securityFeatures.csrfTokens.push(match);
  });

  return analysis;
}

async function analyzeWorkModule() {
  console.log('🚀 Starting HTTP-based analysis of trial.1office.vn/work...');
  
  const analysisData = {
    timestamp: new Date().toISOString(),
    pages: [],
    modules: [],
    apiEndpoints: [],
    securityAnalysis: {},
    errors: []
  };

  try {
    // Step 1: Try to access work module directly
    console.log('📍 Step 1: Accessing /work module...');
    
    const workResponse = await fetchWithRedirects('https://trial.1office.vn/work');
    console.log(`📊 Work module status: ${workResponse.statusCode}`);
    console.log(`🔗 Final URL: ${workResponse.finalUrl}`);
    
    // Save raw HTML
    fs.writeFileSync('work-module-raw.html', workResponse.body);
    console.log('💾 Raw HTML saved to work-module-raw.html');
    
    // Analyze work module page
    const workAnalysis = analyzeHTML(workResponse.body, workResponse.finalUrl);
    analysisData.pages.push({
      url: '/work',
      ...workAnalysis
    });
    
    console.log(`📄 Work module title: "${workAnalysis.title}"`);
    console.log(`🔗 Links found: ${workAnalysis.links.length}`);
    console.log(`📜 JavaScript files: ${workAnalysis.jsFiles.length}`);
    console.log(`🎨 CSS files: ${workAnalysis.cssFiles.length}`);
    
    // Step 2: Try other modules
    console.log('\\n📍 Step 2: Exploring other modules...');
    
    const modulesToTest = [
      '/hrm', '/crm', '/warehouse', '/finance', '/admin', 
      '/dashboard', '/login', '/profile', '/settings'
    ];
    
    for (const modulePath of modulesToTest) {
      try {
        console.log(`🔍 Testing module: ${modulePath}`);
        
        const moduleResponse = await fetchWithRedirects(`https://trial.1office.vn${modulePath}`);
        const moduleAnalysis = analyzeHTML(moduleResponse.body, moduleResponse.finalUrl);
        
        analysisData.pages.push({
          url: modulePath,
          status: moduleResponse.statusCode,
          accessible: moduleResponse.statusCode === 200,
          ...moduleAnalysis
        });
        
        console.log(`  📊 ${modulePath}: ${moduleResponse.statusCode} - "${moduleAnalysis.title}"`);
        
        // Save HTML for accessible modules
        if (moduleResponse.statusCode === 200) {
          fs.writeFileSync(`module-${modulePath.replace('/', '')}.html`, moduleResponse.body);
        }
        
      } catch (error) {
        console.log(`  ❌ ${modulePath}: Error - ${error.message}`);
        analysisData.errors.push({
          module: modulePath,
          error: error.message
        });
      }
    }
    
    // Step 3: Analyze API endpoints
    console.log('\\n📍 Step 3: API endpoint discovery...');
    
    const allApiEndpoints = new Set();
    analysisData.pages.forEach(page => {
      page.apiEndpoints?.forEach(endpoint => allApiEndpoints.add(endpoint));
    });
    
    console.log(`🔗 Unique API endpoints found: ${allApiEndpoints.size}`);
    Array.from(allApiEndpoints).slice(0, 20).forEach(endpoint => {
      console.log(`  📡 ${endpoint}`);
    });
    
    analysisData.apiEndpoints = Array.from(allApiEndpoints);
    
    // Step 4: Module analysis
    console.log('\\n📍 Step 4: Module analysis...');
    
    const moduleStats = {};
    analysisData.pages.forEach(page => {
      page.modules?.forEach(module => {
        if (!moduleStats[module.name]) {
          moduleStats[module.name] = {
            name: module.name,
            totalOccurrences: 0,
            pagesFound: []
          };
        }
        moduleStats[module.name].totalOccurrences += module.occurrences;
        moduleStats[module.name].pagesFound.push(page.url);
      });
    });
    
    console.log('📦 Module statistics:');
    Object.values(moduleStats).forEach(module => {
      console.log(`  🧩 ${module.name}: ${module.totalOccurrences} occurrences in ${module.pagesFound.length} pages`);
    });
    
    analysisData.modules = Object.values(moduleStats);
    
    // Step 5: Security analysis
    console.log('\\n📍 Step 5: Security analysis...');
    
    const securityFeatures = {
      csrfProtection: false,
      httpsOnly: true,
      cookiesSeen: [],
      securityHeaders: []
    };
    
    analysisData.pages.forEach(page => {
      if (page.securityFeatures?.csrfTokens?.length > 0) {
        securityFeatures.csrfProtection = true;
      }
    });
    
    console.log(`🛡️ CSRF Protection: ${securityFeatures.csrfProtection ? '✅' : '❌'}`);
    console.log(`🔒 HTTPS Only: ${securityFeatures.httpsOnly ? '✅' : '❌'}`);
    
    analysisData.securityAnalysis = securityFeatures;
    
    // Step 6: Framework analysis
    console.log('\\n📍 Step 6: Framework analysis...');
    
    const allFrameworks = new Set();
    analysisData.pages.forEach(page => {
      page.frameworks?.forEach(fw => allFrameworks.add(fw));
    });
    
    console.log('🔧 Detected frameworks:');
    Array.from(allFrameworks).forEach(fw => {
      console.log(`  ✅ ${fw}`);
    });
    
    // Final summary
    console.log('\\n📊 Final Summary:');
    console.log(`📄 Total pages analyzed: ${analysisData.pages.length}`);
    console.log(`🔗 Total API endpoints: ${analysisData.apiEndpoints.length}`);
    console.log(`📦 Modules detected: ${analysisData.modules.length}`);
    console.log(`❌ Errors encountered: ${analysisData.errors.length}`);
    
    // Save comprehensive analysis
    fs.writeFileSync('work-module-http-analysis.json', JSON.stringify(analysisData, null, 2));
    console.log('\\n💾 Comprehensive analysis saved to work-module-http-analysis.json');
    
    console.log('\\n✅ HTTP-based analysis completed successfully!');
    
  } catch (error) {
    console.error('💥 Fatal error during analysis:', error.message);
    analysisData.errors.push({
      step: 'main',
      error: error.message
    });
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
