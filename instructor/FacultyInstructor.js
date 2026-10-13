// DROPDOWN
let selectedFacultyReportPeriod = null;

function toggleDropdown() {
  const menu = document.getElementById("dropdownMenu");
  menu.style.display = (menu.style.display === "block") ? "none" : "block";
}

function logout(e){
  e.preventDefault();
  // Calculate scrollbar width before hiding overflow
  const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
  document.documentElement.style.setProperty('--scrollbar-width', `${scrollbarWidth}px`);
  document.body.classList.add("modal-open");
  document.querySelector(".navbar")?.classList.add("modal-compensate");
  document.getElementById("logoutModal").style.display = "flex";}

function closeLogoutModal(){
  document.getElementById("logoutModal").style.display = "none";
  document.body.classList.remove("modal-open");
  document.querySelector(".navbar")?.classList.remove("modal-compensate");
  document.documentElement.style.removeProperty('--scrollbar-width');
}

function confirmLogout(){
  const logoutButton = document.querySelector("#logoutModal .logout-btn");
  const cancelButton = document.querySelector("#logoutModal .no-btn");
  if (!logoutButton || logoutButton.classList.contains("is-loading")) return;

  logoutButton.classList.add("is-loading");
  logoutButton.disabled = true;
  logoutButton.innerHTML = '<span class="logout-spinner" aria-hidden="true"></span> Logging out...';
  if (cancelButton) cancelButton.disabled = true;

  setTimeout(() => window.location.replace("../shared/logout.php"), 800);
}

window.addEventListener("pageshow", event => {
  if (event.persisted || (performance.getEntriesByType("navigation")[0]?.type === "back_forward")) {
    window.location.reload();
  }
});

function showPasswordForm(e){
  if (e) e.preventDefault();
  document.getElementById("dropdownMenu").style.display = "none";
  
  // Calculate scrollbar width before hiding overflow
  const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
  document.documentElement.style.setProperty('--scrollbar-width', `${scrollbarWidth}px`);
  
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
  document.querySelector(".navbar")?.classList.add("modal-compensate");
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
  document.querySelector(".navbar")?.classList.remove("modal-compensate");
  document.documentElement.style.removeProperty('--scrollbar-width');
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

function escapeHtml(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function formatAcademicDate(dateValue) {
  if (!dateValue) return "Not specified";
  const date = new Date(`${dateValue}T00:00:00`);
  return Number.isNaN(date.getTime())
    ? String(dateValue)
    : new Intl.DateTimeFormat(undefined, { year: "numeric", month: "long", day: "numeric" }).format(date);
}

function getOverallStatusLabel(score) {
  const value = Number(score);
  if (isNaN(value)) return 'No Status';
  if (value >= 4.5) return 'Outstanding';
  if (value >= 3.5) return 'Very Good';
  if (value >= 2.5) return 'Good';
  if (value >= 1.5) return 'Fair';
  return 'Poor';
}

function getStatusBadgeClass(score) {
  const value = Number(score);
  if (isNaN(value)) return 'status-poor';
  if (value >= 4.5) return 'status-outstanding';
  if (value >= 3.5) return 'status-very-good';
  if (value >= 2.5) return 'status-good';
  if (value >= 1.5) return 'status-fair';
  return 'status-poor';
}

function getRatingPercentageValue(score) {
  const value = Number(score);
  if (!Number.isFinite(value)) return 0;
  return Math.max(0, Math.min(100, value * 20));
}

function formatRatingPercentage(score) {
  return `${getRatingPercentageValue(score).toFixed(0)}%`;
}

function isFacultyInactive() {
  return (document.getElementById("facultyStatus")?.value || "active").toLowerCase() !== "active";
}

function isEvaluationOngoing() {
  return (document.getElementById("evaluationOngoing")?.value || "0") === "1";
}

function setFacultyResultCardsUnavailable(message = "Unavailable") {
  setFacultyStatsLoading(false);

  const ratingNumberEl = document.getElementById("overallRatingNumber");
  const ratingStatusEl = document.getElementById("overallRatingStatus");
  const responsesEl    = document.getElementById("totalResponsesValue");
  const subjectsEl     = document.getElementById("subjectsEvaluatedValue");

  if (ratingNumberEl) ratingNumberEl.textContent = "0.00";
  if (ratingStatusEl) ratingStatusEl.textContent = message;
  if (responsesEl)    responsesEl.textContent = "0";
  if (subjectsEl)     subjectsEl.textContent = "0";
}

function setFacultyStatsLoading(isLoading) {
  const cards = document.querySelector(".stats-cards-container");
  if (!cards) return;

  cards.classList.toggle("stats-loading", Boolean(isLoading));
  cards.setAttribute("aria-busy", isLoading ? "true" : "false");
}

function updateFacultyResultAccess(evaluationOpen) {
  const hidden = document.getElementById("evaluationOngoing");
  if (hidden) hidden.value = evaluationOpen ? "1" : "0";

  const locked = isFacultyInactive() || evaluationOpen || !selectedFacultyReportPeriod;
  document.querySelectorAll("#evaluationReportButton, #perSubjectReportButton").forEach(btn => {
    btn.disabled = locked;
    btn.title = evaluationOpen
      ? "Evaluation results are available after the current evaluation process closes."
      : (!selectedFacultyReportPeriod ? "No closed evaluation period results are available." : "");
  });

  if (evaluationOpen || !selectedFacultyReportPeriod) {
    setFacultyResultCardsUnavailable(evaluationOpen ? "Unavailable" : "No Data");
  }
}

function formatFeedbackForHtml(feedback) {
  const text = String(feedback || 'No feedback available').trim() || 'No feedback available';
  return escapeHtml(text).replace(/\n/g, '<br>');
}

function renderFeedbackList(feedbackData) {
  const items = Array.isArray(feedbackData)
    ? feedbackData.filter(Boolean)
    : String(feedbackData || '').split(/\n\s*\n/).filter(Boolean);

  if (items.length === 0) {
    return '<div class="feedback-item empty">No feedback available yet.</div>';
  }

  return items.slice(0, 6).map(item => `
    <div class="feedback-item">
      <p>${formatFeedbackForHtml(item)}</p>
    </div>
  `).join('');
}

function renderCategoryTotals(categoryTotals) {
  if (!Array.isArray(categoryTotals) || categoryTotals.length === 0) {
    return '<div class="category-empty">No category totals available yet.</div>';
  }

  // Determine if weights are meaningful (any non-zero)
  const hasWeights = categoryTotals.some(item => parseFloat(item.weight || 0) > 0);

  return `
    <div class="category-table-wrap">
      <table class="category-table">
        <thead>
          <tr>
            <th>Category</th>
            ${hasWeights ? '<th>Weight</th>' : ''}
            <th>Percentage</th>
            <th>Average</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          ${categoryTotals.map(item => {
            const avg         = Number(item.avg_rating) || 0;
            const responseCount = Number(item.response_count || item.responses || 0);
            const hasResponses = responseCount > 0;
            
            const percent     = hasResponses ? getRatingPercentageValue(avg) : 0;
            const percentText = hasResponses ? formatRatingPercentage(avg) : '0%';
            const statusLabel = hasResponses ? getOverallStatusLabel(avg) : 'N/A';
            const statusClass = hasResponses ? getStatusBadgeClass(avg).replace(/^status-/, '') : 'na';
            const normW       = Number(item.normalised_weight ?? item.weight ?? 0);
            const weightText  = hasWeights
              ? `${(Number.isFinite(normW) ? normW : 0).toFixed(1)}%`
              : '—';
            return `
            <tr>
              <td>${escapeHtml(item.category_name || 'Uncategorized')}</td>
              ${hasWeights ? `<td><span class="category-weight-tag">${weightText}</span></td>` : ''}
              <td>
                <div class="category-percent-cell">
                  <span>${percentText}</span>
                  <div class="category-percent-track">
                    <div class="category-percent-fill ${statusClass}" style="width: ${percent}%;"></div>
                  </div>
                </div>
              </td>
              <td>${escapeHtml(item.avg_rating || '0.00')} / 5.00</td>
              <td><span class="report-badge ${statusClass}">${escapeHtml(statusLabel)}</span></td>
            </tr>
          `}).join('')}
        </tbody>
      </table>
    </div>
  `;
}

function showProfile(e){
  if (e) e.preventDefault();
  // Close dropdown first
  document.getElementById("dropdownMenu").style.display = "none";
  
  // Calculate scrollbar width before hiding overflow
  const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
  document.documentElement.style.setProperty('--scrollbar-width', `${scrollbarWidth}px`);
  
  // Get faculty information from hidden fields
  const facultyId = document.getElementById("facultyId")?.value;
  const facultyNumericId = document.getElementById("facultyNumericId")?.value;
  const facultyName = document.getElementById("facultyName")?.value;
  const facultyEmail = document.getElementById("facultyEmail")?.value;
  const facultyStatus = (document.getElementById("facultyStatus")?.value || "active").toLowerCase();
  const statusLabel = facultyStatus === "active" ? "Active" : "Inactive";
  
  // Fetch by both identifiers and merge the results so assignments are never lost.
  const profileLookups = [...new Set([facultyNumericId, facultyId].filter(Boolean))];
  Promise.all(profileLookups.map(fetchFacultyProfileData)).then(profileResponses => {
    const additionalData = profileResponses.find(response => response?.success) || {};
    const subjects = [
      ...(Array.isArray(window.facultyAssignedSubjects) ? window.facultyAssignedSubjects : []),
      ...profileResponses
      .flatMap(response => response?.data?.subjects || [])
    ]
      .filter((subject, index, list) => {
        const key = `${subject.code || ''}|${subject.name || ''}`;
        return index === list.findIndex(item => `${item.code || ''}|${item.name || ''}` === key);
      });

    // Build subjects HTML
    const subjectsHtml = buildAssignedSubjectsHtml(subjects);

    // Create profile modal content with real data matching student profile design
    const profileContent = `
      <div class="sp-modal">
        <div class="sp-body">
          <div class="sp-name-row">
            <div class="sp-initial">${(facultyName || 'F').charAt(0).toUpperCase()}</div>
            <div>
              <div class="sp-name">${facultyName || 'Faculty'}</div>
              <div class="sp-role-badge">Instructor</div>
            </div>
            <span class="sp-status-badge ${facultyStatus === 'active' ? 'is-active' : 'is-inactive'}"><span class="sp-status-dot" aria-hidden="true"></span>${statusLabel}</span>
          </div>

          <div class="sp-divider"></div>

          <div class="sp-info-list">
            <div class="sp-info-row">
              <div class="sp-info-content">
                <span class="sp-info-label">Faculty ID</span>
                <span class="sp-info-value">${facultyId || 'N/A'}</span>
              </div>
            </div>
            <div class="sp-info-row">
              <div class="sp-info-content">
                <span class="sp-info-label">Email</span>
                <span class="sp-info-value">${facultyEmail || 'N/A'}</span>
              </div>
            </div>
          </div>

          <div class="sp-divider"></div>

          <div class="sp-subjects-section">
            <span class="sp-subjects-label"><i class="ph ph-books"></i> Assigned Subjects</span>
            <div class="sp-subjects-list">
              ${subjectsHtml}
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
    document.body.classList.add("modal-open");
    document.querySelector(".navbar")?.classList.add("modal-compensate");
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
    console.error('Error fetching faculty profile data:', error);
  });
}

function getYearOrder(yearLevel) {
  const value = String(yearLevel || '').trim().toLowerCase();
  if (value.startsWith('1')) return 1;
  if (value.startsWith('2')) return 2;
  if (value.startsWith('3')) return 3;
  if (value.startsWith('4')) return 4;
  return 99;
}

function formatYearLevel(yearLevel) {
  const order = getYearOrder(yearLevel);
  if (order === 1) return '1st Year';
  if (order === 2) return '2nd Year';
  if (order === 3) return '3rd Year';
  if (order === 4) return '4th Year';
  return yearLevel ? String(yearLevel) : 'No year level';
}

function buildAssignedSubjectsHtml(subjects) {
  if (!Array.isArray(subjects) || subjects.length === 0) {
    return '<span class="sp-no-subjects">No subjects assigned</span>';
  }

  const grouped = subjects.reduce((groups, subject) => {
    const programCode = String(subject.program_code || 'N/A').trim() || 'N/A';
    const programName = String(subject.program_name || 'Unassigned Program').trim() || 'Unassigned Program';
    const key = `${programCode}__${programName}`;

    if (!groups[key]) {
      groups[key] = {
        programCode,
        programName,
        subjects: []
      };
    }

    groups[key].subjects.push(subject);
    return groups;
  }, {});

  return Object.values(grouped)
    .sort((a, b) => a.programCode.localeCompare(b.programCode) || a.programName.localeCompare(b.programName))
    .map(group => {
      const itemsHtml = group.subjects
        .sort((a, b) => {
          const yearDiff = getYearOrder(a.year_level) - getYearOrder(b.year_level);
          if (yearDiff !== 0) return yearDiff;
          return String(a.code || '').localeCompare(String(b.code || ''));
        })
        .map(s => `
          <div class="sp-subject-item">
            <div class="sp-subject-main">
              <span class="sp-subject-code">${escapeHtml(s.code)}</span>
              <span class="sp-subject-desc">${escapeHtml(s.name)}</span>
            </div>
            <span class="sp-subject-year">${escapeHtml(formatYearLevel(s.year_level))}</span>
          </div>`).join('');

      return `
        <div class="sp-program-group">
          <div class="sp-program-header">
            <span class="sp-program-code">${escapeHtml(group.programCode)}</span>
            <span class="sp-program-name">${escapeHtml(group.programName)}</span>
          </div>
          <div class="sp-program-subjects">
            ${itemsHtml}
          </div>
        </div>`;
    }).join('');
}

// Function to fetch additional faculty profile data from database
async function fetchFacultyProfileData(facultyId) {
  try {
    const response = await fetch('get_faculty_profile.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `faculty_id=${facultyId}`
    });
    
    if (!response.ok) {
      throw new Error('Failed to fetch faculty profile data');
    }
    
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error:', error);
    return {};
  }
}

window.closeProfileModal = () => {
  const profileModal = document.getElementById("profileModal");
  if (profileModal) {
    profileModal.style.display = "none";
    document.body.classList.remove("modal-open");
    document.querySelector(".navbar")?.classList.remove("modal-compensate");
    document.documentElement.style.removeProperty('--scrollbar-width');
  }
};

function togglePassword(fieldId, icon) {
  const field = document.getElementById(fieldId);
  if (field.type === "password") {
    field.type = "text";
    icon.classList.remove("ph-eye-slash");
    icon.classList.add("ph-eye");
  } else {
    field.type = "password";
    icon.classList.remove("ph-eye");
    icon.classList.add("ph-eye-slash");
  }
}

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
  // type: 'success' | 'error'
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

// Global notification (outside modal)
function showGlobalNotification(message, type) {
  // type: 'success' | 'error' | 'warning'
  let notification = document.getElementById("globalNotification");
  
  if (!notification) {
    notification = document.createElement("div");
    notification.id = "globalNotification";
    notification.className = "global-notification";
    document.body.appendChild(notification);
  }
  
  let icon = '';
  if (type === "success") {
    icon = '<i class="ph ph-check-circle"></i>';
  } else if (type === "warning") {
    icon = '<i class="ph ph-warning"></i>';
  } else {
    icon = '<i class="ph ph-warning-circle"></i>';
  }
  
  notification.innerHTML = `
    <div class="notification-content ${type}">
      <div class="notification-icon">${icon}</div>
      <div class="notification-text">${message}</div>
    </div>
  `;
  
  // Remove existing classes
  notification.classList.remove("show");
  
  // Trigger animation
  setTimeout(() => {
    notification.classList.add("show");
  }, 10);
  
  // Auto hide after 3 seconds
  setTimeout(() => {
    notification.classList.remove("show");
  }, 3000);
}

function updatePassword() {
  const facultyId   = document.getElementById("facultyNumericId").value;
  const oldPass     = document.getElementById("oldPass").value;
  const newPass     = document.getElementById("newPass").value;
  const confirmPass = document.getElementById("confirmPass").value;

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

  // Send update request
  fetch("update_facultyPassForm.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      faculty_id: facultyId,
      old_password: oldPass,
      new_password: newPass
    })
  })
  .then(response => response.json())
  .then(data => {
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
  .catch(error => {
    // Re-enable button on error
    submitBtn.disabled = false;
    submitBtn.textContent = originalText;
    submitBtn.style.opacity = "1";
    submitBtn.style.cursor = "pointer";
    
    console.error("Error:", error);
    showPasswordMessageBox("An error occurred while updating password.", "error");
  });
}
// ================= GLOBAL CLICK EVENTS =================

window.addEventListener("click", function(e){
  const modal = document.getElementById("passwordForm");
  if(e.target === modal){
    closePasswordForm();}});

document.addEventListener("click", function(e){
  const menu = document.getElementById("dropdownMenu");
  const trigger = document.querySelector(".instructor-box");

  if (menu.style.display === "block" &&
      !trigger.contains(e.target) &&
      !menu.contains(e.target)) {
    menu.style.display = "none";
  }
});
document.getElementById("logoutModal").addEventListener("click", function(e){
  if (e.target.id === "logoutModal") {
    closeLogoutModal();
  }
});

function loadFacultyStats(program = 'all') {
  if (isFacultyInactive()) {
    setFacultyResultCardsUnavailable("No Data");
    return;
  }

  if (isEvaluationOngoing()) {
    setFacultyResultCardsUnavailable("Unavailable");
    return;
  }

  const periodId = selectedFacultyReportPeriod;
  if (!periodId) {
    setFacultyResultCardsUnavailable("No Data");
    return;
  }
  const params = new URLSearchParams({ period_id: String(periodId) });
  if (program && program !== 'all') params.set('program', program);
  const url = `get_faculty_stats_by_program.php?${params.toString()}`;

  setFacultyStatsLoading(true);

  fetch(url)
    .then(async response => {
      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.message || "Failed to load evaluation-period results.");
      }
      return data;
    })
    .then(data => {
      setFacultyStatsLoading(false);
      if (data.evaluation_ongoing) {
        updateFacultyResultAccess(true);
        return;
      }

      const ratingNumberEl = document.getElementById("overallRatingNumber");
      const ratingStatusEl = document.getElementById("overallRatingStatus");
      const responsesEl    = document.getElementById("totalResponsesValue");
      const subjectsEl     = document.getElementById("subjectsEvaluatedValue");

      const totalResponses = parseInt(data.total_responses || 0);
      const hasResponses = totalResponses > 0;

      if (ratingNumberEl) ratingNumberEl.textContent = data.overall_rating || '0.00';
      if (ratingStatusEl) ratingStatusEl.textContent  = hasResponses ? (data.rating_label || "No Rating Yet") : "N/A";
      if (responsesEl)    responsesEl.textContent      = `${totalResponses}`;
      if (subjectsEl)     subjectsEl.textContent       = `${Number(data.subject_count || 0)}`;
      // Populate program dropdown (only on first load when program = 'all')
      if (program === 'all' && Array.isArray(data.programs_with_data)) {
        populateProgramDropdown(data.programs_with_data);
      }
    })
    .catch(err => {
      setFacultyStatsLoading(false);
      console.error("Failed to load faculty stats:", err);
      setFacultyResultCardsUnavailable("Unable to load");
    });
}

function populateProgramDropdown(programs) {
  const select = document.getElementById("programFilterSelect");
  if (!select) return;

  // Keep the "All Programs" option, remove any old ones
  while (select.options.length > 1) select.remove(1);

  programs.forEach(prog => {
    const opt = document.createElement("option");
    // prog is an object {code, name} from the PHP response
    // Use program name as value (PHP filters by name via add_programs join)
    const name = (typeof prog === 'object' && prog !== null) ? (prog.name || prog.code || prog) : prog;
    opt.value = name;
    opt.textContent = name;
    select.appendChild(opt);
  });

  // Show the filter row only if there are multiple programs
  const filterRow = document.querySelector(".program-filter-row");
  if (filterRow) {
    filterRow.style.display = programs.length > 0 ? "flex" : "none";
  }
}

function onProgramFilterChange(program) {
  loadFacultyStats(program);
}

function renderFacultyReportPeriod(period) {
  const academicYearEl = document.getElementById("instructorAcademicPeriod");
  const periodLabelEl = academicYearEl?.querySelector(".welcome-period-label");

  if (!period) {
    if (academicYearEl) {
      academicYearEl.hidden = true;
      academicYearEl.setAttribute("aria-busy", "false");
    }
    if (periodLabelEl) periodLabelEl.textContent = "";
    return;
  }

  if (academicYearEl) {
    academicYearEl.hidden = false;
    academicYearEl.setAttribute("aria-busy", "false");
  }
  const semester = String(period.semester || "").replace(/\s+semester$/i, "").trim();
  const academicYear = String(period.academic_year || "").trim();
  if (periodLabelEl) {
    periodLabelEl.textContent = `${semester || "Semester not specified"} | ${academicYear || "Academic year not specified"}`;
  }
}

async function loadFacultyReportPeriods() {
  try {
    const response = await fetch("../shared/periods_api.php?action=faculty_periods", {
      cache: "no-store",
      credentials: "same-origin"
    });
    const data = await response.json();
    if (!response.ok || !data.success) {
      throw new Error(data.message || "Failed to load evaluation periods.");
    }
    const periods = Array.isArray(data.periods) ? data.periods : [];

    if (periods.length === 0) {
      selectedFacultyReportPeriod = null;
      renderFacultyReportPeriod(null);
      updateFacultyResultAccess(isEvaluationOngoing());
      return;
    }

    const defaultId = String(data.default_period_id || periods[0].id);
    const selectedPeriod = periods.find(period => Number(period.id) === Number(defaultId));
    selectedFacultyReportPeriod = selectedPeriod ? Number(selectedPeriod.id) : null;
    renderFacultyReportPeriod(selectedPeriod || null);
    updateFacultyResultAccess(isEvaluationOngoing());
    loadFacultyStats(document.getElementById("programFilterSelect")?.value || "all");
  } catch (error) {
    console.error("Failed to load faculty evaluation periods:", error);
    selectedFacultyReportPeriod = null;
    renderFacultyReportPeriod(null);
    updateFacultyResultAccess(isEvaluationOngoing());
  }
}

function showEvaluationReport() {
  if (isFacultyInactive()) {
    alert("Your account has been set to inactive by an admin. You cannot generate or view result until your account is active again.");
    return;
  }
  if (isEvaluationOngoing()) {
    alert("Evaluation results are unavailable while the evaluation process is still ongoing.");
    return;
  }
  if (!selectedFacultyReportPeriod) {
    alert("There are no closed evaluation period results available yet.");
    return;
  }

  // Get faculty information from hidden fields
  const facultyId = document.getElementById("facultyId")?.value;
  const facultyName = document.getElementById("facultyName")?.value;

  // Read the currently active program filter
  const programSelect = document.getElementById("programFilterSelect");
  const selectedProgram = programSelect ? programSelect.value : 'all';
  const selectedProgramLabel = (programSelect && selectedProgram !== 'all')
    ? (programSelect.options[programSelect.selectedIndex]?.text || '')
    : '';

  // Create evaluation report modal if it doesn't exist
  let reportModal = document.getElementById("evaluationReportModal");
  if (!reportModal) {
    reportModal = document.createElement("div");
    reportModal.id = "evaluationReportModal";
    reportModal.className = "evaluation-report-modal";
    document.body.appendChild(reportModal);
  }
  reportModal.innerHTML = `
    <div class="report-content report-loading" role="status" aria-live="polite">
      <i class="ph ph-circle-notch" aria-hidden="true"></i>
      <p>Loading your evaluation report...</p>
    </div>
  `;
  const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
  document.documentElement.style.setProperty('--scrollbar-width', `${scrollbarWidth}px`);
  reportModal.style.display = "flex";
  document.body.classList.add("modal-open");
  document.querySelector(".navbar")?.classList.add("modal-compensate");
  if (!reportModal.dataset.listenerAttached) {
    reportModal.addEventListener('click', function(event) {
      if (event.target === reportModal) closeEvaluationReport();
    });
    reportModal.dataset.listenerAttached = 'true';
  }

  // Fetch faculty evaluation data
  fetch("../shared/get_faculty_evaluation_report.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      faculty_id: facultyId,
      program_filter: selectedProgram !== 'all' ? selectedProgram : '',
      period_id: selectedFacultyReportPeriod
    })
  })
  .then(async response => {
    const responseText = await response.text();
    let data;
    try {
      data = JSON.parse(responseText);
    } catch (parseError) {
      console.error("Invalid evaluation report response:", responseText.slice(0, 500), parseError);
      throw new Error("The report server returned an invalid response. Please try again or contact the administrator.");
    }
    if (!response.ok || !data || !data.success) {
      throw new Error(data?.message || "Failed to load the evaluation report.");
    }
    return data;
  })
  .then(data => {
    const evaluationPeriod = data.evaluation_period || 'Evaluation period unavailable';
    const totalResponses = Number(data.total_responses || 0);
    const hasResponses = totalResponses > 0;
    
    const overallRating = hasResponses ? (data.overall_rating || '0.00') : '0.00';
    const parsedPercent = Number(data.percentage_score || 0);
    const overallPercent = Number.isFinite(parsedPercent) ? Math.max(0, Math.min(100, parsedPercent)) : 0;
    const overallPercentText = hasResponses ? `${overallPercent.toFixed(1)}%` : '0%';
    const statusLabel = hasResponses ? getOverallStatusLabel(overallRating) : 'N/A';
    const statusClass = hasResponses ? getStatusBadgeClass(overallRating) : 'status-na';
    const feedbackHtml = renderFeedbackList(data.feedback_comments || data.feedback);
    const categoryTotals = Array.isArray(data.category_totals) ? data.category_totals : [];

    const reportContent = `
      <div class="report-content">
        <div class="report-header">
          <div class="header-left">
            <img src="../assets/images/logo.png" alt="College Logo" class="college-logo">
            <div class="college-info">
              <h2>Faculty Evaluation Report</h2>
              <p class="report-subtitle">${selectedProgramLabel ? `Program: <strong>${escapeHtml(selectedProgramLabel)}</strong>` : 'A summary of student feedback and performance indicators.'}</p>
            </div>
          </div>
          <button class="close-report-btn" onclick="closeEvaluationReport()">
            <i class="ph ph-x"></i>
          </button>
        </div>

        <div class="report-body">
          <div class="report-summary-grid">
            <div class="report-card ${!hasResponses ? 'no-data' : ''}">
              <span class="report-card-label">Overall Rating</span>
              <strong class="report-card-value">${overallRating} / 5.00</strong>
              <span class="report-card-percent">${overallPercentText}</span>
              <div class="report-card-progress" aria-label="Overall rating ${overallPercentText}">
                <div class="report-card-progress-fill" style="width: ${overallPercent}%;"></div>
              </div>
              <span class="report-card-note">Weighted performance score</span>
            </div>
            <div class="report-card report-score-highlight ${!hasResponses ? 'no-data' : ''}">
              <span class="report-card-label">Performance Score</span>
              <strong class="report-card-value report-pct-value">${overallPercentText}</strong>
              <span class="report-card-note">Out of 100%</span>
            </div>
            <div class="report-card report-card-large ${statusClass}">
              <span class="report-card-label">Performance Status</span>
              <strong class="report-card-value">${statusLabel}</strong>
              <span class="report-card-note">Based on weighted ratings</span>
            </div>
            <div class="report-card ${!hasResponses ? 'no-data' : ''}">
              <span class="report-card-label">Total Responses</span>
              <strong class="report-card-value">${totalResponses}</strong>
              <span class="report-card-note">Responses received</span>
            </div>
          </div>

          <div class="category-breakdown">
            <div class="category-breakdown-header">
              <div>
                <h3>Category Total Rates</h3>
                <p>Percentage and average score for each evaluation category.</p>
              </div>
            </div>
            ${renderCategoryTotals(categoryTotals)}
          </div>

          <div class="feedback-section">
            <div class="feedback-heading">
              <h3>Student Feedback</h3>
              <span class="feedback-summary">Latest comments are shown below.</span>
            </div>
            <div class="feedback-list">
              ${feedbackHtml}
            </div>
          </div>

          <div class="report-footer">
            <button class="download-pdf-btn" onclick="downloadEvaluationReport()">
              <i class="ph ph-download"></i> Download PDF
            </button>
          </div>
        </div>
      </div>
    `;
    
    reportModal.innerHTML = reportContent;
    
    // Calculate scrollbar width and add compensation
    const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
    document.documentElement.style.setProperty('--scrollbar-width', `${scrollbarWidth}px`);
    document.body.classList.add("modal-open");
    document.querySelector(".navbar")?.classList.add("modal-compensate");
    
    reportModal.style.display = "flex";
    
    // Add click outside to close functionality once
    if (!reportModal.dataset.listenerAttached) {
      reportModal.addEventListener('click', function(event) {
        if (event.target === reportModal) {
          closeEvaluationReport();
        }
      });
      reportModal.dataset.listenerAttached = 'true';
    }
    
    // Store data for PDF download
    reportModal.dataset.facultyName = facultyName || 'N/A';
    reportModal.dataset.facultyId = facultyId || 'N/A';
    reportModal.dataset.programFilter = selectedProgramLabel || '';
    reportModal.dataset.totalResponses = totalResponses;
    reportModal.dataset.overallRating = overallRating;
    reportModal.dataset.feedback = data.feedback || 'No feedback available';
    reportModal.dataset.feedbackComments = JSON.stringify(data.feedback_comments || []);
    reportModal.dataset.evaluationPeriod = evaluationPeriod;
    reportModal.dataset.academicYear = data.period?.academic_year || '';
    reportModal.dataset.semester = data.period?.semester || '';
    reportModal.dataset.categoryTotals = JSON.stringify(categoryTotals);
  })
  .catch(error => {
    console.error("Error fetching evaluation report:", error);
    reportModal.innerHTML = `
      <div class="report-content report-error" role="alert">
        <i class="ph ph-warning-circle" aria-hidden="true"></i>
        <h2>Report unavailable</h2>
        <p>${escapeHtml(error.message || "An error occurred while loading the evaluation report.")}</p>
        <button type="button" class="close-report-btn" onclick="closeEvaluationReport()">Close</button>
      </div>
    `;
  });
}

function downloadEvaluationReport() {
  const modal = document.getElementById("evaluationReportModal");
  if (!modal) return;
  
  // Get stored data
  const facultyName = modal.dataset.facultyName || 'N/A';
  const facultyId = modal.dataset.facultyId || 'N/A';
  const totalResponses = modal.dataset.totalResponses || 0;
  const overallRating = modal.dataset.overallRating || '0.00';
  const feedback = modal.dataset.feedback || 'No feedback available';
  const evaluationDetails = JSON.parse(modal.dataset.categoryTotals || '[]').map(category => ({
    category: category.category_name || 'Uncategorized',
    average_score: category.avg_rating || '0.00',
    responses: Number(category.responses || 0),
    normalised_weight: Number(category.normalised_weight || 0)
  }));
  
  // Create PDF content
  const pdfContent = {
    reportType: 'faculty',
    facultyName: facultyName,
    facultyId: facultyId,
    totalResponses: totalResponses,
    overallRating: overallRating,
    feedback: feedback,
    allFeedback: feedback,
    evaluationPeriod: modal.dataset.evaluationPeriod || 'All evaluation periods',
    academicYear: modal.dataset.academicYear || '',
    semester: modal.dataset.semester || '',
    programFilter: modal.dataset.programFilter || '',
    evaluationDetails: evaluationDetails
  };
  
  // Send to PDF generation endpoint
  fetch("../shared/generate_evaluation_pdf.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(pdfContent)
  })
  .then(async response => {
    const contentType = (response.headers.get('content-type') || '').toLowerCase();
    if (!response.ok) {
      const text = await response.text();
      throw new Error(text || 'Network response was not ok');
    }
    if (!contentType.includes('application/pdf')) {
      const text = await response.text();
      throw new Error('Server returned a non-PDF response: ' + (text || contentType));
    }
    return response.arrayBuffer();
  })
  .then(buffer => {
    const blob = new Blob([buffer], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Faculty_Evaluation_Report_${facultyName.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`;
    document.body.appendChild(a);
    a.click();
    setTimeout(() => {
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    }, 1000);
  })
  .catch(error => {
    console.error("Error generating PDF:", error);
    alert("An error occurred while generating the PDF: " + error.message);
  });
}

function closeEvaluationReport() {
  const modal = document.getElementById("evaluationReportModal");
  if (modal) {
    modal.style.display = "none";
    document.body.classList.remove("modal-open");
    document.querySelector(".navbar")?.classList.remove("modal-compensate");
    document.documentElement.style.removeProperty('--scrollbar-width');
  }
}

// Add event listener for password form back button
document.addEventListener("DOMContentLoaded", function() {
  const backBtn = document.getElementById("closePasswordForm");
  if (backBtn) {
    backBtn.addEventListener("click", function(e) {
      e.preventDefault();
      closePasswordForm();
    });
  }
});

// ================= REAL-TIME ACADEMIC PERIOD UPDATE =================

function renderInstructorEvaluationStatus(status) {
  const statusEl = document.getElementById("instructor-evaluation-status");
  if (!statusEl) return;

  statusEl.classList.remove("is-checking", "is-open", "is-closed", "is-unavailable");
  if (!status || !status.success) {
    statusEl.classList.add("is-unavailable");
    statusEl.innerHTML = '<i class="ph ph-warning-circle" aria-hidden="true"></i> Status unavailable';
  } else if (status.evaluation_open) {
    statusEl.classList.add("is-open");
    statusEl.innerHTML = '<i class="ph ph-check-circle" aria-hidden="true"></i> Evaluation is open';
  } else {
    statusEl.classList.add("is-closed");
    statusEl.innerHTML = '<i class="ph ph-lock" aria-hidden="true"></i> Evaluation is closed';
  }
}

function updateInstructorAcademicPeriodDisplay(status) {
  renderInstructorEvaluationStatus(status);
  updateFacultyResultAccess(!!status?.evaluation_open);
}

async function loadInstructorAcademicPeriod() {
  try {
    const statusRes = await fetch("../shared/periods_api.php?action=status", { cache: "no-store", credentials: "same-origin" });
    const status = await statusRes.json();
    if (status && status.success) {
      const wasOngoing = isEvaluationOngoing();
      updateInstructorAcademicPeriodDisplay(status);
      if (wasOngoing && !status.evaluation_open && !isFacultyInactive()) {
        loadFacultyReportPeriods();
      }
    }
  } catch (err) {
    console.error("Failed to load academic period:", err);
    renderInstructorEvaluationStatus(null);
  }
}

// Refresh academic period display every 1 second
function startInstructorPeriodRefresh() {
  loadInstructorAcademicPeriod(); // Load immediately
  setInterval(loadInstructorAcademicPeriod, 1000); // Then every 1 second
}

function loadUploadedPDFs() {
  const container = document.getElementById("uploadedReportsContainer");
  if (!container) return;

  container.innerHTML = '<div style="padding: 20px; text-align: center; color: var(--text);"><i class="ph ph-circle-notch" style="font-size: 24px; animation: spin 1s linear infinite; margin-right: 8px; display: inline-block;"></i>Loading uploaded reports...</div>';

  fetch('get_uploaded_reports.php')
    .then(r => r.json())
    .then(data => {
      if (!data.success || !data.reports || data.reports.length === 0) {
        container.innerHTML = `
          <div class="no-uploaded-reports">
            <i class="ph ph-file-pdf"></i>
            <p>No evaluation PDF reports uploaded to your account yet.</p>
          </div>
        `;
        return;
      }

      let html = '<div class="pdf-reports-grid">';
      data.reports.forEach(report => {
        const uploadedDate = new Date(report.uploaded_at).toLocaleString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
        html += `
          <div class="pdf-report-card">
            <div class="pdf-report-icon">
              <i class="ph ph-file-pdf"></i>
            </div>
            <div class="pdf-report-details">
              <span class="pdf-report-title">${escapeHtml(report.file_name)}</span>
              <span class="pdf-report-meta">Period: ${escapeHtml(report.academic_year)} • ${escapeHtml(report.semester)}</span>
              <span class="pdf-report-date">Uploaded: ${uploadedDate}</span>
            </div>
            <div class="pdf-report-actions">
              <a href="../shared/download_report.php?id=${report.id}" class="pdf-download-link" title="Download Report">
                <i class="ph ph-download-simple"></i> Download
              </a>
            </div>
          </div>
        `;
      });
      html += '</div>';
      container.innerHTML = html;
    })
    .catch(err => {
      console.error('Error loading uploaded reports:', err);
      container.innerHTML = '<div style="padding: 20px; text-align: center; color: #dc2626;"><i class="ph ph-warning-circle" style="font-size: 24px; margin-right: 8px; display: inline-block;"></i>Failed to load uploaded reports.</div>';
    });
}

document.addEventListener("DOMContentLoaded", function() {
  document.getElementById("evaluationReportButton")?.addEventListener("click", showEvaluationReport);
  document.getElementById("perSubjectReportButton")?.addEventListener("click", showPerSubjectReport);
  loadFacultyReportPeriods();
  startInstructorPeriodRefresh();
  loadUploadedPDFs();
});

/* ═══════════════════════════════════════════════════════════════════════
   PER-SUBJECT REPORT
*/

/**
 * Open the per-subject evaluation report modal for the logged-in faculty.
 */
function showPerSubjectReport() {
  if (isFacultyInactive()) {
    alert('Your account has been set to inactive by an admin. You cannot generate or view results until your account is active again.');
    return;
  }
  if (isEvaluationOngoing()) {
    alert('Evaluation results are unavailable while the evaluation process is still ongoing.');
    return;
  }
  if (!selectedFacultyReportPeriod) {
    alert('There are no closed evaluation period results available yet.');
    return;
  }

  const facultyId   = document.getElementById('facultyId')?.value || '';
  const facultyName = document.getElementById('facultyName')?.value || 'Instructor';

  const modal        = document.getElementById('perSubjectReportModal');
  const modalContent = modal.querySelector('.per-subject-modal-content');

  // Show loading state
  modalContent.innerHTML = buildPerSubjectReportSkeletonHtml();
  openPerSubjectModal();

  fetch('get_faculty_per_subject_report.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ faculty_id: facultyId, period_id: selectedFacultyReportPeriod }),
  })
    .then(async response => {
      const text = await response.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch (error) {
        console.error("Invalid per-subject report response:", text.slice(0, 500), error);
        throw new Error("The report service returned an invalid response.");
      }
      if (!response.ok || !data.success) {
        throw new Error(data.message || "Failed to load the per-subject report.");
      }
      return data;
    })
    .then(data => {
      // Store raw data for PDF download
      modal.dataset.reportData = JSON.stringify({
        facultyName,
        facultyId,
        period: data.period,
        departments: data.departments
      });

      modalContent.innerHTML = buildPerSubjectReportHtml(data.departments);
    })
    .catch(err => {
      console.error('Per-subject report error:', err);
      modalContent.innerHTML = `<div class="psm-error" role="alert"><i class="ph ph-warning-circle"></i><p>${escapeHtml(err.message || 'An error occurred while loading the report.')}</p><button class="psm-close-btn" onclick="closePerSubjectReport()">Close</button></div>`;
    });
}

function buildPerSubjectReportSkeletonHtml() {
  return `
    <div class="psm-skeleton" aria-label="Loading per-subject report" aria-busy="true">
      <div class="psm-header psm-skeleton-header">
        <div class="psm-header-left">
          <div class="psm-logo psm-skeleton-block"></div>
          <div class="psm-skeleton-heading">
            <span class="psm-skeleton-line psm-title-line"></span>
            <span class="psm-skeleton-line psm-subtitle-line"></span>
          </div>
        </div>
        <div class="psm-header-actions">
          <span class="psm-skeleton-button"></span>
          <span class="psm-skeleton-icon-button"></span>
        </div>
      </div>

      <div class="psm-body">
        <div class="psm-subjects-section">
          <p class="psm-section-sub">Separated by program / department</p>
          <section class="psm-dept psm-dept-skeleton">
            <div class="psm-dept-header">
              <span class="psm-skeleton-icon"></span>
              <span class="psm-skeleton-line psm-dept-line"></span>
            </div>
            ${Array.from({ length: 2 }).map(() => `
              <div class="psm-subject-card">
                <div class="psm-subject-header">
                  <div class="psm-subject-title-row psm-skeleton-subject-lines">
                    <span class="psm-skeleton-line psm-code-line"></span>
                    <span class="psm-skeleton-line psm-desc-line"></span>
                  </div>
                  <span class="psm-skeleton-line psm-period-line"></span>
                </div>
                <div class="psm-subject-meta">
                  <span class="psm-meta-item psm-meta-skeleton"></span>
                  <span class="psm-meta-item psm-meta-skeleton"></span>
                  <span class="psm-meta-item psm-meta-skeleton"></span>
                </div>
                <div class="psm-comments psm-comments-skeleton"></div>
              </div>
            `).join('')}
          </section>
        </div>
      </div>
    </div>`;
}

function openPerSubjectModal() {
  const modal = document.getElementById('perSubjectReportModal');
  const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
  document.documentElement.style.setProperty('--scrollbar-width', `${scrollbarWidth}px`);
  document.body.classList.add('modal-open');
  document.querySelector('.navbar')?.classList.add('modal-compensate');
  modal.style.display = 'flex';

  if (!modal.dataset.listenerAttached) {
    modal.addEventListener('click', e => { if (e.target === modal) closePerSubjectReport(); });
    modal.dataset.listenerAttached = 'true';
  }
}

function closePerSubjectReport() {
  const modal = document.getElementById('perSubjectReportModal');
  if (modal) {
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');
    document.querySelector('.navbar')?.classList.remove('modal-compensate');
    document.documentElement.style.removeProperty('--scrollbar-width');
  }
}

/**
 * Build the inner HTML for the per-subject report modal.
 */
function buildPerSubjectReportHtml(departments) {
  let deptHtml = '';
  if (!departments || departments.length === 0) {
    deptHtml = '<div class="psm-empty"><i class="ph ph-clipboard-text"></i><p>No subject-level evaluation data found.</p></div>';
  } else {
    departments.forEach(dept => {
      const subjectCount = Array.isArray(dept.subjects) ? dept.subjects.length : 0;
      deptHtml += `
        <section class="psm-dept">
          <div class="psm-dept-header">
            <i class="ph ph-graduation-cap"></i>
            <span class="psm-dept-name">${escapeHtml(dept.program_name)}${dept.program_code ? ' <em>(' + escapeHtml(dept.program_code) + ')</em>' : ''}</span>
            <span class="psm-dept-count">${subjectCount} ${subjectCount === 1 ? 'subject' : 'subjects'}</span>
          </div>
          ${buildSubjectsHtml(dept.subjects || [])}
        </section>`;
    });
  }

  return `
    <div class="psm-header">
      <div class="psm-header-left">
        <img src="../assets/images/logo.png" alt="Logo" class="psm-logo">
        <div>
          <h2 id="perSubjectModalTitle">Per-Subject Evaluation Report</h2>
          <p class="psm-subtitle">Separated by program / department</p>
        </div>
      </div>
      <div class="psm-header-actions">
        <button class="psm-download-btn" onclick="downloadPerSubjectReportPdf()">
          <i class="ph ph-download-simple"></i> Download PDF
        </button>
        <button class="psm-close-btn" onclick="closePerSubjectReport()" aria-label="Close">
          <i class="ph ph-x"></i>
        </button>
      </div>
    </div>

    <div class="psm-body">
      <div class="psm-subjects-section">
        ${deptHtml}
      </div>

    </div>`;
}

function buildSubjectsHtml(subjects) {
  if (!subjects.length) return '<p class="psm-no-subjects">No evaluation data for any subject in this program.</p>';

  return subjects.map(s => {
    const avgValue = Number(s.avg_rating || 0);
    const pctValue = Number(s.percentage || 0);
    const avg     = Number.isFinite(avgValue) ? avgValue : 0;
    const pct     = Number.isFinite(pctValue) ? Math.max(0, Math.min(100, pctValue)) : 0;
    const badge   = getStatusBadgeClass(avg).replace(/^status-/, '');
    const evalCount = Number(s.eval_count || 0);
    const hasData = evalCount > 0;
    const comments = Array.isArray(s.comments) ? s.comments.filter(comment => String(comment || '').trim()) : [];
    const categories = Array.isArray(s.category_totals) ? s.category_totals : [];

    const commentsHtml = comments.length > 0
      ? `<div class="psm-comments">
           <h4 class="psm-comments-title"><i class="ph ph-chat-circle-dots"></i> Student Comments <span class="psm-count-badge">${comments.length}</span></h4>
           <div class="psm-comments-list">
             ${comments.slice(0, 6).map((c, i) => `
               <div class="psm-comment-item">
                 <span class="psm-comment-num" aria-hidden="true">${i + 1}</span>
                 <p>${escapeHtml(c)}</p>
               </div>`).join('')}
             ${comments.length > 6 ? `<p class="psm-more-comments">Showing 6 of ${comments.length} comments</p>` : ''}
           </div>
         </div>`
      : '<div class="psm-no-comments"><i class="ph ph-chat-slash"></i><p>No comments submitted for this subject.</p></div>';

    return `
      <div class="psm-subject-card">
        <div class="psm-subject-header">
          <div class="psm-subject-title-row">
            <span class="psm-subject-code">${escapeHtml(s.subject_code || 'Subject')}</span>
            <span class="psm-subject-desc">${escapeHtml(s.subject_desc || 'Course evaluation')}</span>
          </div>
          <span class="psm-period-tag"><i class="ph ph-calendar"></i> <span>${escapeHtml(s.period || 'Period unavailable')}</span></span>
        </div>

        <div class="psm-subject-meta">
          <div class="psm-meta-item">
            <span class="psm-meta-label">Avg Rating</span>
            <strong class="psm-meta-value">${avg.toFixed(2)} <span>/ 5.00</span></strong>
          </div>
          <div class="psm-meta-item">
            <span class="psm-meta-label">Performance Score</span>
            <strong class="psm-meta-value">${pct.toFixed(1)}%</strong>
          </div>
          <div class="psm-meta-item">
            <span class="psm-meta-label">Evaluations</span>
            <strong class="psm-meta-value">${evalCount}</strong>
          </div>
          <div class="psm-meta-item">
            <span class="psm-meta-label">Status</span>
            <span class="report-badge ${hasData ? badge : 'na'} psm-status-badge">${escapeHtml(hasData ? (s.rating_label || 'No Rating') : 'No Responses')}</span>
          </div>
        </div>

        <div class="psm-subject-progress" role="img" aria-label="Performance score ${pct.toFixed(1)} percent">
          <div class="psm-prog-track"><div class="psm-prog-fill ${badge}" style="width:${hasData ? pct : 0}%"></div></div>
        </div>

        <div class="psm-subject-categories">
          <h4 class="psm-subsection-title"><i class="ph ph-chart-bar"></i> Category Breakdown</h4>
          ${renderCategoryTotals(categories)}
        </div>

        ${commentsHtml}
      </div>`;
  }).join('');
}

/**
 * Download the per-subject report as a PDF.
 */
function downloadPerSubjectReportPdf() {
  const modal = document.getElementById('perSubjectReportModal');
  if (!modal || !modal.dataset.reportData) return;

  let reportData;
  try {
    reportData = JSON.parse(modal.dataset.reportData);
  } catch (e) {
    alert('Report data is not available. Please reload the report.');
    return;
  }

  fetch('generate_per_subject_report_pdf.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(reportData),
  })
    .then(r => {
      if (!r.ok) throw new Error('PDF generation failed');
      return r.blob();
    })
    .then(blob => {
      const url  = URL.createObjectURL(blob);
      const a    = document.createElement('a');
      a.href     = url;
      a.download = `Per_Subject_Report_${(reportData.facultyName || 'Faculty').replace(/\s+/g, '_')}_${new Date().toISOString().slice(0, 10)}.pdf`;
      document.body.appendChild(a);
      a.click();
      URL.revokeObjectURL(url);
      document.body.removeChild(a);
    })
    .catch(err => {
      console.error('PDF download error:', err);
      alert('An error occurred while generating the PDF.');
    });
}
