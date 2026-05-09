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

function showPasswordForm(){
  document.getElementById("passwordForm").style.display = "flex";}

function closePasswordForm(){
  document.getElementById("passwordForm").style.display = "none";}

function showProfile(){
  // Close dropdown first
  document.getElementById("dropdownMenu").style.display = "none";
  
  // Get faculty information from hidden fields
  const facultyId = document.getElementById("facultyId")?.value;
  const facultyName = document.getElementById("facultyName")?.value;
  const facultyEmail = document.getElementById("facultyEmail")?.value;
  
  // Create profile modal content
  const profileContent = `
    <div class="profile-header">
      <div class="profile-icon">
        <i class="ph ph-user-circle"></i>
      </div>
      <h3>Faculty Profile</h3>
    </div>
    <div class="profile-info">
      <p><strong>Full Name:</strong> ${facultyName || 'N/A'}</p>
      <p><strong>Faculty ID:</strong> ${facultyId || 'N/A'}</p>
      <p><strong>Email:</strong> ${facultyEmail || 'N/A'}</p>
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
  
  // Add click outside to close functionality
  profileModal.addEventListener('click', function(event) {
    if (event.target === profileModal) {
      closeProfileModal();
    }
  });
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

function updatePassword() {
  const facultyId = document.getElementById("facultyNumericId").value;
  const oldPass = document.getElementById("oldPass").value;
  const newPass = document.getElementById("newPass").value;
  const confirmPass = document.getElementById("confirmPass").value;

  // Validation
  if (!oldPass || !newPass || !confirmPass) {
    alert("All fields are required.");
    return;
  }

  if (newPass !== confirmPass) {
    alert("New passwords do not match.");
    return;
  }

  if (newPass.length < 8) {
    alert("Password must be at least 8 characters long.");
    return;
  }

  // Send update request
  fetch("update_facultyPassForm.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      faculty_id: facultyId,
      old_password: oldPass,
      new_password: newPass
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert("Password updated successfully!");
      closePasswordForm();
      // Clear form fields
      document.getElementById("oldPass").value = "";
      document.getElementById("newPass").value = "";
      document.getElementById("confirmPass").value = "";
    } else {
      alert(data.message || "Failed to update password.");
    }
  })
  .catch(error => {
    console.error("Error:", error);
    alert("An error occurred while updating password.");
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
  fetch("get_faculty_stats.php")
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      const ratingNumberEl = document.getElementById("overallRatingNumber");
      const ratingStatusEl = document.getElementById("overallRatingStatus");
      const responsesEl = document.getElementById("totalResponsesValue");

      if (ratingNumberEl) {
        ratingNumberEl.textContent = `${data.overall_rating} / 5.00`;
      }
      
      if (ratingStatusEl) {
        ratingStatusEl.textContent = data.rating_label || "No Rating Yet";
      }
      
      if (responsesEl) {
        responsesEl.textContent = `${data.total_responses}`;
      }
    })
    .catch(err => {
      console.error("Failed to load faculty stats:", err);
    });
}

function showEvaluationReport() {
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
    if (data.success) {
      const reportContent = `
        <div class="report-content">
          <div class="report-header">
            <div class="header-left">
              <img src="logo.png" alt="College Logo" class="college-logo">
              <div class="college-info">
                <h2>Granby Colleges of Science and Technology</h2>
                <p class="report-subtitle">Faculty Evaluation Report</p>
              </div>
            </div>
            <button class="close-report-btn" onclick="closeEvaluationReport()">
              <i class="ph ph-x"></i>
            </button>
          </div>
          
          
            <div class="report-body">
            <div class="faculty-details">
              <div class="detail-item">
                <label>Name:</label>
                <span>${facultyName || 'N/A'}</span>
              </div>
              <div class="detail-item">
                <label>Faculty ID:</label>
                <span>${facultyId || 'N/A'}</span>
              </div>
              <div class="detail-item">
                <label>Overall Rating:</label>
                <span>${data.overall_rating || '0.00'} / 5.00</span>
              </div>
              <div class="detail-item">
                <label>Total Student Responses:</label>
                <span>${data.total_responses || '0'}</span>
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
      
      // Add click outside to close functionality
      reportModal.addEventListener('click', function(event) {
        if (event.target === reportModal) {
          closeEvaluationReport();
        }
      });
      
      // Store data for PDF download
      reportModal.dataset.facultyName = facultyName || 'N/A';
      reportModal.dataset.facultyId = facultyId || 'N/A';
      reportModal.dataset.totalResponses = data.total_responses || 0;
      reportModal.dataset.overallRating = data.overall_rating || '0.00';
      reportModal.dataset.feedback = data.feedback || 'No feedback available';
    } else {
      alert("Failed to load evaluation report: " + (data.message || "Unknown error"));
    }
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

document.addEventListener("DOMContentLoaded", loadFacultyStats);
