// DROPDOWN
function toggleDropdown() {
  const menu = document.getElementById("dropdownMenu");

  menu.style.display = (menu.style.display === "block") ? "none" : "block";}
function logout(e){
  e.preventDefault();
  document.getElementById("logoutModal").style.display = "flex";}

function closeLogoutModal(){
  document.getElementById("logoutModal").style.display = "none";}

function confirmLogout(){
  window.location.href = "EvalMain.php";}

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

function escapeHtml(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
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
            const percent     = getRatingPercentageValue(avg);
            const percentText = formatRatingPercentage(avg);
            const statusLabel = getOverallStatusLabel(avg);
            const statusClass = getStatusBadgeClass(avg).replace(/^status-/, '');
            const normW       = parseFloat(item.normalised_weight || item.weight || 0);
            const weightText  = hasWeights
              ? `${normW.toFixed(1)}%`
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
  
  // Get faculty information from hidden fields
  const facultyId = document.getElementById("facultyId")?.value;
  const facultyName = document.getElementById("facultyName")?.value;
  const facultyEmail = document.getElementById("facultyEmail")?.value;
  
  // Fetch additional faculty data from database
  fetchFacultyProfileData(facultyId).then(additionalData => {
    // Create profile modal content with real data
    const profileContent = `
      <div class="profile-header">
        <h3>Faculty Profile</h3>
      </div>
      <div class="profile-body">
        <div class="profile-header-info">
          <div class="profile-avatar">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png" alt="Profile Picture">
          </div>
          <div class="profile-name-section">
            <h4>${facultyName || 'Faculty'}</h4>
            <p>Faculty Instructor</p>
          </div>
        </div>
        <div class="profile-fields">
          <div class="field-group">
            <div class="field-label">Email</div>
            <div class="field-value">${facultyEmail || 'N/A'}</div>
          </div>
          <div class="field-group">
            <div class="field-label">Faculty ID</div>
            <div class="field-value">${facultyId || 'N/A'}</div>
          </div>
        </div>
      </div>
      <div class="profile-buttons">
        <button class="btn close-profile-btn" onclick="closeProfileModal()">Close</button>
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
    
    profileModal.innerHTML = `
      <div class="logout-content">
        ${profileContent}
      </div>
    `;
    
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
  document.getElementById("profileModal").style.display = "none";
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

function loadFacultyStats() {
  if ((document.getElementById("facultyStatus")?.value || "active").toLowerCase() !== "active") {
    const ratingNumberEl     = document.getElementById("overallRatingNumber");
    const ratingPctEl        = document.getElementById("overallRatingPercentage");
    const ratingStatusEl     = document.getElementById("overallRatingStatus");
    const responsesEl        = document.getElementById("totalResponsesValue");
    const progressFill       = document.getElementById("overallRatingProgressFill");
    if (ratingNumberEl)  ratingNumberEl.textContent  = "0.00 / 5.00";
    if (ratingPctEl)     ratingPctEl.textContent      = "0%";
    if (ratingStatusEl)  ratingStatusEl.textContent   = "No Data";
    if (responsesEl)     responsesEl.textContent       = "0";
    if (progressFill)    progressFill.style.width      = "0%";
    return;
  }

  fetch("get_faculty_stats.php")
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;

      const ratingNumberEl = document.getElementById("overallRatingNumber");
      const ratingPctEl    = document.getElementById("overallRatingPercentage");
      const ratingStatusEl = document.getElementById("overallRatingStatus");
      const responsesEl    = document.getElementById("totalResponsesValue");
      const progressFill   = document.getElementById("overallRatingProgressFill");

      const pct = parseFloat(data.percentage_score || 0);

      if (ratingNumberEl) ratingNumberEl.textContent = `${data.overall_rating} / 5.00`;
      if (ratingPctEl)    ratingPctEl.textContent     = `${pct.toFixed(1)}%`;
      if (ratingStatusEl) ratingStatusEl.textContent  = data.rating_label || "No Rating Yet";
      if (responsesEl)    responsesEl.textContent      = `${data.total_responses}`;
      if (progressFill)   progressFill.style.width     = `${Math.min(pct, 100)}%`;
    })
    .catch(err => {
      console.error("Failed to load faculty stats:", err);
    });
}

function showEvaluationReport() {
  if ((document.getElementById("facultyStatus")?.value || "active").toLowerCase() !== "active") {
    alert("Your account has been set to inactive by an admin. You cannot generate or view result until your account is active again.");
    return;
  }

  // Get faculty information from hidden fields
  const facultyId = document.getElementById("facultyId")?.value;
  const facultyName = document.getElementById("facultyName")?.value;
  
  // Create evaluation report modal if it doesn't exist
  let reportModal = document.getElementById("evaluationReportModal");
  if (!reportModal) {
    reportModal = document.createElement("div");
    reportModal.id = "evaluationReportModal";
    reportModal.className = "evaluation-report-modal";
    document.body.appendChild(reportModal);
  }
  
  // Fetch faculty evaluation data
  fetch("get_faculty_evaluation_report.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      faculty_id: facultyId
    })
  })
  .then(response => response.json())
  .then(data => {
    // Always show the modal with back button
    const evaluationPeriod = data.success ? (data.evaluation_period || 'All evaluation periods') : 'All evaluation periods';
    const overallRating   = data.success ? (data.overall_rating || '0.00') : '0.00';
    const overallPercent  = data.success && data.percentage_score
      ? parseFloat(data.percentage_score)
      : getRatingPercentageValue(overallRating);
    const overallPercentText = `${overallPercent.toFixed(1)}%`;
    const statusLabel    = getOverallStatusLabel(overallRating);
    const statusClass    = getStatusBadgeClass(overallRating);
    const feedbackHtml = renderFeedbackList(data.success ? (data.feedback_comments || data.feedback) : []);

    const reportContent = `
      <div class="report-content">
        <div class="report-header">
          <div class="header-left">
            <img src="logo.png" alt="College Logo" class="college-logo">
            <div class="college-info">
              <h2>Faculty Evaluation Report</h2>
              <p class="report-subtitle">A summary of student feedback and performance indicators.</p>
            </div>
          </div>
          <button class="close-report-btn" onclick="closeEvaluationReport()">
            <i class="ph ph-x"></i>
          </button>
        </div>

        <div class="report-body">
          <div class="report-context">
            <p>This report aggregates student responses from <strong>${evaluationPeriod}</strong> and highlights your current teaching performance and feedback trends.</p>
          </div>

          <div class="report-summary-grid">
            <div class="report-card">
              <span class="report-card-label">Overall Rating</span>
              <strong class="report-card-value">${overallRating} / 5.00</strong>
              <span class="report-card-percent">${overallPercentText}</span>
              <div class="report-card-progress" aria-label="Overall rating ${overallPercentText}">
                <div class="report-card-progress-fill" style="width: ${overallPercent}%;"></div>
              </div>
              <span class="report-card-note">Weighted performance score</span>
            </div>
            <div class="report-card report-score-highlight">
              <span class="report-card-label">Performance Score</span>
              <strong class="report-card-value report-pct-value">${overallPercentText}</strong>
              <span class="report-card-note">Out of 100%</span>
            </div>
            <div class="report-card report-card-large ${statusClass}">
              <span class="report-card-label">Performance Status</span>
              <strong class="report-card-value">${statusLabel}</strong>
              <span class="report-card-note">Based on weighted ratings</span>
            </div>
            <div class="report-card">
              <span class="report-card-label">Total Responses</span>
              <strong class="report-card-value">${data.success ? (data.total_responses || '0') : '0'}</strong>
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
            ${renderCategoryTotals(data.success ? (data.category_totals || []) : [])}
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
    reportModal.dataset.totalResponses = data.success ? (data.total_responses || 0) : 0;
    reportModal.dataset.overallRating = data.success ? (data.overall_rating || '0.00') : '0.00';
    reportModal.dataset.feedback = data.success ? (data.feedback || 'No feedback available') : 'No feedback available';
    reportModal.dataset.feedbackComments = JSON.stringify(data.success ? (data.feedback_comments || []) : []);
    reportModal.dataset.evaluationPeriod = data.success ? (data.evaluation_period || 'All evaluation periods') : 'All evaluation periods';
  })
  .catch(error => {
    console.error("Error fetching evaluation report:", error);
    alert("An error occurred while loading the evaluation report.");
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
  
  // Create PDF content
  const pdfContent = {
    facultyName: facultyName,
    facultyId: facultyId,
    totalResponses: totalResponses,
    overallRating: overallRating,
    feedback: feedback
  };
  
  // Send to PDF generation endpoint
  fetch("generate_evaluation_pdf.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(pdfContent)
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    return response.blob();
  })
  .then(blob => {
    // Create download link
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Faculty_Evaluation_Report_${facultyName.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`;
    document.body.appendChild(a);
    a.click();
    
    // Clean up immediately
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  })
  .catch(error => {
    console.error("Error generating PDF:", error);
    alert("An error occurred while generating the PDF.");
  });
}

function closeEvaluationReport() {
  const modal = document.getElementById("evaluationReportModal");
  if (modal) {
    modal.style.display = "none";
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

document.addEventListener("DOMContentLoaded", loadFacultyStats);
