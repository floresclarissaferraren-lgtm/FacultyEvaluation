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

// ===================== Profile Buttons =====================
Object.values(modals).forEach(user => {
  if (user.profileBtn) user.profileBtn.addEventListener("click", e => {
    e.preventDefault();
    openModal(user.login);
  });
});

// ===================== Close Buttons =====================
Object.values(modals).forEach(user => {
  if (user.closeLogin) user.closeLogin.addEventListener("click", () => closeModal(user.login));
  if (user.closeForgot) user.closeForgot.addEventListener("click", () => switchModal(user.forgot, user.login));
});

// ===================== Forgot Buttons =====================
if (modals.student.openForgot) modals.student.openForgot.addEventListener("click", e => {
   e.preventDefault(); switchModal(modals.student.login, modals.student.forgot); 
});
if (modals.instructor.openForgot) modals.instructor.openForgot.addEventListener("click", e => { 
  e.preventDefault(); switchModal(modals.instructor.login, modals.instructor.forgot); 
});
if (hamburger) hamburger.addEventListener("click", toggleHamburgerMenu);

// ===================== Login form submit handlers =====================
const studentForm = modals.student.login?.querySelector('form');
if (studentForm) {
  studentForm.addEventListener('submit', async e => {
    e.preventDefault();
    const idInput = studentForm.querySelector('#studentNumber');
    const pwInput = studentForm.querySelector('#studentPassword');
    const id = idInput?.value.trim();
    const pw = pwInput?.value.trim();

    // clear previous errors
    document.getElementById('studentNumberError').textContent = '';
    document.getElementById('studentPasswordError').textContent = '';

    let hasError = false;

    if (!id) {
      document.getElementById('studentNumberError').textContent = 'Please enter Student ID.';
      hasError = true;
    } else if (!id.startsWith("GC-")) {
      document.getElementById('studentNumberError').textContent = 'Student ID must start with "GC-".';
      hasError = true;
    }

    if (!pw) {
      document.getElementById('studentPasswordError').textContent = 'Please enter Password.';
      hasError = true;
    }

    if (hasError) return;

    try {
      const formData = new FormData();
      formData.append('student_number', id);
      formData.append('password', pw);

      const res = await fetch('student_account.php', { method: 'POST', body: formData });
      const text = (await res.text()).trim();

      if (text === 'success') {
        window.location.href = 'FacultyUser.php';
      } else {
        document.getElementById('studentPasswordError').textContent = text;
      }
    } catch (err) {
      console.error('Login error:', err);
      document.getElementById('studentPasswordError').textContent = 'An error occurred. Please try again.';
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

// ===================== Password toggle =====================
function setupPasswordToggle(inputId, toggleId) {
  const input = document.getElementById(inputId);
  const toggle = document.getElementById(toggleId);

  if (input && toggle) {
    toggle.addEventListener('click', () => {
      if (input.type === 'password') {
        input.type = 'text';
        toggle.classList.remove('fa-eye');
        toggle.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        toggle.classList.remove('fa-eye-slash');
        toggle.classList.add('fa-eye');
      }
    });
  }
}
setupPasswordToggle('adminPassword','togglePassword');
setupPasswordToggle('studentPassword','toggleStudentPassword');
setupPasswordToggle('instructorPassword','toggleInstructorPassword');

// ===================== Close hamburger when clicking outside =====================
document.addEventListener("click", e => {
  if (!navMenu || !hamburger) return;
  if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
    navMenu.classList.remove("show");
    const icon = hamburger.querySelector(".material-icons");
    if (icon) icon.textContent = "menu";
  }
});