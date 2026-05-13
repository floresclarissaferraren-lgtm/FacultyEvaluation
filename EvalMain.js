const modals = {
  login: document.getElementById("loginModal"),
  forgot: document.getElementById("forgotModal")
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

// ===================== Close Buttons =====================
const closeLogin = document.getElementById("closeLogin");
if (closeLogin) closeLogin.addEventListener("click", () => closeModal(modals.login));

const closeForgot = document.getElementById("closeForgot");
if (closeForgot) closeForgot.addEventListener("click", () => switchModal(modals.forgot, modals.login));

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
          const elapsedTime = Date.now() - startTime;
          if (elapsedTime < minDisplayTime) {
            setTimeout(() => {
              window.location.href = 'FacultyInstructor.php';
            }, minDisplayTime - elapsedTime);
          } else {
            window.location.href = 'FacultyInstructor.php';
          }
        } else if (data.role === 'admin') {
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
        }
      } else {
        document.getElementById('passwordError').textContent = data.message || 'Login failed';
        fullscreenSpinner.classList.add('hidden');
      }
    } catch (err) {
      console.error('Login error:', err);
      document.getElementById('passwordError').textContent = 'An error occurred. Please try again.';
      fullscreenSpinner.classList.add('hidden');
    }
  });
}

// ===================== Forgot form submit handler =====================
const forgotForm = document.getElementById('forgotForm');
const codeVerificationSection = document.getElementById('codeVerificationSection');
const verifyCodeBtn = document.getElementById('verifyCodeBtn');

if (forgotForm) {
  forgotForm.addEventListener('submit', async e => {
    e.preventDefault();
    const emailInput = forgotForm.querySelector('#resetEmail');
    const email = emailInput?.value.trim();

    if (!email) {
      alert('Please enter your email.');
      return;
    }

    try {
      const response = await fetch('send_password_reset.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'email=' + encodeURIComponent(email)
      });
      
      const result = await response.json();
      
      if (result.success) {
        alert('Reset code sent to ' + email);
        // Show code verification section
        codeVerificationSection.style.display = 'block';
        // Hide the send button
        e.target.style.display = 'none';
      } else {
        alert(result.message || 'Failed to send reset code');
      }
    } catch (error) {
      alert('An error occurred. Please try again.');
    }
  });
}

// Verification code handler
if (verifyCodeBtn) {
  verifyCodeBtn.addEventListener('click', async () => {
    const email = document.getElementById('resetEmail').value;
    const code = document.getElementById('resetCode').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmNewPassword').value;

    if (!email || !code || !newPassword || !confirmPassword) {
      alert('Please fill all fields');
      return;
    }

    if (code.length !== 6) {
      alert('Please enter a valid 6-digit code');
      return;
    }

    if (newPassword !== confirmPassword) {
      alert('Passwords do not match');
      return;
    }

    if (newPassword.length < 6) {
      alert('Password must be at least 6 characters');
      return;
    }

    try {
      const response = await fetch('verify_reset_code.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email: email,
          code: code,
          password: newPassword
        })
      });
      
      const result = await response.json();
      
      if (result.success) {
        alert('Password reset successful! You can now login with your new password.');
        switchModal(modals.forgot, modals.login);
        // Clear form
        document.getElementById('resetCode').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmNewPassword').value = '';
        codeVerificationSection.style.display = 'none';
        // Show the send button again
        forgotForm.querySelector('button[type="submit"]').style.display = 'block';
      } else {
        alert(result.message || 'Failed to reset password');
      }
    } catch (error) {
      alert('An error occurred. Please try again.');
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

// ===================== Close hamburger when clicking outside =====================
document.addEventListener("click", e => {
  if (!navMenu || !hamburger) return;
  if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
    navMenu.classList.remove("show");
    const icon = hamburger.querySelector(".material-icons");
    if (icon) icon.textContent = "menu";
  }
});
