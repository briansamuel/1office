class LoginPage {
  constructor(page) {
    this.page = page;
    this.url = 'https://trial.1office.vn';
    
    // Các selector có thể có cho form đăng nhập
    this.emailSelectors = [
      'input[type="email"]',
      'input[name="email"]',
      'input[name="username"]',
      'input[placeholder*="email" i]',
      'input[placeholder*="tài khoản" i]',
      'input[placeholder*="Email" i]',
      '#email',
      '#username',
      '.email-input',
      '.username-input',
      '[data-testid="email"]',
      '[data-testid="username"]'
    ];

    this.passwordSelectors = [
      'input[type="password"]',
      'input[name="password"]',
      'input[placeholder*="password" i]',
      'input[placeholder*="mật khẩu" i]',
      'input[placeholder*="Password" i]',
      '#password',
      '.password-input',
      '[data-testid="password"]'
    ];

    this.loginButtonSelectors = [
      'button[type="submit"]',
      'input[type="submit"]',
      'button:has-text("Đăng nhập")',
      'button:has-text("Login")',
      'button:has-text("Sign in")',
      'button:has-text("ĐĂNG NHẬP")',
      '.login-btn',
      '.btn-login',
      '#login-button',
      '.submit-btn',
      '[data-testid="login-button"]'
    ];

    this.errorSelectors = [
      '.error-message',
      '.alert-danger',
      '.text-red',
      '.invalid-feedback',
      '[class*="error"]',
      '.notification-error',
      '.toast-error',
      ':has-text("sai")',
      ':has-text("incorrect")',
      ':has-text("invalid")',
      ':has-text("không đúng")',
      ':has-text("thất bại")'
    ];

    this.successIndicators = [
      '.dashboard',
      '.main-menu',
      '.sidebar',
      '.navbar',
      '.user-info',
      '.profile',
      '.avatar',
      '.welcome',
      '[class*="dashboard"]',
      '[class*="main-content"]'
    ];
  }

  async goto() {
    await this.page.goto(this.url);
    await this.page.waitForLoadState('networkidle');
  }

  async findElement(selectors) {
    for (const selector of selectors) {
      try {
        const element = this.page.locator(selector).first();
        if (await element.isVisible({ timeout: 1000 })) {
          return element;
        }
      } catch (e) {
        continue;
      }
    }
    return null;
  }

  async getEmailField() {
    return await this.findElement(this.emailSelectors);
  }

  async getPasswordField() {
    return await this.findElement(this.passwordSelectors);
  }

  async getLoginButton() {
    return await this.findElement(this.loginButtonSelectors);
  }

  async login(email, password) {
    // Tìm các trường input
    const emailField = await this.getEmailField();
    const passwordField = await this.getPasswordField();
    const loginButton = await this.getLoginButton();

    if (!emailField) {
      throw new Error('Không tìm thấy trường email/username');
    }

    if (!passwordField) {
      throw new Error('Không tìm thấy trường password');
    }

    if (!loginButton) {
      throw new Error('Không tìm thấy nút đăng nhập');
    }

    // Điền thông tin
    await emailField.clear();
    await emailField.fill(email);
    
    await passwordField.clear();
    await passwordField.fill(password);

    // Click đăng nhập
    await loginButton.click();

    // Chờ response
    await this.page.waitForLoadState('networkidle');
  }

  async isLoginSuccessful() {
    // Kiểm tra URL thay đổi
    if (this.page.url() !== this.url && !this.page.url().includes('/login')) {
      return true;
    }

    // Kiểm tra các indicator thành công
    for (const selector of this.successIndicators) {
      try {
        const element = this.page.locator(selector);
        if (await element.isVisible({ timeout: 2000 })) {
          return true;
        }
      } catch (e) {
        continue;
      }
    }

    // Kiểm tra không còn form login
    const passwordField = await this.getPasswordField();
    if (!passwordField || !(await passwordField.isVisible())) {
      return true;
    }

    return false;
  }

  async getErrorMessage() {
    for (const selector of this.errorSelectors) {
      try {
        const element = this.page.locator(selector);
        if (await element.isVisible({ timeout: 2000 })) {
          return await element.textContent();
        }
      } catch (e) {
        continue;
      }
    }
    return null;
  }

  async captureLoginForm() {
    await this.page.screenshot({ 
      path: `screenshots/login-form-${Date.now()}.png`, 
      fullPage: true 
    });

    // Lấy thông tin về form structure
    const formInfo = {
      url: this.page.url(),
      title: await this.page.title(),
      forms: await this.page.locator('form').count(),
      inputs: [],
      buttons: []
    };

    // Lấy thông tin inputs
    const inputs = await this.page.locator('input').all();
    for (const input of inputs) {
      const info = {
        type: await input.getAttribute('type'),
        name: await input.getAttribute('name'),
        id: await input.getAttribute('id'),
        placeholder: await input.getAttribute('placeholder'),
        class: await input.getAttribute('class'),
        visible: await input.isVisible()
      };
      formInfo.inputs.push(info);
    }

    // Lấy thông tin buttons
    const buttons = await this.page.locator('button').all();
    for (const button of buttons) {
      const info = {
        type: await button.getAttribute('type'),
        text: (await button.textContent())?.trim(),
        class: await button.getAttribute('class'),
        visible: await button.isVisible()
      };
      formInfo.buttons.push(info);
    }

    return formInfo;
  }

  async waitForPageLoad() {
    await this.page.waitForLoadState('networkidle');
    await this.page.waitForTimeout(1000); // Thêm delay nhỏ
  }
}

module.exports = LoginPage;
