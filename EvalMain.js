const modals = {
  student: {
    login: document.getElementById("studentLoginModal"),
    forgot: document.getElementById("studentForgotModal"),
    profileBtn: document.querySelector(".dropdown-menu a:nth-child(3)"),
    openForgot: document.getElementById("openStudentForgot"),
    closeLogin: document.getElementById("closeStudentLogin"),
    closeForgot: document.getElementById("closeStudentForgot")
  },
  admin: {
    login: document.getElementById("adminLoginModal"),
    profileBtn: document.querySelector(".dropdown-menu a:nth-child(1)"),
    closeLogin: document.getElementById("closeAdminLogin")
  },
  instructor: {
    login: document.getElementById("instructorLoginModal"),
    forgot: document.getElementById("instructorForgotModal"),
    profileBtn: document.querySelector(".dropdown-menu a:nth-child(2)"),
    openForgot: document.getElementById("openInstructorForgot"),
    closeLogin: document.getElementById("closeInstructorLogin"),
    closeForgot: document.getElementById("closeInstructorForgot")
  }
};
const hamburger = document.getElementById("hamburger");
const navMenu = document.getElementById("navMenu");

// ===================== FUNCTIONS =====================
function openModal(modal) {
  if (modal) {
    modal.classList.add("show");
    document.body.classList.add("modal-open");
  }
}
function closeModal(modal) {
  if (modal) {
    modal.classList.remove("show");
    document.body.classList.remove("modal-open");
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
// ===================== Profile Buttons =====================
Object.values(modals).forEach(user => {
  if (user.profileBtn) user.profileBtn.addEventListener("click", e => { e.preventDefault(); openModal(user.login); });
});

// ===================== Close Buttons =====================
Object.values(modals).forEach(user => {
  if (user.closeLogin) user.closeLogin.addEventListener("click", () => closeModal(user.login));
  if (user.closeForgot) user.closeForgot.addEventListener("click", () => switchModal(user.forgot, user.login));
});

// ===================== Forgot Buttons =====================
if (modals.student.openForgot) modals.student.openForgot.addEventListener("click", e => {
   e.preventDefault(); switchModal(modals.student.login, modals.student.forgot); });
if (modals.instructor.openForgot) modals.instructor.openForgot.addEventListener("click", e => { 
  e.preventDefault(); switchModal(modals.instructor.login, modals.instructor.forgot); });

if (hamburger) hamburger.addEventListener("click", toggleHamburgerMenu);

// ===================== Login form submit handlers =====================
const logins = {
  student: { id: 'STU001', password: 'student123', redirect: 'FacultyUser.html' }
};

const studentForm = modals.student.login?.querySelector('form');
if (studentForm) {
  studentForm.addEventListener('submit', e => {
    e.preventDefault();
    const id = studentForm.querySelector('input[type=text]')?.value.trim();
    const pw = studentForm.querySelector('input[type=password]')?.value.trim();
    if (!id || !pw) return alert('Please enter Student ID and Password.');
    if (id === logins.student.id && pw === logins.student.password) {
      window.location.href = logins.student.redirect;
    } else {
      alert('Incorrect student credentials. Use ID: STU001, Password: student123');
    }
  });
}

const adminForm = document.getElementById('adminLoginForm');
if (adminForm) adminForm.addEventListener('submit', async e => {
  e.preventDefault();
  try {
    const res = await fetch('admin_login.php', { method: 'POST', body: new FormData(adminForm) });
    const text = (await res.text()).trim();
    text === 'success' ? window.location.href = 'FacultyAdmin.php' : alert(text);
  } catch (err) {
    console.error('Login error:', err);
    alert('An error occurred. Please try again.');
  }
});


const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('adminPassword');

if (togglePassword && passwordInput) {
    togglePassword.addEventListener('click', () => {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        // swap icon
        togglePassword.classList.toggle('fa-eye');
        togglePassword.classList.toggle('fa-eye-slash');
    });
}


document.addEventListener("click", e => {
  if (!navMenu || !hamburger) return;
  if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
    navMenu.classList.remove("show");
    const icon = hamburger.querySelector(".material-icons");
    if (icon) icon.textContent = "menu";
  }
});