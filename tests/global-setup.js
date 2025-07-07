const { chromium } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

async function globalSetup() {
  console.log('🚀 Starting global setup...');
  
  // Tạo thư mục screenshots nếu chưa có
  const screenshotsDir = path.join(__dirname, '..', 'screenshots');
  if (!fs.existsSync(screenshotsDir)) {
    fs.mkdirSync(screenshotsDir, { recursive: true });
    console.log('📁 Created screenshots directory');
  }

  // Tạo thư mục test-results nếu chưa có
  const testResultsDir = path.join(__dirname, '..', 'test-results');
  if (!fs.existsSync(testResultsDir)) {
    fs.mkdirSync(testResultsDir, { recursive: true });
    console.log('📁 Created test-results directory');
  }

  // Kiểm tra kết nối đến trial.1office.vn
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  try {
    console.log('🌐 Checking connection to trial.1office.vn...');
    
    const response = await page.goto('https://trial.1office.vn', {
      waitUntil: 'networkidle',
      timeout: 30000
    });
    
    if (response && response.ok()) {
      console.log('✅ Successfully connected to trial.1office.vn');
      console.log(`   Status: ${response.status()}`);
      console.log(`   URL: ${page.url()}`);
      console.log(`   Title: ${await page.title()}`);
      
      // Chụp screenshot trang chủ
      await page.screenshot({ 
        path: path.join(screenshotsDir, 'homepage-initial.png'),
        fullPage: true 
      });
      
      // Lưu thông tin cơ bản về trang
      const pageInfo = {
        url: page.url(),
        title: await page.title(),
        timestamp: new Date().toISOString(),
        status: response.status(),
        headers: await response.allHeaders()
      };
      
      fs.writeFileSync(
        path.join(testResultsDir, 'site-info.json'), 
        JSON.stringify(pageInfo, null, 2)
      );
      
    } else {
      console.log('❌ Failed to connect to trial.1office.vn');
      console.log(`   Status: ${response ? response.status() : 'No response'}`);
    }
    
  } catch (error) {
    console.log('❌ Error connecting to trial.1office.vn:');
    console.log(`   ${error.message}`);
    
    // Vẫn tiếp tục test, có thể là vấn đề tạm thời
  } finally {
    await browser.close();
  }
  
  console.log('✅ Global setup completed');
}

module.exports = globalSetup;
