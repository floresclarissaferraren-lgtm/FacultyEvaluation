

//walang password open ================= DROPDOWN =================
function toggleDropdown() {
  const menu = document.getElementById("dropdownMenu");
  menu.style.display = (menu.style.display === "block") ? "none" : "block";
}
window.showLogoutModal = () => {
  document.getElementById("logoutModal").style.display = "flex";
  document.getElementById("dropdownMenu").style.display = "none";
};
window.closeLogoutModal = () => {
  document.getElementById("logoutModal").style.display = "none";
};

window.confirmLogout = () => location.href = "EvalMain.php?logout=true";
window.logout = e => { 
  e.preventDefault(); 
  showLogoutModal(); 
};

document.addEventListener("click", e => {
  const m = document.getElementById("dropdownMenu"),
        t = document.querySelector(".student-box");
  if (m.style.display === "block" && !t.contains(e.target) && !m.contains(e.target)) {
    m.style.display = "none";
  }
});
document.getElementById("logoutModal").addEventListener("click", e => {
  if (e.target === document.getElementById("logoutModal")) closeLogoutModal();
});

function showPasswordForm(e){
  if (e) e.preventDefault();
  document.getElementById("dropdownMenu").style.display = "none";
  
  const passwordForm = document.getElementById("passwordForm");
  const formContent = passwordForm.querySelector(".LoginForm-content");
  
  // Reset any previous animations/transforms
  if (formContent) {
    formContent.style.animation = "none";
    formContent.style.transform = "none";
    // Force reflow to restart animation
    void formContent.offsetHeight;
  }
  
  document.body.classList.add("modal-open");
  passwordForm.classList.add("show");
  
  // Reset button state when opening form
  const submitBtn = document.querySelector('#passwordChangeForm .modern-login-btn');
  if (submitBtn) {
    submitBtn.disabled = false;
    submitBtn.textContent = "Update Password";
    submitBtn.style.opacity = "1";
    submitBtn.style.cursor = "pointer";
  }
  
  // Clear input fields
  document.getElementById("oldPass").value = "";
  document.getElementById("newPass").value = "";
  document.getElementById("confirmPass").value = "";
  if (typeof clearPassMsgs === "function") clearPassMsgs();
}

function closePasswordForm(){
  const passwordForm = document.getElementById("passwordForm");
  const formContent = passwordForm.querySelector(".LoginForm-content");
  
  document.body.classList.remove("modal-open");
  passwordForm.classList.remove("show");
  
  // Reset animations after closing
  setTimeout(() => {
    if (formContent) {
      formContent.style.animation = "";
      formContent.style.transform = "";
    }
  }, 300);
  
  if (typeof clearPassMsgs === "function") clearPassMsgs();
  const box = document.getElementById("passGlobalMsg");
  if (box) box.style.display = "none";
  
  // Reset button state when closing form
  const submitBtn = document.querySelector('#passwordChangeForm .modern-login-btn');
  if (submitBtn) {
    submitBtn.disabled = false;
    submitBtn.textContent = "Update Password";
    submitBtn.style.opacity = "1";
    submitBtn.style.cursor = "pointer";
  }
}
function showProfile(e){
  if (e) e.preventDefault();
  // Close dropdown first
  document.getElementById("dropdownMenu").style.display = "none";
  
  // Get student information from hidden fields
  const studentId = document.getElementById("studentId")?.value;
  const studentName = document.getElementById("studentName")?.value;
  const studentNumber = document.getElementById("studentNumber")?.value;
  const studentYearLevel = document.getElementById("studentYearLevel")?.value;
  const studentSection = document.getElementById("studentSection")?.value;
  const studentProgram = document.getElementById("studentProgram")?.value;
  
  // Fetch additional student data from database
  fetchStudentProfileData(studentId).then(additionalData => {
    // Create profile modal content with real data
    const profileContent = `
      <div class="sp-modal">
        <div class="sp-header">
          <h3 class="sp-title">Student Profile</h3>
          <button class="sp-close-btn" onclick="closeProfileModal()" aria-label="Close">
            <i class="ph ph-x"></i>
          </button>
        </div>

        <div class="sp-body">
          <div class="sp-name-row">
            <div class="sp-initial">${(studentName || 'S').charAt(0).toUpperCase()}</div>
            <div>
              <div class="sp-name">${studentName || 'Student'}</div>
              <div class="sp-role-badge">Student</div>
            </div>
          </div>

          <div class="sp-divider"></div>

          <div class="sp-info-list">
            <div class="sp-info-row">
              <div class="sp-info-content">
                <span class="sp-info-label">Student ID</span>
                <span class="sp-info-value">${studentNumber || 'N/A'}</span>
              </div>
            </div>
            <div class="sp-info-row">
              <div class="sp-info-content">
                <span class="sp-info-label">Program</span>
                <span class="sp-info-value">${studentProgram || 'N/A'}</span>
              </div>
            </div>
            <div class="sp-info-row">
              <div class="sp-info-content">
                <span class="sp-info-label">Year Level</span>
                <span class="sp-info-value">${studentYearLevel ? studentYearLevel + (studentYearLevel == 1 ? 'st' : studentYearLevel == 2 ? 'nd' : studentYearLevel == 3 ? 'rd' : 'th') + ' Year' : 'N/A'}</span>
              </div>
            </div>
            <div class="sp-info-row">
              <div class="sp-info-content">
                <span class="sp-info-label">Section</span>
                <span class="sp-info-value">${studentSection || 'N/A'}</span>
              </div>
            </div>
          </div>

          <button class="sp-close-main-btn" onclick="closeProfileModal()">Close</button>
        </div>
      </div>
    `;
    
    // Create modal if it doesn't exist
    let profileModal = document.getElementById("profileModal");
    if (!profileModal) {
      profileModal = document.createElement("div");
      profileModal.id = "profileModal";
      profileModal.className = "profileform";
      document.body.appendChild(profileModal);
    }
    
    profileModal.innerHTML = profileContent;
    profileModal.style.display = "flex";

    // Attach click-outside listener only once
    if (!profileModal._clickListenerAttached) {
      profileModal.addEventListener('click', function(event) {
        if (event.target === profileModal) {
          closeProfileModal();
        }
      });
      profileModal._clickListenerAttached = true;
    }
  }).catch(error => {
    console.error('Error fetching student profile data:', error);
  });
}

// Function to fetch additional student profile data from database
async function fetchStudentProfileData(studentId) {
  try {
    const response = await fetch('get_student_profile.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `student_id=${studentId}`
    });
    
    if (!response.ok) {
      throw new Error('Failed to fetch student profile data');
    }
    
    const data = await response.json();
    return data.success ? data.data : {};
  } catch (error) {
    console.error('Error:', error);
    return {};
  }
}

window.closeProfileModal = () => {
  document.getElementById("profileModal").style.display = "none";
};
function showPassMsg(fieldId, message) {
  const el = document.getElementById(fieldId);
  if (el) { el.textContent = message; el.style.display = message ? "block" : "none"; }
}

function clearPassMsgs() {
  ["oldPassError", "newPassError", "confirmPassError"].forEach(id => showPassMsg(id, ""));
}

function validatePasswordStrength(password) {
  const errors = [];
  if (password.length < 8)               errors.push("at least 8 characters");
  if (!/[A-Z]/.test(password))           errors.push("at least one uppercase letter");
  if (!/[a-z]/.test(password))           errors.push("at least one lowercase letter");
  if (!/[^A-Za-z0-9]/.test(password))   errors.push("at least one special character");
  return errors;
}

function showPasswordMessageBox(message, type) {
  let box = document.getElementById("passGlobalMsg");
  if (!box) {
    box = document.createElement("div");
    box.id = "passGlobalMsg";
    box.className = "password-message-box";
  }
  const container = document.querySelector("#passwordForm .login-form-container");
  if (container && box.parentElement !== container) {
    container.insertBefore(box, container.firstChild);
  }
  box.style.display = "flex";
  
  // Create icon based on type
  const icon = type === "success" 
    ? '<i class="ph ph-check-circle"></i>' 
    : '<i class="ph ph-warning-circle"></i>';
  
  box.innerHTML = `
    <div class="message-icon">${icon}</div>
    <div class="message-text">${message}</div>
  `;
  
  // Remove existing type classes
  box.classList.remove("success", "error", "show");
  
  // Add type class and trigger animation
  setTimeout(() => {
    box.classList.add(type, "show");
  }, 10);
  
  // Auto hide after 4 seconds
  setTimeout(() => {
    box.classList.remove("show");
  }, 4000);
}

function updatePassword() {
  const studentId   = document.getElementById("studentId").value.trim();
  const oldPass     = document.getElementById("oldPass").value.trim();
  const newPass     = document.getElementById("newPass").value.trim();
  const confirmPass = document.getElementById("confirmPass").value.trim();

  clearPassMsgs();

  let hasError = false;

  if (!oldPass) {
    showPassMsg("oldPassError", "Current password is required.");
    hasError = true;
  }

  if (!newPass) {
    showPassMsg("newPassError", "New password is required.");
    hasError = true;
  } else {
    const strengthErrors = validatePasswordStrength(newPass);
    if (strengthErrors.length > 0) {
      showPassMsg("newPassError", "Password must have: " + strengthErrors.join(", ") + ".");
      hasError = true;
    }
  }

  if (!confirmPass) {
    showPassMsg("confirmPassError", "Please confirm your new password.");
    hasError = true;
  } else if (newPass && newPass !== confirmPass) {
    showPassMsg("confirmPassError", "Passwords do not match.");
    hasError = true;
  }

  if (hasError) return;

  // Get button and disable it during submission
  const submitBtn = document.querySelector('#passwordChangeForm .modern-login-btn');
  const originalText = submitBtn.textContent;
  
  if (submitBtn.disabled) return; // Prevent double submission
  
  submitBtn.disabled = true;
  submitBtn.textContent = "Updating...";
  submitBtn.style.opacity = "0.6";
  submitBtn.style.cursor = "not-allowed";

  fetch("update_studentPassForm.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      student_id: studentId,
      old_password: oldPass,
      new_password: newPass,
    }),
  })
    .then((res) => {
      if (!res.ok) throw new Error("Network response was not ok");
      return res.json();
    })
    .then((data) => {
      // Re-enable button
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
      submitBtn.style.opacity = "1";
      submitBtn.style.cursor = "pointer";
      
      if (data.success) {
        showPasswordMessageBox("Password updated successfully!", "success");
        setTimeout(() => {
          closePasswordForm();
          document.getElementById("oldPass").value = "";
          document.getElementById("newPass").value = "";
          document.getElementById("confirmPass").value = "";
          clearPassMsgs();
        }, 1500);
      } else {
        if ((data.message || "").toLowerCase().includes("old") ||
            (data.message || "").toLowerCase().includes("current") ||
            (data.message || "").toLowerCase().includes("incorrect")) {
          showPassMsg("oldPassError", data.message || "Current password is incorrect.");
        } else {
          showPasswordMessageBox(data.message || "Failed to update password.", "error");
        }
      }
    })
    .catch((err) => {
      // Re-enable button on error
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
      submitBtn.style.opacity = "1";
      submitBtn.style.cursor = "pointer";
      
      console.error("Error updating password:", err);
      showPasswordMessageBox("Something went wrong. Please try again.", "error");
    });
}

// Close modal by clicking overlay
document.getElementById("passwordForm").addEventListener("click", (e) => {
  if (e.target.id === "passwordForm") closePasswordForm();
});

function togglePassword(id, icon) {
  const input = document.getElementById(id);
  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("ph-eye-slash");
    icon.classList.add("ph-eye");
  } else {
    input.type = "password";
    icon.classList.remove("ph-eye");
    icon.classList.add("ph-eye-slash");
  }
}

// ================= switch section =================

function updateStudentAcademicPeriodDisplay(status) {
  const periodEl = document.getElementById("studentAcademicPeriod");
  if (!periodEl) return;

  const academicYear = (status?.current_academic_year || status?.active_academic_year || "").trim();
  const semester = (status?.current_semester || status?.active_semester || "").trim();

  if (academicYear && semester) {
    periodEl.textContent = `Academic Year: ${academicYear} - ${semester}`;
    return;
  }

  periodEl.textContent = status?.active_period_name
    ? `Academic Period: ${status.active_period_name}`
    : "Academic Year: No active period";
}

async function loadStudentAcademicPeriod() {
  try {
    const statusRes = await fetch("periods_api.php?action=status", { cache: "no-store", credentials: "same-origin" });
    const status = await statusRes.json();
    if (status && status.success) updateStudentAcademicPeriodDisplay(status);
  } catch (_) {
    updateStudentAcademicPeriodDisplay(null);
  }
}

async function showEvaluateSection() {
  if ((document.getElementById("studentStatus")?.value || "active").toLowerCase() !== "active") {
    alert("Your account has been set to inactive by an admin. You cannot evaluate until your account is active again.");
    return;
  }

  try {
    const statusRes = await fetch("periods_api.php?action=status", { cache: "no-store", credentials: "same-origin" });
    const status = await statusRes.json();
    updateStudentAcademicPeriodDisplay(status);
    if (!status || !status.success || !status.evaluation_open) {
      alert("Evaluation is closed");
      return;
    }
  } catch (_) {
    // If status check fails, be safe and block entry.
    alert("Evaluation is closed");
    return;
  }

  document.getElementById("mainPage").style.display = "none";
  document.getElementById("evaluateSection").style.display = "block";
  document.getElementById("facultyCards").style.display = "block";
  document.getElementById("evaluationContainer").style.display = "none";

  await loadStudentFacultyCards();
}
function goBackToMain() {
  document.getElementById("evaluateSection").style.display = "none";
  document.getElementById("evaluationContainer").style.display = "none";
  document.getElementById("facultyCards").style.display = "none";
  document.getElementById("mainPage").style.display = "flex";
}

async function refreshStudentPeriodAccess() {
  const evaluateSection = document.getElementById("evaluateSection");
  const isEvaluating = evaluateSection && evaluateSection.style.display !== "none";
  if (!isEvaluating) return;

  try {
    const statusRes = await fetch("periods_api.php?action=status", { cache: "no-store", credentials: "same-origin" });
    const status = await statusRes.json();
    updateStudentAcademicPeriodDisplay(status);
    if (!status || !status.success || !status.evaluation_open) {
      goBackToMain();
      alert("Evaluation period has ended or is closed.");
    }
  } catch (_) {
    goBackToMain();
    alert("Evaluation is closed");
  }
}

setInterval(refreshStudentPeriodAccess, 60000);
// ================= LOAD CATEGORIES + QUESTIONS =================
let currentCriteria = 0;
let criteriaTables = [];

async function loadStudentFacultyCards() {
  const studentId = document.getElementById('studentId')?.value.trim();
  const yearLevel = document.getElementById('studentYearLevel')?.value.trim();
  const container = document.getElementById('facultyContainer');

  if (!container) return;

  // Block evaluate when evaluation is closed
  const studentActive = (document.getElementById("studentStatus")?.value || "active").toLowerCase() === "active";
  let evaluationOpen = false;
  try {
    const statusRes = await fetch("periods_api.php?action=status", { cache: "no-store", credentials: "same-origin" });
    const status = await statusRes.json();
    evaluationOpen = studentActive && !!(status && status.success && status.evaluation_open);
  } catch (_) {
    evaluationOpen = false;
  }

  if (!studentId && !yearLevel) {
    container.innerHTML = '<div class="no-faculty"><i class="ph ph-chalkboard-teacher"></i><span>No Faculty Available</span></div>';
    return;
  }

  const params = new URLSearchParams();
  if (studentId) params.set('student_id', studentId);
  else if (yearLevel) params.set('year_level', yearLevel);

  try {
    const res = await fetch(`getFaculty.php?${params.toString()}`);
    const facultyList = await res.json();

    container.innerHTML = '';

    if (Array.isArray(facultyList) && facultyList.length > 0) {
      facultyList.forEach(faculty => {
        const subjectLabels = Array.isArray(faculty.subjects)
          ? faculty.subjects.map(sub => sub.subject_code || sub.subject_desc).join(', ')
          : '';

        const nameParts = [
          faculty.firstname || '',
          faculty.lastname || '',
          faculty.suffix ? faculty.suffix : ''
        ].filter(Boolean);

        const fullName = nameParts.join(' ');

        const card = document.createElement('div');
        card.className = 'faculty-card';
        card.onclick = () => {
          if (!studentActive) return alert("Your account has been set to inactive by an admin. You cannot evaluate until your account is active again.");
          if (!evaluationOpen) return alert("Evaluation is closed");
          selectFaculty(faculty.id, fullName, subjectLabels);
        };
         
        card.innerHTML = `
          <div class="faculty-header">
            <div class="faculty-icon">
              <i class="ph ph-user-circle"></i>
            </div>
            <div class="faculty-name-bg">
              <h3 class="faculty-name">${fullName}</h3>
            </div>
          </div>
          <div class="faculty-info">
            <p class="faculty-subjects-label">Subjects:</p>
            <div class="faculty-subjects">${subjectLabels || 'No subjects assigned'}</div>
          </div>
          <div class="evaluate-action">
            <button class="evaluate-faculty-btn" ${evaluationOpen ? "" : "disabled"}>Evaluate</button>
          </div>
        `;
        
        container.appendChild(card);
      });
    } else {
      container.innerHTML = '<div class="no-faculty"><i class="ph ph-chalkboard-teacher"></i><span>No Faculty Available</span></div>';
    }
  } catch (err) {
    console.error('Error loading faculty cards:', err);
    container.innerHTML = '<div class="no-faculty"><i class="ph ph-warning-circle"></i><span>Unable to Load Faculty</span></div>';
  }
}

function selectFaculty(facultyId, facultyName, subjects) {
  // Store selected faculty data
  window.selectedFaculty = {
    id: facultyId,
    name: facultyName,
    subjects: subjects
  };
  
  // Hide faculty cards and show evaluation form
  document.getElementById('facultyCards').style.display = 'none';
  document.getElementById('evaluationContainer').style.display = 'block';
  
  // Load evaluation categories and questions
  loadFacultyCategories();
}

async function loadFacultyCategories() {
  try {
    const res = await fetch("get_category.php");
    const categories = await res.json();
    console.log("Categories:", categories);

    const container = document.getElementById("evaluationContainer");
    container.innerHTML = "";

    // Move evaluationContainer to content-container for equal heights
    const contentContainer = document.querySelector(".content-container");
    if (contentContainer && !contentContainer.contains(container)) {
      contentContainer.appendChild(container);
    }

    // Create main evaluation container with header
    const mainEvaluationContainer = document.createElement("div");
    mainEvaluationContainer.className = "main-evaluation-container";
    
    // Create header section
    const headerSection = document.createElement("div");
    headerSection.className = "evaluation-header-section";
    headerSection.innerHTML = `
      <h2 class="performance-evaluation-title">Performance Evaluation</h2>
      <div class="rating-legends-mini">
        <ul>
          <li><span class="dot dot5"></span>5 - Outstanding</li>
          <li><span class="dot dot4"></span>4 - Very Good</li>
          <li><span class="dot dot3"></span>3 - Good</li>
          <li><span class="dot dot2"></span>2 - Fair</li>
          <li><span class="dot dot1"></span>1 - Poor</li>
        </ul>
      </div>
    `;
    
    // Create form content area
    const formContentArea = document.createElement("div");
    formContentArea.className = "evaluation-form-area";

    criteriaTables = [];
    currentCriteria = 0;
    
    for (let i = 0; i < categories.length; i++) {
      const c = categories[i];
      const table = document.createElement("table");
      table.className = "evaluationform";
      if (i === 0) table.classList.add("active");
      
      table.innerHTML = `
        <thead>
          <tr>
            <th><i class="ph ph-graduation-cap"></i> ${c.category_name.toUpperCase()}</th>
            <th>5</th><th>4</th><th>3</th><th>2</th><th>1</th>
          </tr>
        </thead>
        <tbody></tbody>
      `;
      
      const tbody = table.querySelector("tbody");
      const qRes = await fetch(`get_question.php?category_id=${c.id}`);
      const questions = await qRes.json();
      console.log("Questions for", c.id, questions);

      if (Array.isArray(questions) && questions.length > 0) {
        questions.forEach((q, index) => {
          const tr = document.createElement("tr");
          tr.className = index % 2 === 0 ? "row-even" : "row-odd";
          tr.innerHTML = `
            <td class="question-list">${q.question_text}</td>
            <td><input type="radio" name="q_${q.id}" value="5"></td>
            <td><input type="radio" name="q_${q.id}" value="4"></td>
            <td><input type="radio" name="q_${q.id}" value="3"></td>
            <td><input type="radio" name="q_${q.id}" value="2"></td>
            <td><input type="radio" name="q_${q.id}" value="1"></td>
          `;
          tbody.appendChild(tr);
        });
      } else {
        const tr = document.createElement("tr");
        tr.innerHTML = `<td colspan="6">No questions available</td>`;
        tbody.appendChild(tr);
      }

      formContentArea.appendChild(table);
      criteriaTables.push(table);
    }
    
    // Add feedback container (hidden initially)
    const feedbackSubmitContainer = document.createElement("div");
    feedbackSubmitContainer.id = "feedbackSubmitContainer";
    feedbackSubmitContainer.style.display = "none";
    feedbackSubmitContainer.innerHTML = `
      <div class="feedback-container">
        <div class="feedback-box">
          <label for="studentFeedback">OPTIONAL COMMENTS</label>
          <textarea id="studentFeedback" placeholder="Type your feedback here..."></textarea>
          <div class="feedback-badwords-msg" id="feedbackBadwordsMsg" aria-live="polite">
            Bad words is not allowed
          </div>
        </div>
      </div>
    `;
    
    formContentArea.appendChild(feedbackSubmitContainer);

    // Bad words filtering for feedback
    const feedbackTextarea = document.getElementById("studentFeedback");
    const badwordsMsg = document.getElementById("feedbackBadwordsMsg");
    const badWordsList = [
      "putangina",
      "puta",
      "tangina",
      "tang ina",
      "gago",
      "tanga",
      "bobo",
      "ulol",
      "tarantado",
      "inutil",
      "leche",
      "bwiset",
      "bwisit",
      "punyeta",
      "fuck you",
      "fuck",
      "shit",
      "bitch",
      "asshole",
      "dick",
      "cunt",
      "faggot",
      "nigger"
    ];

    function normalizeFeedbackText(text) {
      return (" " + String(text || "")
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, " ")
        .replace(/\s+/g, " ")
        .trim() + " ");
    }

    function feedbackHasBadWords(text) {
      const normalized = normalizeFeedbackText(text);
      return badWordsList.some(w => normalized.includes(` ${w} `));
    }

    function setFeedbackBadwordsState(hasBadWords) {
      const feedbackBox = document.querySelector(".feedback-box");
      if (feedbackBox) feedbackBox.classList.toggle("has-badwords", hasBadWords);
      if (badwordsMsg) badwordsMsg.style.display = hasBadWords ? "block" : "none";
    }

    if (feedbackTextarea) {
      setFeedbackBadwordsState(false);
      feedbackTextarea.addEventListener("input", () => {
        setFeedbackBadwordsState(feedbackHasBadWords(feedbackTextarea.value));
      });
    }
    
    // Create navigation section
    const navigationSection = document.createElement("div");
    navigationSection.className = "evaluation-navigation";
    navigationSection.innerHTML = `
      <div class="pagination-buttons">
        <button class="pagination-btn arrow-left" onclick="previousCriteria()" id="prevBtn">
          Previous
        </button>
        <button class="pagination-btn arrow-right" onclick="nextCriteria()" id="nextBtn">
          Next
        </button>
      </div>
      <div class="pagination-info" id="pageInfo">1 / ${categories.length}</div>
    `;
    
    // Assemble the main container
    mainEvaluationContainer.appendChild(headerSection);
    mainEvaluationContainer.appendChild(formContentArea);
    mainEvaluationContainer.appendChild(navigationSection);
    
    container.appendChild(mainEvaluationContainer);
    
    updatePaginationButtons();
    
  } catch (err) {
    console.error("Error loading faculty categories:", err);
  }
}

function previousCriteria() {
  if (currentCriteria > 0) {
    criteriaTables[currentCriteria].classList.remove("active");
    currentCriteria--;
    criteriaTables[currentCriteria].classList.add("active");
    updatePaginationButtons();
  }
}

function nextCriteria() {
  if (currentCriteria < criteriaTables.length - 1) {
    criteriaTables[currentCriteria].classList.remove("active");
    currentCriteria++;
    criteriaTables[currentCriteria].classList.add("active");
    updatePaginationButtons();
  }
}

function updatePaginationButtons() {
  const pageInfo = document.getElementById("pageInfo");
  const prevBtn = document.getElementById("prevBtn");
  const nextBtn = document.getElementById("nextBtn");
  const feedbackSubmitContainer = document.getElementById("feedbackSubmitContainer");
  
  if (pageInfo) {
    pageInfo.textContent = `${currentCriteria + 1} / ${criteriaTables.length}`;
  }
  
  if (prevBtn) {
    prevBtn.disabled = currentCriteria === 0;
  }
  
  if (nextBtn) {
    const isLastPage = currentCriteria === criteriaTables.length - 1;
    nextBtn.disabled = false;
    nextBtn.textContent = isLastPage ? "Submit" : "Next";
    nextBtn.onclick = isLastPage ? submitEvaluation : nextCriteria;
  }
  
  // Show feedback and submit button only on last page
  if (feedbackSubmitContainer) {
    feedbackSubmitContainer.style.display = currentCriteria === criteriaTables.length - 1 ? "block" : "none";
  }
}

// ================= SUBMIT =================
function submitEvaluation() {
  const studentId = document.getElementById('studentId')?.value?.trim() || '';
  const facultyId = window.selectedFaculty?.id || '';
  const feedbackText = document.getElementById('studentFeedback')?.value.trim() || '';

  // Prevent submission when feedback has bad words
  const normalizedFeedback = (" " + feedbackText.toLowerCase().replace(/[^a-z0-9]+/g, " ").replace(/\s+/g, " ").trim() + " ");
  const hasBadWords = [
    "putangina","puta","tangina","tang ina","gago","tanga","bobo","ulol","tarantado","inutil","leche","bwiset","bwisit","punyeta",
    "fuck you","fuck","shit","bitch","asshole","dick","cunt","faggot","nigger"
  ].some(w => normalizedFeedback.includes(` ${w} `));

  if (hasBadWords) {
    const feedbackBox = document.querySelector(".feedback-box");
    const badwordsMsg = document.getElementById("feedbackBadwordsMsg");
    if (feedbackBox) feedbackBox.classList.add("has-badwords");
    if (badwordsMsg) badwordsMsg.style.display = "block";
    alert("Bad words is not allowed");
    updatePaginationButtons();
    return;
  }

  const feedbackBox = document.querySelector(".feedback-box");
  const badwordsMsg = document.getElementById("feedbackBadwordsMsg");
  if (feedbackBox) feedbackBox.classList.remove("has-badwords");
  if (badwordsMsg) badwordsMsg.style.display = "none";

  if (!facultyId) {
    alert('Please select a faculty member to evaluate.');
    updatePaginationButtons();
    return;
  }

  const selected = document.querySelectorAll('input[type="radio"]:checked');
  const data = {};

  selected.forEach(r => {
    data[r.name] = r.value;
  });

  const totalQuestions = document.querySelectorAll('.question-list').length;

  if (Object.keys(data).length < totalQuestions) {
    alert("Please answer all questions!");
    updatePaginationButtons();
    return;
  }

  const nextBtn = document.getElementById("nextBtn");
  if (nextBtn) {
    nextBtn.disabled = true;
    nextBtn.textContent = "Submitting...";
  }

  const facultyLabel = window.selectedFaculty?.name || '';
  const submission = {
    student_id: studentId,
    faculty_id: facultyId,
    faculty_name: facultyLabel,
    answers: data,
    feedback: feedbackText
  };

  fetch("submit_evaluation.php", {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(submission)
  })
    .then(r => r.json())
    .then(async res => {
      if (!res.success) {
        alert(res.message || "Failed to submit evaluation.");
        updatePaginationButtons();
        return;
      }
      alert(`Evaluation submitted! Your overall rating for this instructor: ${res.overall_rating}/5.00`);
      document.querySelectorAll('input[type="radio"]:checked').forEach(el => { el.checked = false; });
      const fb = document.getElementById('studentFeedback');
      if (fb) fb.value = '';
      
      // Refresh faculty cards to remove evaluated faculty
      await loadStudentFacultyCards();

      document.getElementById("evaluateSection").style.display = "block";
      document.getElementById("facultyCards").style.display = "block";
      document.getElementById("evaluationContainer").style.display = "none";
      window.selectedFaculty = null;
      
      // Reset to first criteria page
      currentCriteria = 0;
      criteriaTables.forEach((table, index) => {
        table.classList.toggle("active", index === 0);
      });
      updatePaginationButtons();
    })
    .catch(err => {
      console.error("Submit error:", err);
      alert("Error submitting evaluation. Please try again.");
      updatePaginationButtons();
    });
}

// Add event listener for password form back button
document.addEventListener("DOMContentLoaded", function() {
  loadStudentAcademicPeriod();

  const backBtn = document.getElementById("closePasswordForm");
  if (backBtn) {
    backBtn.addEventListener("click", function(e) {
      e.preventDefault();
      closePasswordForm();
    });
  }
});

// ================= EVALUATION HISTORY =================
async function showEvaluationHistory(event) {
  if (event) event.preventDefault();
  const dropdown = document.getElementById("dropdownMenu");
  if (dropdown) dropdown.style.display = "none";

  const studentId = document.getElementById('studentId')?.value.trim();
  
  if (!studentId) {
    alert('Student ID not found');
    return;
  }
  
  try {
    const response = await fetch('get_student_evaluation_history.php');
    const data = await response.json();
    
    if (!data.success) {
      alert(data.message || 'Failed to load evaluation history');
      return;
    }
    
    displayEvaluationHistory(data.data);
  } catch (error) {
    console.error('Error fetching evaluation history:', error);
    alert('Error loading evaluation history');
  }
}

function displayEvaluationHistory(history) {
  let historyModal = document.getElementById('historyModal');
  if (!historyModal) {
    historyModal = document.createElement('div');
    historyModal.id = 'historyModal';
    historyModal.className = 'historyform';
    document.body.appendChild(historyModal);
  }
  
  let historyContent = '';
  
  if (history.length === 0) {
    historyContent = `
      <div class="history-content">
        <div class="history-header">
          <h3>Evaluation History</h3>
          <button class="close-history-btn" onclick="closeHistoryModal()">×</button>
        </div>
        <div class="history-body">
          <p class="no-history">No evaluations submitted yet.</p>
        </div>
      </div>
    `;
  } else {
    const historyItems = history.map(item => `
      <div class="history-item">
        <div class="history-faculty">
          <i class="ph ph-user-circle"></i>
          <span class="faculty-name">${item.faculty_name}</span>
        </div>
        <div class="history-details">
          <div class="history-rating">
            <span class="rating-label">${item.rating_label}</span>
            <span class="rating-score">${item.overall_rating}/5.00</span>
          </div>
          <div class="history-date">${item.date_evaluated}</div>
        </div>
        ${item.feedback ? `<div class="history-feedback"><strong>Feedback:</strong> ${item.feedback}</div>` : ''}
      </div>
    `).join('');
    
    historyContent = `
      <div class="history-content">
        <div class="history-header">
          <h3>Evaluation History</h3>
          <button class="close-history-btn" onclick="closeHistoryModal()">×</button>
        </div>
        <div class="history-body">
          <div class="history-list">
            ${historyItems}
          </div>
        </div>
      </div>
    `;
  }
  
  historyModal.innerHTML = `
    <div class="logout-content">
      ${historyContent}
    </div>
  `;
  
  historyModal.style.display = 'flex';

  // Attach click-outside listener only once
  if (!historyModal._clickListenerAttached) {
    historyModal.addEventListener('click', function(event) {
      if (event.target === historyModal) {
        closeHistoryModal();
      }
    });
    historyModal._clickListenerAttached = true;
  }
}

function closeHistoryModal() {
  const historyModal = document.getElementById('historyModal');
  if (historyModal) {
    historyModal.style.display = 'none';
  }
}

function displayEvaluationHistory(history) {
  let historyModal = document.getElementById('historyModal');
  if (!historyModal) {
    historyModal = document.createElement('div');
    historyModal.id = 'historyModal';
    historyModal.className = 'historyform';
    document.body.appendChild(historyModal);
  }

  const escapeHistoryHtml = value => String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");

  const normalizeRatingLabel = label => {
    const value = String(label || "").trim();
    return value.toLowerCase() === "excellent" ? "Outstanding" : value;
  };

  const historyItems = history.length === 0
    ? `<p class="no-history">No evaluations submitted yet.</p>`
    : history.map(item => {
      const facultyName = escapeHistoryHtml(item.faculty_name);
      const initial = facultyName.trim().charAt(0).toUpperCase() || "F";
      const label = escapeHistoryHtml(normalizeRatingLabel(item.rating_label));
      const ratingClass = label.toLowerCase().replace(/\s+/g, "-");
      const rating = Number(item.overall_rating || 0).toFixed(2);
      const date = escapeHistoryHtml(item.date_evaluated);
      const feedback = escapeHistoryHtml(item.feedback || "");

      return `
        <div class="history-item" data-history-name="${facultyName.toLowerCase()}">
          <div class="history-item-top">
            <div class="history-main">
              <div class="history-avatar">${initial}</div>
              <div class="history-faculty-info">
                <div class="faculty-name">${facultyName}</div>
                <div class="history-date">${date}</div>
              </div>
            </div>
            <div class="history-rating">
              <div class="rating-label rating-${ratingClass}">${label}</div>
              <div class="rating-score">${rating}/5.00</div>
            </div>
          </div>
          ${feedback ? `<div class="history-feedback"><span>Feedback:</span> ${feedback}</div>` : ""}
        </div>
      `;
    }).join('');

  historyModal.innerHTML = `
    <div class="history-content">
      <button class="close-history-btn" onclick="closeHistoryModal()" aria-label="Close history">
        <i class="ph ph-x"></i>
      </button>
      <div class="history-header">
        <h3>Evaluation History</h3>
        <p>You will see your evaluation history here</p>
      </div>
      <div class="history-search">
        <i class="ph ph-magnifying-glass"></i>
        <input type="search" id="historySearchInput" placeholder="Search faculty">
      </div>
      <div class="history-body">
        <div class="history-list" id="historyList">
          ${historyItems}
        </div>
        <p class="no-history history-no-results" id="historyNoResults" style="display:none;">No matching evaluations found.</p>
      </div>
    </div>
  `;

  const searchInput = historyModal.querySelector("#historySearchInput");
  searchInput?.addEventListener("input", () => {
    const query = searchInput.value.trim().toLowerCase();
    const items = historyModal.querySelectorAll(".history-item");
    let visibleCount = 0;

    items.forEach(item => {
      const isVisible = item.dataset.historyName.includes(query);
      item.style.display = isVisible ? "flex" : "none";
      if (isVisible) visibleCount++;
    });

    const noResults = historyModal.querySelector("#historyNoResults");
    if (noResults) noResults.style.display = items.length && visibleCount === 0 ? "block" : "none";
  });

  historyModal.style.display = 'flex';
  historyModal.onclick = event => {
    if (event.target === historyModal) closeHistoryModal();
  };
}
