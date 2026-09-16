

//walang password open ================= DROPDOWN =================
function closeStudentDropdown() {
  const menu = document.getElementById("dropdownMenu");
  const trigger = document.querySelector(".student-box");
  if (menu) {
    menu.classList.remove("show");
    menu.style.display = "";
  }
  if (trigger) {
    trigger.classList.remove("is-open");
    trigger.setAttribute("aria-expanded", "false");
  }
}

function toggleDropdown() {
  const menu = document.getElementById("dropdownMenu");
  const trigger = document.querySelector(".student-box");
  if (!menu) return;
  menu.style.display = "";
  const isOpen = menu.classList.toggle("show");
  if (trigger) {
    trigger.classList.toggle("is-open", isOpen);
    trigger.setAttribute("aria-expanded", String(isOpen));
  }
}

window.showLogoutModal = () => {
  document.getElementById("logoutModal").style.display = "flex";
  closeStudentDropdown();
};

window.closeLogoutModal = () => {
  document.getElementById("logoutModal").style.display = "none";
};

window.confirmLogout = () => {
  const logoutButton = document.querySelector("#logoutModal .logout-btn");
  const cancelButton = document.querySelector("#logoutModal .no-btn");
  if (!logoutButton || logoutButton.classList.contains("is-loading")) return;

  logoutButton.classList.add("is-loading");
  logoutButton.disabled = true;
  logoutButton.innerHTML = '<span class="logout-spinner" aria-hidden="true"></span> Logging out...';
  if (cancelButton) cancelButton.disabled = true;

  setTimeout(() => location.replace("logout.php"), 800);
};
window.logout = e => { 
  e.preventDefault(); 
  showLogoutModal(); 
};

window.addEventListener("pageshow", event => {
  if (event.persisted || (performance.getEntriesByType("navigation")[0]?.type === "back_forward")) {
    window.location.reload();
  }
});

document.addEventListener("click", e => {
  const m = document.getElementById("dropdownMenu"),
        t = document.querySelector(".student-box");
  if (m && t && m.classList.contains("show") && !t.contains(e.target) && !m.contains(e.target)) {
    closeStudentDropdown();
  }
});

const logoutModal = document.getElementById("logoutModal");
logoutModal?.addEventListener("click", e => {
  if (e.target === logoutModal) closeLogoutModal();
});

function showPasswordForm(e){
  if (e) e.preventDefault();
  closeStudentDropdown();
  
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
function formatStudentYearLevel(value) {
  const level = String(value || "").trim();
  if (!level) return "N/A";
  if (/^irregular$/i.test(level)) return "Irregular";
  if (/year$/i.test(level)) return level;
  const suffix = level === "1" ? "st" : level === "2" ? "nd" : level === "3" ? "rd" : "th";
  return `${level}${suffix} Year`;
}

function formatStudentStatus(value) {
  const status = String(value || "Active").trim();
  return status ? status.charAt(0).toUpperCase() + status.slice(1).toLowerCase() : "Active";
}

function formatStudentType(value) {
  const type = String(value || "Regular").trim();
  return type ? type.charAt(0).toUpperCase() + type.slice(1).toLowerCase() : "Regular";
}

function formatStudentYearAndSection(yearLevel, section) {
  const year = String(yearLevel || "").trim();
  const block = String(section || "").trim();
  const yearNumber = year.match(/\d+/)?.[0] || year;
  if (!yearNumber && !block) return "N/A";
  if (!yearNumber) return block;
  if (!block) return yearNumber;
  return `${yearNumber}-${block}`;
}

function showProfile(e){
  if (e) e.preventDefault();
  closeStudentDropdown();
  
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
    const currentStudentStatus = String(
      additionalData.status || document.getElementById("studentStatus")?.value || "active"
    ).trim().toLowerCase();
    const formattedStatus = formatStudentStatus(currentStudentStatus);
    const studentStatusClass = formattedStatus.toLowerCase() === 'active' ? 'is-active' : 'is-inactive';

    const statusInput = document.getElementById("studentStatus");
    if (statusInput) statusInput.value = currentStudentStatus;
    const statusIndicator = document.querySelector(".status-indicator");
    if (statusIndicator) {
      statusIndicator.classList.toggle("active", currentStudentStatus === "active");
      statusIndicator.classList.toggle("inactive", currentStudentStatus !== "active");
    }

    const profileContent = `
      <div class="sp-modal">
        <div class="sp-body">
          <div class="sp-name-row">
            <div class="sp-initial">${(studentName || 'S').charAt(0).toUpperCase()}</div>
            <div>
              <div class="sp-name">${studentName || 'Student'}</div>
              <div class="sp-role-badge">Student</div>
            </div>
            <span class="sp-status-badge ${studentStatusClass}"><span class="sp-status-dot" aria-hidden="true"></span>${formattedStatus}</span>
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
                <span class="sp-info-value">${additionalData.program_name || studentProgram || 'N/A'}</span>
              </div>
            </div>
            <div class="sp-info-row">
              <div class="sp-info-content">
                <span class="sp-info-label">Year and Section</span>
                <span class="sp-info-value">${formatStudentYearAndSection(studentYearLevel, studentSection)}</span>
              </div>
            </div>
            <div class="sp-info-row">
              <div class="sp-info-content">
                <span class="sp-info-label">Type</span>
                <span class="sp-info-value">${formatStudentType(additionalData.student_type)}</span>
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
      cache: 'no-store',
      credentials: 'same-origin',
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

// Global notification (now using unified system from unified_notifications.js)
// Old function removed - using consistent notifications across all pages

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
        // Close form first
        closePasswordForm();
        document.getElementById("oldPass").value = "";
        document.getElementById("newPass").value = "";
        document.getElementById("confirmPass").value = "";
        clearPassMsgs();
        
        // Show success notification outside the modal
        showGlobalNotification("Password updated successfully!", "success");
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
  console.log("updateStudentAcademicPeriodDisplay called with:", status);
  const periodEl = document.getElementById("studentAcademicPeriod");
  if (!periodEl) return;

  const academicYear = (status?.current_academic_year || status?.active_academic_year || "").trim();
  const semester = (status?.current_semester || status?.active_semester || "").trim();

  if (academicYear && semester) {
    periodEl.textContent = `Academic Year: ${academicYear} - ${semester}`;
  } else {
    periodEl.textContent = status?.active_period_name
      ? `Academic Period: ${status.active_period_name}`
      : "Academic Year: No active period";
  }

  // Update Evaluate Now button state in real-time
  const evaluateBtn = document.querySelector(".evaluate-now-btn");
  if (evaluateBtn) {
    const studentActive = (document.getElementById("studentStatus")?.value || "active").trim().toLowerCase() === "active";
    const evaluationOpen = !!(status && status.success && status.evaluation_open);
    console.log("Button state check - Student Active:", studentActive, "Evaluation Open:", evaluationOpen);
    if (studentActive && evaluationOpen) {
      console.log("ENABLING button");
      evaluateBtn.removeAttribute("disabled");
      evaluateBtn.disabled = false;
      evaluateBtn.setAttribute("aria-disabled", "false");
      evaluateBtn.style.opacity = "1";
      evaluateBtn.style.cursor = "pointer";
    } else {
      console.log("DISABLING button - studentActive:", studentActive, "evaluationOpen:", evaluationOpen);
      evaluateBtn.setAttribute("disabled", "true");
      evaluateBtn.disabled = true;
      evaluateBtn.setAttribute("aria-disabled", "true");
      evaluateBtn.style.opacity = "0.5";
      evaluateBtn.style.cursor = "not-allowed";
    }
  } else {
    console.error("Evaluate button not found!");
  }
}

async function loadStudentAcademicPeriod() {
  try {
    const statusRes = await fetch("periods_api.php?action=status", { cache: "no-store", credentials: "same-origin" });
    const status = await statusRes.json();
    console.log("Period status received:", status);
    if (status && status.success) {
      updateStudentAcademicPeriodDisplay(status);
    } else {
      console.error("Status check failed:", status);
      updateStudentAcademicPeriodDisplay(null);
    }
  } catch (error) {
    console.error("Error loading period status:", error);
    updateStudentAcademicPeriodDisplay(null);
  }
}

async function showEvaluateSection() {
  if ((document.getElementById("studentStatus")?.value || "active").trim().toLowerCase() !== "active") {
    alert("Your account has been set to inactive by an admin. You cannot evaluate until your account is active again.");
    return;
  }

  try {
    const statusRes = await fetch("periods_api.php?action=status", { cache: "no-store", credentials: "same-origin" });
    const status = await statusRes.json();
    updateStudentAcademicPeriodDisplay(status);
    if (!status || !status.success || !status.evaluation_open) {
      showGlobalNotification("Evaluation is closed", "warning");
      return;
    }
  } catch (_) {
    // If status check fails, be safe and block entry.
    showGlobalNotification("Evaluation is closed", "warning");
    return;
  }

  const mainPage = document.getElementById("mainPage");
  const evaluateSection = document.getElementById("evaluateSection");
  const facultyCards = document.getElementById("facultyCards");
  const evaluationContainer = document.getElementById("evaluationContainer");

  if (mainPage) mainPage.style.display = "none";
  if (evaluateSection) evaluateSection.style.display = "block";
  if (facultyCards) facultyCards.style.display = "block";
  if (evaluationContainer) evaluationContainer.style.display = "none";

  await loadStudentFacultyCards();
}
function goBackToMain() {
  const evalContainer = document.getElementById("evaluationContainer");
  const facultyCards = document.getElementById("facultyCards");
  const evaluateSection = document.getElementById("evaluateSection");
  const mainPage = document.getElementById("mainPage");

  if (evalContainer && evalContainer.style.display !== "none") {
    // If currently evaluating, return to faculty cards list
    evalContainer.style.display = "none";
    if (facultyCards) facultyCards.style.display = "block";
    window.selectedFaculty = null;
  } else {
    // Otherwise, go back to main student dashboard
    if (evaluateSection) evaluateSection.style.display = "none";
    if (evalContainer) evalContainer.style.display = "none";
    if (facultyCards) facultyCards.style.display = "none";
    if (mainPage) mainPage.style.display = "block";
  }
}

async function refreshStudentPeriodAccess() {
  try {
    const statusRes = await fetch("periods_api.php?action=status", { cache: "no-store", credentials: "same-origin" });
    const status = await statusRes.json();
    updateStudentAcademicPeriodDisplay(status);

    const evaluateSection = document.getElementById("evaluateSection");
    const isEvaluating = evaluateSection && evaluateSection.style.display !== "none";
    if (isEvaluating) {
      if (!status || !status.success || !status.evaluation_open) {
        goBackToMain();
        showGlobalNotification("Evaluation period has ended or is closed", "warning");
      }
    }
  } catch (_) {
    const evaluateSection = document.getElementById("evaluateSection");
    const isEvaluating = evaluateSection && evaluateSection.style.display !== "none";
    if (isEvaluating) {
      goBackToMain();
      showGlobalNotification("Evaluation is closed", "warning");
    }
  }
}

setInterval(refreshStudentPeriodAccess, 1000);
// ================= LOAD CATEGORIES + QUESTIONS =================
let currentCriteria = 0;
let criteriaTables = [];
let evaluationSubmitting = false;

async function loadStudentFacultyCards() {
  const studentId = document.getElementById('studentId')?.value.trim();
  const yearLevel = document.getElementById('studentYearLevel')?.value.trim();
  const container = document.getElementById('facultyContainer');

  if (!container) return;

  // Block evaluate when evaluation is closed
  const studentActive = (document.getElementById("studentStatus")?.value || "active").trim().toLowerCase() === "active";
  let evaluationOpen = false;
  try {
    const statusRes = await fetch("periods_api.php?action=status", { cache: "no-store", credentials: "same-origin" });
    const status = await statusRes.json();
    evaluationOpen = studentActive && !!(status && status.success && status.evaluation_open);
  } catch (_) {
    evaluationOpen = false;
  }

  if (!studentId && !yearLevel) {
    container.innerHTML = renderFacultyEmptyState("No Faculty Available", "There are no instructors assigned to your account for evaluation right now.");
    return;
  }

  const params = new URLSearchParams();
  if (studentId) params.set('student_id', studentId);
  else if (yearLevel) params.set('year_level', yearLevel);

  try {
    const res = await fetch(`getFaculty.php?${params.toString()}`, {
      cache: "no-store",
      credentials: "same-origin"
    });
    const facultyList = await res.json();

    const assignments = Array.isArray(facultyList) ? facultyList : [];
    const evaluatedCount = assignments.filter(faculty => Number(faculty.evaluation_id || 0) > 0).length;
    const pendingCount = assignments.length - evaluatedCount;
    updateEvaluationSummary(pendingCount, evaluatedCount);
    const periodLabel = document.getElementById("studentAcademicPeriod")?.textContent || "";
    const periodLabelEl = document.getElementById("evaluationPeriodLabel");
    const subjectCountEl = document.getElementById("evaluationSubjectCount");
    if (periodLabelEl) periodLabelEl.textContent = periodLabel.replace(/^Academic Year:\s*/i, "— ");
    if (subjectCountEl) subjectCountEl.textContent = `${assignments.length} subject${assignments.length === 1 ? "" : "s"}`;

    container.innerHTML = '';

    if (assignments.length > 0) {
      assignments.forEach(faculty => {
        const subject = Array.isArray(faculty.subjects) && faculty.subjects.length
          ? faculty.subjects[0]
          : faculty;
        const subjectCode = subject.subject_code || faculty.subject_code || '';
        const subjectDesc = subject.subject_desc || faculty.subject_desc || '';
        const subjectLabels = [subjectCode, subjectDesc].filter(Boolean).join(' - ');
        const isIrregularStudent = yearLevel.toLowerCase() === 'irregular';
        const subjectYearLevel = subject.year_level || subject.class_year_level || '';
        const programCode = subject.program_code || faculty.program_code || '';
        const classLabel = [
          subject.class_year_level || faculty.class_year_level || '',
          subject.class_section || faculty.class_section || ''
        ].filter(Boolean).join(' / ');

        const nameParts = [
          faculty.firstname || '',
          faculty.lastname || '',
          faculty.suffix ? faculty.suffix : ''
        ].filter(Boolean);

        const fullName = nameParts.join(' ');
        const isEvaluated = Number(faculty.evaluation_id || 0) > 0;
        const leftLabel = isIrregularStudent && subjectYearLevel ? subjectYearLevel : subjectCode;

        const card = document.createElement('div');
        card.className = 'faculty-evaluation-row';
        card.onclick = () => {
          if (isEvaluated) return;
          if (!studentActive) return showGlobalNotification("Your account has been set to inactive by an admin. You cannot evaluate until your account is active again", "warning");
          if (!evaluationOpen) return showGlobalNotification("Evaluation is closed", "warning");
          selectFaculty(faculty.id, fullName, {
            subject_id: Number(subject.subject_id || faculty.subject_id || 0),
            class_id: Number(subject.class_id || faculty.class_id || 0),
            subject_label: subjectLabels,
            class_label: classLabel
          });
        };
         
        card.innerHTML = `
          <div class="subject-code-badge">${leftLabel || 'Subject'}</div>
          <div class="faculty-evaluation-details">
            <h3 class="faculty-name">${subjectLabels || 'Assigned Subject'}</h3>
            <p class="faculty-evaluation-meta">${fullName || 'Faculty Member'}${programCode ? ` <span>·</span> ${programCode}` : ''}</p>
          </div>
            <button class="evaluate-faculty-btn ${isEvaluated ? "evaluated" : ""}" ${evaluationOpen && !isEvaluated ? "" : "disabled"}>
              <i class="ph ${isEvaluated ? "ph-check-circle" : "ph-note-pencil"}"></i> ${isEvaluated ? "Evaluated" : "Evaluate Now"}
          </button>
        `;
        
        container.appendChild(card);
      });
    } else {
      container.innerHTML = evaluatedCount > 0
        ? renderFacultyEmptyState("All Done!", "You have completed all evaluations.", "ph-check-circle")
        : renderFacultyEmptyState("No Faculty Available", "There are no instructors assigned to your account for evaluation right now.");
    }
  } catch (err) {
    console.error('Error loading faculty cards:', err);
    container.innerHTML = renderFacultyEmptyState("Unable to Load Faculty", "Please refresh the page or try again later.", "ph-warning-circle");
  }
}

function renderFacultyEmptyState(title, message, icon = "ph-chalkboard-teacher") {
  return `
    <div class="no-faculty" role="status">
      <div class="no-faculty-icon"><i class="ph ${icon}"></i></div>
      <div class="no-faculty-copy">
        <span class="no-faculty-title">${title}</span>
        <p>${message}</p>
      </div>
    </div>
  `;
}

function updateEvaluationSummary(pendingCount, evaluatedCount) {
  const total = pendingCount + evaluatedCount;
  const percent = total > 0 ? Math.round((evaluatedCount / total) * 100) : 100;
  const percentEl = document.getElementById("summaryPercent");
  const totalEl = document.getElementById("summaryTotal");
  const evaluatedEl = document.getElementById("summaryEvaluated");
  const pendingEl = document.getElementById("summaryPending");
  const progressEl = document.querySelector(".summary-progress");

  if (percentEl) percentEl.textContent = `${percent}%`;
  if (progressEl) progressEl.style.setProperty("--progress", percent);
  if (totalEl) totalEl.textContent = total;
  if (evaluatedEl) evaluatedEl.textContent = evaluatedCount;
  if (pendingEl) pendingEl.textContent = pendingCount;
}

function selectFaculty(facultyId, facultyName, assignment) {
  // Store selected faculty data
  window.selectedFaculty = {
    id: facultyId,
    name: facultyName,
    subject_id: Number(assignment?.subject_id || 0),
    class_id: Number(assignment?.class_id || 0),
    subject_label: assignment?.subject_label || '',
    class_label: assignment?.class_label || ''
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
    const selectedSubjectText = [
      window.selectedFaculty?.name || '',
      window.selectedFaculty?.subject_label || '',
      window.selectedFaculty?.class_label || ''
    ].filter(Boolean).join(' | ');
    headerSection.innerHTML = `
      <h2 class="performance-evaluation-title">Performance Evaluation</h2>
      ${selectedSubjectText ? `<p class="performance-evaluation-subtitle">${selectedSubjectText}</p>` : ''}
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

    const timeline = document.createElement("div");
    timeline.className = "evaluation-timeline";
    timeline.setAttribute("aria-label", "Evaluation pages");
    timeline.innerHTML = categories.map((category, index) => `
      <div class="evaluation-timeline-step${index === 0 ? " active" : ""}" data-page="${index}">
        <span class="evaluation-timeline-dot">${index + 1}</span>
        <span class="evaluation-timeline-label">${category.category_name}</span>
      </div>
    `).join("");
    formContentArea.appendChild(timeline);

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
  const timelineSteps = document.querySelectorAll(".evaluation-timeline-step");
  
  if (pageInfo) {
    pageInfo.textContent = `${currentCriteria + 1} / ${criteriaTables.length}`;
  }

  timelineSteps.forEach((step, index) => {
    step.classList.toggle("active", index === currentCriteria);
    step.classList.toggle("completed", index < currentCriteria);
  });
  
  if (prevBtn) {
    prevBtn.disabled = currentCriteria === 0;
  }
  
  if (nextBtn) {
    const isLastPage = currentCriteria === criteriaTables.length - 1;
    nextBtn.disabled = evaluationSubmitting;
    nextBtn.textContent = isLastPage ? "Submit" : "Next";
    if (evaluationSubmitting) nextBtn.textContent = "Submitting...";
    nextBtn.onclick = isLastPage ? submitEvaluation : nextCriteria;
  }
  
  // Show feedback and submit button only on last page
  if (feedbackSubmitContainer) {
    feedbackSubmitContainer.style.display = currentCriteria === criteriaTables.length - 1 ? "block" : "none";
  }
}

function setFeedbackBadwordsState(hasBadWords) {
  const feedbackBox = document.querySelector(".feedback-box");
  const badwordsMsg = document.getElementById("feedbackBadwordsMsg");
  if (feedbackBox) feedbackBox.classList.toggle("has-badwords", hasBadWords);
  if (badwordsMsg) badwordsMsg.style.display = hasBadWords ? "block" : "none";
}

// ================= SUBMIT =================
function submitEvaluation() {
  const studentId = document.getElementById('studentId')?.value?.trim() || '';
  const facultyId = window.selectedFaculty?.id || '';
  const subjectId = window.selectedFaculty?.subject_id || '';
  const feedbackText = document.getElementById('studentFeedback')?.value.trim() || '';

  // Prevent submission when feedback has bad words
  const normalizedFeedback = (" " + feedbackText.toLowerCase().replace(/[^a-z0-9]+/g, " ").replace(/\s+/g, " ").trim() + " ");
  const hasBadWords = [
    "putangina","puta","tangina","tang ina","gago","tanga","bobo","ulol","tarantado","inutil","leche","bwiset","bwisit","punyeta",
    "fuck you","fuck","shit","bitch","asshole","dick","cunt","faggot","nigger"
  ].some(w => normalizedFeedback.includes(` ${w} `));

  if (hasBadWords) {
    setFeedbackBadwordsState(true);
    updatePaginationButtons();
    return;
  }

  setFeedbackBadwordsState(false);

  if (!facultyId || !subjectId) {
    showGlobalNotification("Please select a subject to evaluate.", "warning");
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
    showGlobalNotification("Please answer all questions!", "warning");
    updatePaginationButtons();
    return;
  }

  // ---- All validation passed: show Final Review modal ----
  showFinalReviewModal();
}

function escapeReviewHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function getEvaluationReviewSummary() {
  const feedback = document.getElementById('studentFeedback')?.value.trim() || '';
  const categories = Array.from(document.querySelectorAll('.evaluationform')).map(table => {
    const title = table.querySelector('thead th:first-child')?.textContent?.trim() || 'Evaluation';
    const questions = Array.from(table.querySelectorAll('tbody tr')).map(row => {
      const question = row.querySelector('.question-list')?.textContent?.trim() || '';
      const selected = row.querySelector('input[type="radio"]:checked')?.value || '';

      return { question, selected };
    }).filter(item => item.question);

    return { title, questions };
  }).filter(category => category.questions.length);

  return {
    categories,
    feedback: feedback || "No feedback provided."
  };
}

function showFinalReviewModal() {
  let modal = document.getElementById('finalReviewModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'finalReviewModal';
    modal.className = 'final-review-overlay';
    modal.addEventListener('click', e => {
      if (e.target === modal) closeFinalReviewModal();
    });
    document.body.appendChild(modal);
  }
  modal.innerHTML = `
    <div class="final-review-card" role="dialog" aria-modal="true" aria-labelledby="frTitle">
      <div class="fr-icon-wrap">
        <i class="ph ph-paper-plane-tilt fr-icon"></i>
      </div>
      <h2 class="fr-title" id="frTitle">Final Review</h2>
      <p class="fr-sub">Submit this evaluation?</p>
      <p class="fr-warning">This cannot be undone.</p>
      <button class="fr-submit-btn" onclick="_doSubmitEvaluation()">
        <i class="ph ph-check-circle"></i> YES, SUBMIT NOW
      </button>
      <button class="fr-cancel-btn" onclick="showReviewAgainSummaryModal()">
        Review Again
      </button>
    </div>
  `;
  requestAnimationFrame(() => modal.classList.add('fr-visible'));
}

function showReviewAgainSummaryModal() {
  const modal = document.getElementById('finalReviewModal');
  if (!modal) return;

  const summary = getEvaluationReviewSummary();
  const summaryHtml = summary.categories.map(category => `
    <div class="fr-mini-category">
      <div class="fr-mini-category-title">${escapeReviewHtml(category.title)}</div>
      <div class="fr-mini-table" role="table" aria-label="${escapeReviewHtml(category.title)}">
        <div class="fr-mini-head" role="row">
          <span>Question</span>
          <span>5</span>
          <span>4</span>
          <span>3</span>
          <span>2</span>
          <span>1</span>
        </div>
        ${category.questions.map(item => `
          <div class="fr-mini-row" role="row">
            <span class="fr-mini-question">${escapeReviewHtml(item.question)}</span>
            ${[5, 4, 3, 2, 1].map(score => `
              <span class="fr-mini-rating ${String(score) === item.selected ? 'is-selected' : ''}" aria-label="${String(score) === item.selected ? `Selected ${score}` : `Not selected ${score}`}"></span>
            `).join("")}
          </div>
        `).join("")}
      </div>
    </div>
  `).join("");

  modal.innerHTML = `
    <div class="final-review-card fr-summary-card" role="dialog" aria-modal="true" aria-labelledby="frSummaryTitle">
      <h2 class="fr-title" id="frSummaryTitle">Review Evaluation</h2>
      <div class="fr-mini-summary">
        ${summaryHtml}
      </div>
      <div class="fr-feedback-preview">
        <span>Feedback</span>
        <p>${escapeReviewHtml(summary.feedback)}</p>
      </div>
      <div class="fr-summary-actions">
        <button class="fr-back-btn" onclick="closeFinalReviewModal()">Go Back to Form</button>
        <button class="fr-submit-btn" onclick="_doSubmitEvaluation()">
          <i class="ph ph-check-circle"></i> Submit
        </button>
      </div>
    </div>
  `;
}

function closeFinalReviewModal() {
  const modal = document.getElementById('finalReviewModal');
  if (modal) {
    modal.classList.remove('fr-visible');
  }
}

function _doSubmitEvaluation() {
  if (evaluationSubmitting) return;
  evaluationSubmitting = true;
  closeFinalReviewModal();

  const studentId = document.getElementById('studentId')?.value?.trim() || '';
  const facultyId = window.selectedFaculty?.id || '';
  const subjectId = window.selectedFaculty?.subject_id || '';
  const classId = window.selectedFaculty?.class_id || 0;
  const feedbackText = document.getElementById('studentFeedback')?.value.trim() || '';
  const selected = document.querySelectorAll('input[type="radio"]:checked');
  const data = {};
  selected.forEach(r => { data[r.name] = r.value; });

  const nextBtn = document.getElementById("nextBtn");
  if (nextBtn) {
    nextBtn.disabled = true;
    nextBtn.textContent = "Submitting...";
  }

  const facultyLabel = window.selectedFaculty?.name || '';
  const submission = {
    student_id: studentId,
    faculty_id: facultyId,
    subject_id: subjectId,
    class_id: classId,
    faculty_name: facultyLabel,
    subject_name: window.selectedFaculty?.subject_label || '',
    answers: data,
    feedback: feedbackText
  };

  fetch("submit_evaluation.php", {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(submission)
  })
    .then(async response => {
      const responseText = await response.text();
      let result;
      try {
        result = JSON.parse(responseText);
      } catch (parseError) {
        throw new Error(`Invalid submission response (${response.status})`);
      }
      if (!response.ok) {
        throw new Error(result.message || `Submission failed (${response.status})`);
      }
      return result;
    })
    .then(async res => {
      if (!res.success) {
        if ((res.message || "").toLowerCase().includes("bad words")) {
          setFeedbackBadwordsState(true);
          return;
        }
        showGlobalNotification(res.message || "Failed to submit evaluation.", "error");
        return;
      }

      // Show the success modal with rating
      showSuccessModal(res.overall_rating);

      document.querySelectorAll('input[type="radio"]:checked').forEach(el => { el.checked = false; });
      const fb = document.getElementById('studentFeedback');
      if (fb) fb.value = '';

      // Refresh faculty cards to remove evaluated faculty
      try {
        await loadStudentFacultyCards();
        await updateStudentStats();
      } catch (refreshError) {
        console.error("Evaluation saved, but faculty list refresh failed:", refreshError);
      }

      document.getElementById("evaluateSection")?.style.setProperty("display", "block");
      document.getElementById("facultyCards")?.style.setProperty("display", "block");
      document.getElementById("evaluationContainer")?.style.setProperty("display", "none");
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
      showGlobalNotification(err.message || "Error submitting evaluation. Please try again.", "error");
    })
    .finally(() => {
      evaluationSubmitting = false;
      updatePaginationButtons();
    });
}

// ================= SUCCESS MODAL =================
function showSuccessModal(overallRating) {
  let modal = document.getElementById('evalSuccessModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'evalSuccessModal';
    modal.className = 'success-overlay';
    document.body.appendChild(modal);
  }
  const ratingText = overallRating ? `Overall Rating: ${parseFloat(overallRating).toFixed(2)} / 5.00` : '';
  modal.innerHTML = `
    <div class="success-card" role="dialog" aria-modal="true">
      <div class="su-icon-wrap">
        <i class="ph ph-check-circle su-icon"></i>
      </div>
      <h2 class="su-title">Success!</h2>
      <p class="su-message">Evaluation submitted successfully!</p>
      ${ratingText ? `<span class="su-rating">${ratingText}</span>` : ''}
      <button class="su-ok-btn" onclick="closeSuccessModal()">OK</button>
    </div>
  `;
  requestAnimationFrame(() => modal.classList.add('su-visible'));
}

function closeSuccessModal() {
  const modal = document.getElementById('evalSuccessModal');
  if (modal) modal.classList.remove('su-visible');
}

// Add event listener for password form back button
document.addEventListener("DOMContentLoaded", function() {
  loadStudentAcademicPeriod();
  setInterval(loadStudentAcademicPeriod, 15000);
  loadStudentStats(); // Load stats for the dashboard

  const backBtn = document.getElementById("closePasswordForm");
  if (backBtn) {
    backBtn.addEventListener("click", function(e) {
      e.preventDefault();
      closePasswordForm();
    });
  }
});

// ================= LOAD STUDENT STATS =================
async function loadStudentStats() {
  const studentId = document.getElementById('studentId')?.value.trim();
  if (!studentId) return;

  try {
    // Get evaluations done count
    const evalResponse = await fetch(`get_student_evaluations.php?student_id=${studentId}`);
    const evalData = await evalResponse.json();
    
    if (evalData && evalData.success) {
      const evaluationsDone = evalData.count || 0;
      const evaluationsDoneEl = document.getElementById('evaluationsDone');
      if (evaluationsDoneEl) {
        evaluationsDoneEl.textContent = evaluationsDone;
      }
    }

    // Get pending faculty count
    const facultyResponse = await fetch(`getFaculty.php?student_id=${studentId}`);
    const facultyData = await facultyResponse.json();
    
    if (Array.isArray(facultyData)) {
      const pendingFaculty = facultyData.length;
      const pendingFacultyEl = document.getElementById('pendingFaculty');
      if (pendingFacultyEl) {
        pendingFacultyEl.textContent = pendingFaculty;
      }
    }
  } catch (error) {
    console.error('Error loading student stats:', error);
  }
}

// ================= EVALUATION HISTORY =================
async function showEvaluationHistory(event) {
  if (event) event.preventDefault();
  closeStudentDropdown();

  const studentId = document.getElementById('studentId')?.value.trim();
  
  if (!studentId) {
    alert('Student ID not found');
    return;
  }
  
  try {
    const response = await fetch('get_student_evaluation_history.php', {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-cache'
    });
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
      const subjectName = escapeHistoryHtml(item.subject_label || item.subject_name || "");
      const className = escapeHistoryHtml(item.class_label || "");
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
                ${subjectName ? `<div class="history-date">${subjectName}${className ? ` (${className})` : ""}</div>` : ""}
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
