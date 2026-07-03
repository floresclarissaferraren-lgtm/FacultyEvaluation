// =====================================================================
// FULL-PAGE NAVIGATION
// =====================================================================
(function() {
  const pages   = Array.from(document.querySelectorAll('.page'));
  const dots    = Array.from(document.querySelectorAll('.dot'));
  const LIGHT   = [1, 2, 3]; // pages with light background (dot & ql style)
  let current   = 0;
  let animating = false;

  function goTo(index) {
    if (index === current || animating || index < 0 || index >= pages.length) return;
    animating = true;

    const dir = index > current ? 'up' : 'down';
    pages[current].classList.add(dir === 'up' ? 'exit-up' : 'exit-down');
    pages[current].classList.remove('active');

    setTimeout(() => {
      pages[current].classList.remove('exit-up', 'exit-down');
      current = index;
      pages[current].classList.add('active');

      // dot style
      dots.forEach((d, i) => {
        d.classList.toggle('active', i === current);
        d.classList.toggle('dark', LIGHT.includes(current));
      });

      animating = false;
    }, 30);

    // trigger enter animation
    setTimeout(() => {}, 0);
  }

  // Init first page
  pages[0].classList.add('active');

  // Dot clicks
  dots.forEach(d => {
    d.addEventListener('click', () => goTo(+d.dataset.page));
  });

  // Nav links & page-links
  document.querySelectorAll('[data-page]').forEach(el => {
    if (el.classList.contains('dot')) return;
    el.addEventListener('click', e => {
      e.preventDefault();
      goTo(+el.dataset.page);
    });
  });

  // Mouse wheel
  let wheelTimeout;
  window.addEventListener('wheel', e => {
    clearTimeout(wheelTimeout);
    wheelTimeout = setTimeout(() => {
      goTo(e.deltaY > 0 ? current + 1 : current - 1);
    }, 50);
  }, { passive: true });

  // Touch swipe
  let touchStartY = 0;
  window.addEventListener('touchstart', e => { touchStartY = e.touches[0].clientY; }, { passive: true });
  window.addEventListener('touchend', e => {
    const diff = touchStartY - e.changedTouches[0].clientY;
    if (Math.abs(diff) > 40) goTo(diff > 0 ? current + 1 : current - 1);
  });

  // Keyboard
  window.addEventListener('keydown', e => {
    if (e.key === 'ArrowDown' || e.key === 'PageDown') goTo(current + 1);
    if (e.key === 'ArrowUp'   || e.key === 'PageUp')   goTo(current - 1);
  });

  // Expose for nav hash links
  window._goToPage = goTo;
})();

const modals = {
  login:       document.getElementById("loginModal"),
  forgot:      document.getElementById("forgotModal"),
  otp:         document.getElementById("otpModal"),
  newPassword: document.getElementById("newPasswordModal")
};

const hamburger = document.getElementById("hamburger");
const navMenu = document.getElementById("navMenu");
const navbar = document.querySelector("nav"); // added navbar reference

// ===================== FUNCTIONS =====================
function openModal(modal) {
  if (modal) {
    modal.classList.add("show");
    document.body.classList.add("modal-open");
    if (navbar) navbar.classList.add("hidden"); // hide navbar when modal opens
  }
}

function closeModal(modal) {
  if (modal) {
    modal.classList.remove("show");
    document.body.classList.remove("modal-open");
    if (navbar) navbar.classList.remove("hidden"); // show navbar when modal closes
  }
}

// ===================== Switch between login and forgot modals =====================
function switchModal(closeModalEl, openModalEl) {
  if (closeModalEl && openModalEl) {
    closeModalEl.classList.remove("show");
    openModalEl.classList.add("show");
  }
}

// ===================== Toggle hamburger Buttons =====================
function toggleHamburgerMenu() {
  if (!navMenu || !hamburger) return;
  navMenu.classList.toggle("show");
  const icon = hamburger.querySelector(".material-icons");
  if (icon) icon.textContent = navMenu.classList.contains("show") ? "close" : "menu";
}

// ===================== Login Button =====================
const loginBtn = document.getElementById("loginBtn");
if (loginBtn) loginBtn.addEventListener("click", e => {
  e.preventDefault();
  openModal(modals.login);
});

const loginBtn2 = document.getElementById("loginBtn2");
if (loginBtn2) loginBtn2.addEventListener("click", e => {
  e.preventDefault();
  openModal(modals.login);
});

// ===================== Close Buttons =====================
const closeLogin = document.getElementById("closeLogin");
if (closeLogin) closeLogin.addEventListener("click", () => closeModal(modals.login));

const closeForgot = document.getElementById("closeForgot");
if (closeForgot) closeForgot.addEventListener("click", () => switchModal(modals.forgot, modals.login));

const closeOtp = document.getElementById("closeOtp");
if (closeOtp) closeOtp.addEventListener("click", () => { resetForgotFlow(); switchModal(modals.otp, modals.forgot); });

const closeNewPass = document.getElementById("closeNewPass");
if (closeNewPass) closeNewPass.addEventListener("click", () => { resetForgotFlow(); closeModal(modals.newPassword); });

// ===================== Forgot Button =====================
const openForgot = document.getElementById("openForgot");
if (openForgot) openForgot.addEventListener("click", e => {
  e.preventDefault();
  switchModal(modals.login, modals.forgot);
});

if (hamburger) hamburger.addEventListener("click", toggleHamburgerMenu);

// ===================== Unified Login form submit handler =====================
const loginForm = document.getElementById('loginForm');
if (loginForm) {
  loginForm.addEventListener('submit', async e => {
    e.preventDefault();
    const usernameInput = loginForm.querySelector('#username');
    const passwordInput = loginForm.querySelector('#password');
    const username = usernameInput?.value.trim();
    const password = passwordInput?.value.trim();

    // clear previous errors
    document.getElementById('usernameError').textContent = '';
    document.getElementById('passwordError').textContent = '';

    let hasError = false;

    if (!username) {
      document.getElementById('usernameError').textContent = 'Please enter Username/ID.';
      hasError = true;
    }

    if (!password) {
      document.getElementById('passwordError').textContent = 'Please enter Password.';
      hasError = true;
    }

    if (hasError) return;

    const fullscreenSpinner = document.getElementById('fullscreen-spinner');
    
    // Show full-page loading spinner
    fullscreenSpinner.classList.remove('hidden');

    // Predict loading type based on username prefix GC-
    if (username.toUpperCase().startsWith('GC-')) {
      fullscreenSpinner.classList.add('loading-student');
      fullscreenSpinner.classList.remove('loading-admin');
    } else {
      fullscreenSpinner.classList.add('loading-admin');
      fullscreenSpinner.classList.remove('loading-student');
    }

    try {
      const formData = new FormData();
      formData.append('username', username);
      formData.append('password', password);

      const res = await fetch('multi_login.php', { method: 'POST', body: formData });
      const data = await res.json();

      // Add minimum display time of 3 seconds so users can see the spinner
      const minDisplayTime = 3000;
      const startTime = Date.now();
      
      if (data.success) {
        if (data.role === 'student') {
          fullscreenSpinner.classList.add('loading-student');
          fullscreenSpinner.classList.remove('loading-admin');
          // Wait for minimum display time before redirecting
          const elapsedTime = Date.now() - startTime;
          if (elapsedTime < minDisplayTime) {
            setTimeout(() => {
              window.location.href = 'FacultyUser.php';
            }, minDisplayTime - elapsedTime);
          } else {
            window.location.href = 'FacultyUser.php';
          }
        } else if (data.role === 'faculty') {
          fullscreenSpinner.classList.add('loading-admin');
          fullscreenSpinner.classList.remove('loading-student');
          const elapsedTime = Date.now() - startTime;
          if (elapsedTime < minDisplayTime) {
            setTimeout(() => {
              window.location.href = 'FacultyInstructor.php';
            }, minDisplayTime - elapsedTime);
          } else {
            window.location.href = 'FacultyInstructor.php';
          }
        } else if (data.role === 'admin') {
          fullscreenSpinner.classList.add('loading-admin');
          fullscreenSpinner.classList.remove('loading-student');
          const elapsedTime = Date.now() - startTime;
          if (elapsedTime < minDisplayTime) {
            setTimeout(() => {
              window.location.href = 'FacultyAdmin.php';
            }, minDisplayTime - elapsedTime);
          } else {
            window.location.href = 'FacultyAdmin.php';
          }
        } else {
          document.getElementById('passwordError').textContent = 'Login successful, but role is unknown.';
          fullscreenSpinner.classList.add('hidden');
          fullscreenSpinner.classList.remove('loading-student', 'loading-admin');
        }
      } else {
        document.getElementById('passwordError').textContent = data.message || 'Login failed';
        fullscreenSpinner.classList.add('hidden');
        fullscreenSpinner.classList.remove('loading-student', 'loading-admin');
      }
    } catch (err) {
      console.error('Login error:', err);
      document.getElementById('passwordError').textContent = 'An error occurred. Please try again.';
      fullscreenSpinner.classList.add('hidden');
      fullscreenSpinner.classList.remove('loading-student', 'loading-admin');
    }
  });
}

// ===================== FORGOT PASSWORD - 3-STEP FLOW =====================

// Stores the verified email between steps
let _resetEmail = '';
let _resetEmail_code = '';

function resetForgotFlow() {
  _resetEmail = '';
  _resetEmail_code = '';
  const forgotForm = document.getElementById('forgotForm');
  const resetEmailInput = document.getElementById('resetEmail');
  const resetEmailError = document.getElementById('resetEmailError');
  const sendCodeBtn = document.getElementById('sendCodeBtn');

  if (forgotForm) forgotForm.reset();
  if (resetEmailError) { resetEmailError.textContent = ''; resetEmailError.style.display = 'none'; }
  if (sendCodeBtn) {
    sendCodeBtn.disabled = false;
    sendCodeBtn.innerHTML = '<i class="ph ph-paper-plane-right"></i>&nbsp; Send Code';
    sendCodeBtn.style.opacity = '1';
    sendCodeBtn.style.cursor = 'pointer';
  }

  // Clear OTP inputs
  document.querySelectorAll('.otp-digit').forEach(input => { input.value = ''; input.classList.remove('otp-error'); });
  const otpError = document.getElementById('otpError');
  if (otpError) { otpError.textContent = ''; otpError.style.display = 'none'; }
  const verifyOtpBtn = document.getElementById('verifyOtpBtn');
  if (verifyOtpBtn) {
    verifyOtpBtn.disabled = false;
    verifyOtpBtn.innerHTML = '<i class="ph ph-check-circle"></i>&nbsp; Verify Code';
    verifyOtpBtn.style.opacity = '1';
    verifyOtpBtn.style.cursor = 'pointer';
  }

  // Clear new password inputs
  const newResetPass = document.getElementById('newResetPass');
  const confirmResetPass = document.getElementById('confirmResetPass');
  if (newResetPass) newResetPass.value = '';
  if (confirmResetPass) confirmResetPass.value = '';
  const newResetPassError = document.getElementById('newResetPassError');
  const confirmResetPassError = document.getElementById('confirmResetPassError');
  if (newResetPassError) { newResetPassError.textContent = ''; newResetPassError.style.display = 'none'; }
  if (confirmResetPassError) { confirmResetPassError.textContent = ''; confirmResetPassError.style.display = 'none'; }
  const resetPasswordBtn = document.getElementById('resetPasswordBtn');
  if (resetPasswordBtn) {
    resetPasswordBtn.disabled = false;
    resetPasswordBtn.textContent = 'Reset Password';
    resetPasswordBtn.style.opacity = '1';
    resetPasswordBtn.style.cursor = 'pointer';
  }
}

// ── STEP 1: Send code ──
const forgotForm = document.getElementById('forgotForm');
if (forgotForm) {
  forgotForm.addEventListener('submit', async e => {
    e.preventDefault();
    const emailInput = document.getElementById('resetEmail');
    const emailError = document.getElementById('resetEmailError');
    const sendCodeBtn = document.getElementById('sendCodeBtn');
    const email = emailInput?.value.trim();

    emailError.textContent = '';
    emailError.style.display = 'none';

    if (!email) {
      emailError.textContent = 'Please enter your email address.';
      emailError.style.display = 'block';
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      emailError.textContent = 'Please enter a valid email address.';
      emailError.style.display = 'block';
      return;
    }

    sendCodeBtn.disabled = true;
    sendCodeBtn.innerHTML = '<i class="ph ph-circle-notch"></i>&nbsp; Sending...';
    sendCodeBtn.style.opacity = '0.7';
    sendCodeBtn.style.cursor = 'not-allowed';

    try {
      const res = await fetch('send_password_reset.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'email=' + encodeURIComponent(email)
      });
      const result = await res.json();

      if (result.success) {
        _resetEmail = email;
        // Update OTP step description
        const otpDesc = document.getElementById('otpDesc');
        if (otpDesc) otpDesc.textContent = `Enter the 6-digit code sent to ${email}`;
        switchModal(modals.forgot, modals.otp);
        // Focus first OTP digit
        setTimeout(() => { document.querySelector('.otp-digit')?.focus(); }, 200);
      } else {
        emailError.textContent = result.message || 'Failed to send code. Please try again.';
        emailError.style.display = 'block';
      }
    } catch {
      emailError.textContent = 'An error occurred. Please try again.';
      emailError.style.display = 'block';
    } finally {
      sendCodeBtn.disabled = false;
      sendCodeBtn.innerHTML = '<i class="ph ph-paper-plane-right"></i>&nbsp; Send Code';
      sendCodeBtn.style.opacity = '1';
      sendCodeBtn.style.cursor = 'pointer';
    }
  });
}

// ── OTP input keyboard navigation ──
initOtpInputs();

function initOtpInputs() {
  const digits = document.querySelectorAll('.otp-digit');
  if (!digits.length) return;

  digits.forEach((input, idx) => {
    input.addEventListener('input', e => {
      // Allow only digits
      input.value = input.value.replace(/[^0-9]/g, '').slice(-1);
      input.classList.remove('otp-error');
      if (input.value && idx < digits.length - 1) {
        digits[idx + 1].focus();
      }
    });
    input.addEventListener('keydown', e => {
      if (e.key === 'Backspace') {
        if (!input.value && idx > 0) {
          digits[idx - 1].focus();
          digits[idx - 1].value = '';
        }
        input.value = '';
        input.classList.remove('otp-error');
        e.preventDefault();
      }
      if (e.key === 'ArrowLeft' && idx > 0) digits[idx - 1].focus();
      if (e.key === 'ArrowRight' && idx < digits.length - 1) digits[idx + 1].focus();
    });
    input.addEventListener('paste', e => {
      e.preventDefault();
      const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
      pasted.split('').slice(0, 6).forEach((char, i) => {
        if (digits[i]) digits[i].value = char;
      });
      const nextEmpty = [...digits].findIndex(d => !d.value);
      (digits[nextEmpty === -1 ? 5 : nextEmpty])?.focus();
    });
  });
}

// ── STEP 2: Verify OTP ──
const verifyOtpBtn = document.getElementById('verifyOtpBtn');
if (verifyOtpBtn) {
  verifyOtpBtn.addEventListener('click', async () => {
    const digits = document.querySelectorAll('.otp-digit');
    const otpError = document.getElementById('otpError');
    const code = [...digits].map(d => d.value).join('');

    otpError.textContent = '';
    otpError.style.display = 'none';
    digits.forEach(d => d.classList.remove('otp-error'));

    if (code.length < 6) {
      otpError.textContent = 'Please enter all 6 digits.';
      otpError.style.display = 'block';
      digits.forEach(d => { if (!d.value) d.classList.add('otp-error'); });
      return;
    }

    // Verify the code against the backend (just check, don't reset yet)
    verifyOtpBtn.disabled = true;
    verifyOtpBtn.innerHTML = '<i class="ph ph-circle-notch"></i>&nbsp; Verifying...';
    verifyOtpBtn.style.opacity = '0.7';
    verifyOtpBtn.style.cursor = 'not-allowed';

    try {
      // We use a lightweight check endpoint
      const res = await fetch('verify_reset_code.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: _resetEmail, code: code, check_only: true })
      });
      const result = await res.json();

      if (result.success || result.code_valid) {
        // Store the code for the final step
        _resetEmail_code = code;
        // Move to step 3
        switchModal(modals.otp, modals.newPassword);
        setTimeout(() => { document.getElementById('newResetPass')?.focus(); }, 200);
      } else {
        otpError.textContent = result.message || 'Invalid or expired code. Please try again.';
        otpError.style.display = 'block';
        digits.forEach(d => d.classList.add('otp-error'));
      }
    } catch {
      otpError.textContent = 'An error occurred. Please try again.';
      otpError.style.display = 'block';
    } finally {
      verifyOtpBtn.disabled = false;
      verifyOtpBtn.innerHTML = '<i class="ph ph-check-circle"></i>&nbsp; Verify Code';
      verifyOtpBtn.style.opacity = '1';
      verifyOtpBtn.style.cursor = 'pointer';
    }
  });
}

// ── Resend code ──
const resendCodeBtn = document.getElementById('resendCodeBtn');
if (resendCodeBtn) {
  resendCodeBtn.addEventListener('click', async () => {
    if (!_resetEmail) return;

    resendCodeBtn.disabled = true;
    resendCodeBtn.textContent = 'Sending...';

    try {
      const res = await fetch('send_password_reset.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'email=' + encodeURIComponent(_resetEmail)
      });
      const result = await res.json();
      const otpError = document.getElementById('otpError');
      if (result.success) {
        // Clear digits and show a brief confirmation
        document.querySelectorAll('.otp-digit').forEach(d => { d.value = ''; d.classList.remove('otp-error'); });
        otpError.textContent = 'A new code has been sent.';
        otpError.style.display = 'block';
        otpError.style.color = '#059669';
        document.querySelector('.otp-digit')?.focus();
        setTimeout(() => { otpError.textContent = ''; otpError.style.display = 'none'; otpError.style.color = ''; }, 4000);
      } else {
        otpError.textContent = result.message || 'Failed to resend. Please try again.';
        otpError.style.display = 'block';
      }
    } catch {
      const otpError = document.getElementById('otpError');
      otpError.textContent = 'An error occurred. Please try again.';
      otpError.style.display = 'block';
    } finally {
      setTimeout(() => {
        resendCodeBtn.disabled = false;
        resendCodeBtn.textContent = 'Resend Code';
      }, 5000);
    }
  });
}

// ── STEP 3: Reset Password ──
const resetPasswordBtn = document.getElementById('resetPasswordBtn');
if (resetPasswordBtn) {
  resetPasswordBtn.addEventListener('click', async () => {
    const newPass     = document.getElementById('newResetPass')?.value ?? '';
    const confirmPass = document.getElementById('confirmResetPass')?.value ?? '';
    const newPassErr  = document.getElementById('newResetPassError');
    const confPassErr = document.getElementById('confirmResetPassError');

    newPassErr.textContent  = '';  newPassErr.style.display  = 'none';
    confPassErr.textContent = '';  confPassErr.style.display = 'none';

    let hasError = false;

    if (!newPass) {
      newPassErr.textContent = 'New password is required.'; newPassErr.style.display = 'block'; hasError = true;
    } else {
      const errs = [];
      if (newPass.length < 8) errs.push('at least 8 characters');
      if (!/[A-Z]/.test(newPass)) errs.push('one uppercase letter');
      if (!/[a-z]/.test(newPass)) errs.push('one lowercase letter');
      if (!/[^A-Za-z0-9]/.test(newPass)) errs.push('one special character');
      if (errs.length) { newPassErr.textContent = 'Password needs: ' + errs.join(', ') + '.'; newPassErr.style.display = 'block'; hasError = true; }
    }

    if (!confirmPass) {
      confPassErr.textContent = 'Please confirm your new password.'; confPassErr.style.display = 'block'; hasError = true;
    } else if (newPass && newPass !== confirmPass) {
      confPassErr.textContent = 'Passwords do not match.'; confPassErr.style.display = 'block'; hasError = true;
    }

    if (hasError) return;

    resetPasswordBtn.disabled = true;
    resetPasswordBtn.textContent = 'Resetting...';
    resetPasswordBtn.style.opacity = '0.7';
    resetPasswordBtn.style.cursor = 'not-allowed';

    try {
      const res = await fetch('verify_reset_code.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: _resetEmail, code: _resetEmail_code, password: newPass })
      });
      const result = await res.json();

      if (result.success) {
        // Success! Close modal and go back to login
        closeModal(modals.newPassword);
        resetForgotFlow();
        // Small delay then open login with a success indicator
        setTimeout(() => {
          openModal(modals.login);
          const passErr = document.getElementById('passwordError');
          if (passErr) {
            passErr.textContent = 'Password reset successfully! Please log in.';
            passErr.style.color = '#059669';
            passErr.style.display = 'block';
            setTimeout(() => { passErr.textContent = ''; passErr.style.color = ''; passErr.style.display = 'none'; }, 5000);
          }
        }, 300);
      } else {
        newPassErr.textContent = result.message || 'Failed to reset password. Please try again.';
        newPassErr.style.display = 'block';
      }
    } catch {
      newPassErr.textContent = 'An error occurred. Please try again.';
      newPassErr.style.display = 'block';
    } finally {
      resetPasswordBtn.disabled = false;
      resetPasswordBtn.textContent = 'Reset Password';
      resetPasswordBtn.style.opacity = '1';
      resetPasswordBtn.style.cursor = 'pointer';
    }
  });
}

// ===================== Password toggle =====================
function setupPasswordToggle(inputId, toggleId) {
  const input = document.getElementById(inputId);
  const toggle = document.getElementById(toggleId);

  if (input && toggle) {
    toggle.addEventListener('click', () => {
      const icon = toggle.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('ph-eye-slash');
        icon.classList.add('ph-eye');
      } else {
        input.type = 'password';
        icon.classList.remove('ph-eye');
        icon.classList.add('ph-eye-slash');
      }
    });
  }
}
setupPasswordToggle('password','togglePassword');

function toggleResetPass(inputId, toggleEl) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const icon = toggleEl.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('ph-eye-slash');
    icon.classList.add('ph-eye');
  } else {
    input.type = 'password';
    icon.classList.remove('ph-eye');
    icon.classList.add('ph-eye-slash');
  }
}

// ===================== Close hamburger when clicking outside =====================
document.addEventListener("click", e => {
  if (!navMenu || !hamburger) return;
  if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
    navMenu.classList.remove("show");
    const icon = hamburger.querySelector(".material-icons");
    if (icon) icon.textContent = "menu";
  }
});

// ===================== Navbar hash links -> page nav =====================
document.querySelectorAll('nav ul li a[data-page]').forEach(a => {
  a.addEventListener('click', e => {
    e.preventDefault();
    if (window._goToPage) window._goToPage(+a.dataset.page);
    navMenu.classList.remove('show');
  });
});
