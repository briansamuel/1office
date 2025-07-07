const https = require('https');
const fs = require('fs');
const { URL } = require('url');

async function fetchPage(url, maxRedirects = 5) {
  return new Promise((resolve, reject) => {
    const options = {
      headers: {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language': 'en-US,en;q=0.5',
        'Accept-Encoding': 'gzip, deflate, br',
        'Connection': 'keep-alive',
        'Upgrade-Insecure-Requests': '1'
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

        let data = '';

        res.on('data', (chunk) => {
          data += chunk;
        });

        res.on('end', () => {
          resolve({
            statusCode: res.statusCode,
            headers: res.headers,
            body: data,
            finalUrl: currentUrl
          });
        });
      }).on('error', (err) => {
        reject(err);
      });
    }

    makeRequest(url);
  });
}

function analyzeHTML(html) {
  const analysis = {
    title: '',
    metaTags: [],
    cssFiles: [],
    jsFiles: [],
    inlineStyles: [],
    inlineScripts: [],
    forms: [],
    inputs: [],
    buttons: [],
    frameworks: [],
    structure: {}
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

  // Extract inline styles
  const styleMatches = html.match(/<style[^>]*>(.*?)<\/style>/gis) || [];
  styleMatches.forEach((style, index) => {
    const contentMatch = style.match(/<style[^>]*>(.*?)<\/style>/is);
    if (contentMatch) {
      analysis.inlineStyles.push({
        index: index + 1,
        content: contentMatch[1].trim().substring(0, 500) + '...',
        length: contentMatch[1].trim().length
      });
    }
  });

  // Extract inline scripts
  const scriptMatches = html.match(/<script(?![^>]*src=)[^>]*>(.*?)<\/script>/gis) || [];
  scriptMatches.forEach((script, index) => {
    const contentMatch = script.match(/<script[^>]*>(.*?)<\/script>/is);
    if (contentMatch) {
      analysis.inlineScripts.push({
        index: index + 1,
        content: contentMatch[1].trim().substring(0, 500) + '...',
        length: contentMatch[1].trim().length
      });
    }
  });

  // Extract forms
  const formMatches = html.match(/<form[^>]*>.*?<\/form>/gis) || [];
  formMatches.forEach((form, index) => {
    const actionMatch = form.match(/action=["']([^"']+)["']/i);
    const methodMatch = form.match(/method=["']([^"']+)["']/i);
    const idMatch = form.match(/id=["']([^"']+)["']/i);
    const classMatch = form.match(/class=["']([^"']+)["']/i);
    
    analysis.forms.push({
      index: index + 1,
      action: actionMatch ? actionMatch[1] : '',
      method: methodMatch ? methodMatch[1] : 'GET',
      id: idMatch ? idMatch[1] : '',
      className: classMatch ? classMatch[1] : '',
      html: form.substring(0, 300) + '...'
    });
  });

  // Extract inputs
  const inputMatches = html.match(/<input[^>]*>/gi) || [];
  inputMatches.forEach((input, index) => {
    const typeMatch = input.match(/type=["']([^"']+)["']/i);
    const nameMatch = input.match(/name=["']([^"']+)["']/i);
    const idMatch = input.match(/id=["']([^"']+)["']/i);
    const placeholderMatch = input.match(/placeholder=["']([^"']+)["']/i);
    const classMatch = input.match(/class=["']([^"']+)["']/i);
    const requiredMatch = input.match(/required/i);
    
    analysis.inputs.push({
      index: index + 1,
      type: typeMatch ? typeMatch[1] : 'text',
      name: nameMatch ? nameMatch[1] : '',
      id: idMatch ? idMatch[1] : '',
      placeholder: placeholderMatch ? placeholderMatch[1] : '',
      className: classMatch ? classMatch[1] : '',
      required: !!requiredMatch
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

  // Detect frameworks
  if (html.includes('vue.js') || html.includes('Vue.js') || html.includes('vue@')) {
    analysis.frameworks.push('Vue.js');
  }
  if (html.includes('react') || html.includes('React')) {
    analysis.frameworks.push('React');
  }
  if (html.includes('angular') || html.includes('Angular')) {
    analysis.frameworks.push('Angular');
  }
  if (html.includes('jquery') || html.includes('jQuery')) {
    analysis.frameworks.push('jQuery');
  }
  if (html.includes('bootstrap') || html.includes('Bootstrap')) {
    analysis.frameworks.push('Bootstrap');
  }
  if (html.includes('tailwind') || html.includes('Tailwind')) {
    analysis.frameworks.push('Tailwind CSS');
  }
  if (html.includes('laravel') || html.includes('Laravel')) {
    analysis.frameworks.push('Laravel');
  }

  return analysis;
}

async function analyzeTrialOffice() {
  console.log('🚀 Starting analysis of trial.1office.vn...');
  
  try {
    console.log('📍 Fetching trial.1office.vn...');
    const response = await fetchPage('https://trial.1office.vn');
    
    console.log(`📊 Response Status: ${response.statusCode}`);
    console.log(`📦 Content-Type: ${response.headers['content-type']}`);
    console.log(`📏 Content-Length: ${response.headers['content-length'] || 'unknown'}`);
    console.log(`🔗 Final URL: ${response.finalUrl}`);

    if (response.statusCode !== 200) {
      console.log('❌ Failed to fetch page');
      console.log('Response body preview:', response.body.substring(0, 500));
      return;
    }
    
    // Save raw HTML
    fs.writeFileSync('trial-office-raw.html', response.body);
    console.log('💾 Raw HTML saved to trial-office-raw.html');
    
    // Analyze HTML
    console.log('\\n🔍 Analyzing HTML content...');
    const analysis = analyzeHTML(response.body);
    
    // Display results
    console.log(`\\n📄 Page Title: ${analysis.title}`);
    
    console.log(`\\n🏷️ Meta Tags (${analysis.metaTags.length}):`);
    analysis.metaTags.slice(0, 10).forEach(meta => {
      console.log(`  ${meta.name}: ${meta.content.substring(0, 100)}${meta.content.length > 100 ? '...' : ''}`);
    });
    
    console.log(`\\n🎨 CSS Files (${analysis.cssFiles.length}):`);
    analysis.cssFiles.forEach((css, index) => {
      console.log(`  ${index + 1}. ${css}`);
    });
    
    console.log(`\\n📜 JavaScript Files (${analysis.jsFiles.length}):`);
    analysis.jsFiles.forEach((js, index) => {
      console.log(`  ${index + 1}. ${js}`);
    });
    
    console.log(`\\n🎨 Inline Styles (${analysis.inlineStyles.length}):`);
    analysis.inlineStyles.forEach(style => {
      console.log(`  Block ${style.index}: ${style.length} characters`);
    });
    
    console.log(`\\n📜 Inline Scripts (${analysis.inlineScripts.length}):`);
    analysis.inlineScripts.forEach(script => {
      console.log(`  Block ${script.index}: ${script.length} characters`);
    });
    
    console.log(`\\n📝 Forms (${analysis.forms.length}):`);
    analysis.forms.forEach(form => {
      console.log(`  Form ${form.index}:`);
      console.log(`    Action: ${form.action}`);
      console.log(`    Method: ${form.method}`);
      console.log(`    ID: ${form.id}`);
      console.log(`    Class: ${form.className}`);
    });
    
    console.log(`\\n🔤 Input Fields (${analysis.inputs.length}):`);
    analysis.inputs.forEach(input => {
      console.log(`  Input ${input.index}: type="${input.type}" name="${input.name}" id="${input.id}" placeholder="${input.placeholder}" required=${input.required}`);
    });
    
    console.log(`\\n🔘 Buttons (${analysis.buttons.length}):`);
    analysis.buttons.forEach(button => {
      console.log(`  Button ${button.index}: "${button.text}" type="${button.type}" class="${button.className}"`);
    });
    
    console.log(`\\n🔧 Detected Frameworks (${analysis.frameworks.length}):`);
    if (analysis.frameworks.length > 0) {
      analysis.frameworks.forEach(framework => {
        console.log(`  ✅ ${framework}`);
      });
    } else {
      console.log('  ❌ No common frameworks detected');
    }
    
    // Look for login form specifically
    console.log('\\n🔐 LOGIN FORM ANALYSIS:');
    const emailInputs = analysis.inputs.filter(input => 
      input.type === 'email' || 
      input.name.toLowerCase().includes('email') || 
      input.name.toLowerCase().includes('username') ||
      input.placeholder.toLowerCase().includes('email')
    );
    
    const passwordInputs = analysis.inputs.filter(input => input.type === 'password');
    
    const submitButtons = analysis.buttons.filter(button => 
      button.type === 'submit' || 
      button.text.toLowerCase().includes('login') ||
      button.text.toLowerCase().includes('đăng nhập') ||
      button.className.toLowerCase().includes('login')
    );
    
    console.log(`Email/Username fields found: ${emailInputs.length}`);
    emailInputs.forEach(input => {
      console.log(`  - ${input.type} field: name="${input.name}" id="${input.id}" placeholder="${input.placeholder}"`);
    });
    
    console.log(`Password fields found: ${passwordInputs.length}`);
    passwordInputs.forEach(input => {
      console.log(`  - Password field: name="${input.name}" id="${input.id}" placeholder="${input.placeholder}"`);
    });
    
    console.log(`Submit buttons found: ${submitButtons.length}`);
    submitButtons.forEach(button => {
      console.log(`  - Button: "${button.text}" type="${button.type}" class="${button.className}"`);
    });
    
    // Save analysis
    const fullAnalysis = {
      url: 'https://trial.1office.vn',
      timestamp: new Date().toISOString(),
      response: {
        statusCode: response.statusCode,
        headers: response.headers
      },
      analysis,
      loginFormAnalysis: {
        emailInputs,
        passwordInputs,
        submitButtons
      }
    };
    
    fs.writeFileSync('trial-office-analysis.json', JSON.stringify(fullAnalysis, null, 2));
    console.log('\\n💾 Complete analysis saved to trial-office-analysis.json');
    
    console.log('\\n✅ Analysis completed successfully!');
    
  } catch (error) {
    console.error('💥 Error during analysis:', error.message);
  }
}

// Run the analysis
analyzeTrialOffice().catch(console.error);
